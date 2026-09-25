<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\EstadoSolicitud;
use App\Models\TipoSolicitud;
use App\Models\HistorialEstadoSolicitud; // ¡Bonus para tu proyecto!
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    // Mostrar estadísticas y tabla (Equivale a los SELECT del inicio)
    public function index(Request $request)
    {
        // Traemos las solicitudes con sus relaciones (Eager Loading para evitar el N+1 y los JOINs manuales)
        $query = Solicitud::with(['usuario', 'tipoSolicitud', 'estadoActual']);

        // Filtro: buscar por cédula O por nombre del estudiante en un solo campo.
        // whereHas() permite filtrar Solicitud según una condición de su relación 'usuario'.
        // Dentro del closure, encadenar where()->orWhere()->orWhere() arma la condición:
        // (cédula LIKE ... O nombre LIKE ... O apellido LIKE ...)
        if ($request->filled('busqueda')) {
            $termino = $request->input('busqueda');
            $query->whereHas('usuario', function ($q) use ($termino) {
                $q->where('usu_numero_documento', 'like', '%' . $termino . '%')
                  ->orWhere('usu_primer_nombre', 'like', '%' . $termino . '%')
                  ->orWhere('usu_primer_apellido', 'like', '%' . $termino . '%');
            });
        }

        // Filtro: buscar por tipo de solicitud (Cambio de Carrera, Constancia, etc.)
        if ($request->filled('tipo_solicitud')) {
            $query->where('sol_tsi_id', $request->input('tipo_solicitud'));
        }

        // Filtro: buscar por estado (pendiente / aprobada / rechazada).
        // Es el filtro más usado en el día a día: el admin casi siempre quiere ver
        // primero "qué me falta por resolver" antes que el historial completo.
        if ($request->filled('estado')) {
            $query->whereHas('estadoActual', function ($q) use ($request) {
                $q->where('eso_nombre_estado', $request->input('estado'));
            });
        }

        // Orden: las solicitudes PENDIENTES siempre arriba (sin importar qué tan
        // recientes sean), y entre las pendientes, la más antigua primero (FIFO,
        // como una fila de banco: al que lleva más tiempo esperando se le atiende primero).
        // Para esto necesitamos un JOIN (en vez de whereHas) porque queremos ORDENAR
        // por una columna de la tabla relacionada, no solo filtrar por ella.
        $solicitudes = $query
            ->join('estado_solicitudes', 'estado_solicitudes.eso_id', '=', 'solicitudes.sol_eso_id')
            ->select('solicitudes.*')
            ->orderByRaw("CASE WHEN estado_solicitudes.eso_nombre_estado = 'pendiente' THEN 0 ELSE 1 END")
            ->orderBy('solicitudes.sol_fecha_creacion', 'ASC')
            ->paginate(20) // Con 20,000 solicitudes, cargar todo de una vez sería impensable.
            ->withQueryString(); // Conserva los filtros activos al cambiar de página.

        // Las estadísticas de las tarjetas (Total/Pendientes/Aprobadas/Rechazadas) se calculan
        // siempre sobre TODAS las solicitudes, no sobre el resultado filtrado/paginado, para que
        // el admin siga viendo el panorama completo del sistema aunque esté buscando algo puntual.
        $todasLasSolicitudes = Solicitud::with('estadoActual')->get();
        $stats = [
            'pendiente' => $todasLasSolicitudes->where('estadoActual.eso_nombre_estado', 'pendiente')->count(),
            'aprobada'  => $todasLasSolicitudes->where('estadoActual.eso_nombre_estado', 'aprobada')->count(),
            'rechazada' => $todasLasSolicitudes->where('estadoActual.eso_nombre_estado', 'rechazada')->count(),
            'total'     => $todasLasSolicitudes->count(),
        ];

        // Lista de tipos de solicitud para llenar el <select> del filtro
        $tiposSolicitud = TipoSolicitud::orderBy('tsi_nombre_tipo')->get();

        // Si la petición viene de nuestro JS (fetch con el header X-Requested-With),
        // devolvemos SOLO el pedazo de la tabla, no la página completa con el layout,
        // el buscador, etc. Así el navegador no necesita recargar nada.
        if ($request->ajax()) {
            return view('admin.partials.resultados', compact('solicitudes'));
        }

        return view('admin.dashboard', compact('solicitudes', 'stats', 'tiposSolicitud'));
    }

    // Cambiar estado (Equivale a action=aprobar o action=rechazar)
    public function cambiarEstado(Request $request, $id, $accion)
    {
        // $accion puede ser 'aprobar' o 'rechazar'
        $solicitud = Solicitud::findOrFail($id);
        
        // 1. Buscamos el ID del estado destino en la tabla estado_solicitudes
        $nombreEstado = ($accion === 'aprobar') ? 'aprobada' : 'rechazada';
        $estadoNuevo = EstadoSolicitud::where('eso_nombre_estado', $nombreEstado)->firstOrFail();

        // Bonus: Guardar el historial antes de cambiarlo (Opcional pero recomendado para tu sistema)
        HistorialEstadoSolicitud::create([
            'hes_sol_id' => $solicitud->sol_id,
            'hes_usu_id_responsable' => auth()->user()->usu_id ?? 1, // ID del admin autenticado
            'hes_eso_id_anterior' => $solicitud->sol_eso_id,
            'hes_eso_id_nuevo' => $estadoNuevo->eso_id,
        ]);

        // 2. Actualizamos la solicitud con el nuevo ID de estado
        $solicitud->update([
            'sol_eso_id' => $estadoNuevo->eso_id
        ]);

        $mensaje = ($accion === 'aprobar') 
            ? 'La solicitud ha sido aprobada con éxito.' 
            : 'La solicitud ha sido rechazada.';

        // Si el pedido viene del JS del modal (fetch), respondemos con un
        // simple "OK" en vez de redirigir. Así el modal se queda abierto
        // y solo actualiza el color, sin recargar toda la página.
        if ($request->ajax()) {
            return response()->json([
                'estado' => $nombreEstado,
                'mensaje' => $mensaje,
            ]);
        }

        return redirect()->route('admin.dashboard')->with('success', $mensaje);
    }
}