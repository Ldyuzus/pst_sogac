<?php

use App\Models\Cita;
use App\Models\Documentacion;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Solicitud;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(admin());
});

/**
 * La ficha de tratadas debe renderizar los adjuntos y la cita con las columnas
 * reales de la base. Antes usaba doc_nombre_archivo / doc_tipo_mime / doc_tamano
 * y cit_fecha / cit_hora_inicio / cit_hora_fin / estadoCita, que no existen, y
 * ademas enlazaba a route('admin.documentos.descargar'), que tampoco existe.
 * Con adjuntos la pagina reventaba con 500.
 */
test('la ficha de tratadas renderiza los documentos adjuntos', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    $s = solicitudConHistorial($est, $tipo, ['aprobada']);

    Documentacion::create([
        'doc_sol_id' => $s->sol_id,
        'doc_nombre_original_archivo' => 'constancia.pdf',
        'doc_tipo_documento' => 'Cédula de Identidad',
        'doc_formato_archivo' => 'PDF',
        'doc_tamano_bytes' => 204_800,
        'doc_ruta_almacenamiento_url' => 'documentos/demo/constancia.pdf',
        'doc_estado_validacion' => 'validado',
    ]);

    $this->get(route('admin.tratadas.show', $s))
        ->assertOk()
        ->assertSee('constancia.pdf')
        ->assertSee('PDF')
        ->assertSee('200.0 KB')
        ->assertSee('validado', false);
});

test('la ficha de tratadas renderiza la cita de validación', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    $s = solicitudConHistorial($est, $tipo, ['aprobada']);

    Cita::create([
        'cit_usu_id' => $est->usu_id,
        'cit_sol_id' => $s->sol_id,
        'cit_motivo' => 'Oficina de Control de Estudios',
        'cit_fecha_hora' => now()->addDays(3)->setTime(9, 30),
        'cit_estado' => 'agendada',
    ]);

    $this->get(route('admin.tratadas.show', $s))
        ->assertOk()
        ->assertSee('Cita de validación asignada')
        ->assertSee('Oficina de Control de Estudios')
        ->assertSee('agendada', false);
});

test('la ficha de tratadas no consulta una ruta de descarga inexistente', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    $s = solicitudConHistorial($est, $tipo, ['aprobada']);

    Documentacion::create([
        'doc_sol_id' => $s->sol_id,
        'doc_nombre_original_archivo' => 'soporte.jpg',
        'doc_formato_archivo' => 'JPG',
        'doc_tamano_bytes' => 1024,
        'doc_ruta_almacenamiento_url' => 'documentos/demo/soporte.jpg',
    ]);

    // Si la vista intentara route('admin.documentos.descargar') la peticion
    // reventaria; que devuelva 200 es la garantia de que ya no lo hace.
    $this->get(route('admin.tratadas.show', $s))->assertOk();
});

test('la ficha de tratadas muestra el rol de quien la atendió', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    $s = solicitudConHistorial($est, $tipo, ['aprobada']);

    $this->get(route('admin.tratadas.show', $s))
        ->assertOk()
        ->assertSee('Administrador');
});

test('el listado de tratadas muestra el rol en la columna resuelto por', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    solicitudConHistorial($est, $tipo, ['aprobada']);

    $this->get(route('admin.tratadas.index'))
        ->assertOk()
        ->assertSee('Administrador');
});

test('el historial del estudiante muestra quién atiende y con qué rol', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    $s = solicitudConHistorial($est, $tipo, ['aprobada']);

    $this->actingAs($est)
        ->get(route('user.historial.show', $s))
        ->assertOk()
        ->assertSee('Atendida por')
        ->assertSee('Administrador');
});

test('el panel del estudiante muestra la columna atendida por', function () {
    $tipo = tipoSolicitud();
    $est = estudiante();
    $s = solicitudPendienteSinHistorial($est, $tipo);

    // Un movimiento deja registrado al responsable sin resolver la solicitud.
    HistorialEstadoSolicitud::create([
        'hes_sol_id' => $s->sol_id,
        'hes_usu_id_responsable' => admin()->usu_id,
        'hes_eso_id_anterior' => estado('pendiente')->eso_id,
        'hes_eso_id_nuevo' => estado('pendiente')->eso_id,
        'hes_fecha_cambio' => now(),
    ]);

    $this->actingAs($est)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Atendida por')
        ->assertSee('Administrador');
});

test('el menú lateral del admin ofrece el filtro de solicitudes pendientes con su query', function () {
    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Solicitudes pendientes')
        ->assertSee(route('admin.dashboard', ['estado' => 'pendiente']), false);
});
