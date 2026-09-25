{{-- Este fragmento se reutiliza en dos casos:
     1. La carga normal de la página (dentro de dashboard.blade.php)
     2. Las respuestas AJAX cuando se busca/filtra/pagina sin recargar la página --}}
@if($solicitudes->isEmpty())
  @if(request('busqueda') || request('tipo_solicitud') || request('estado'))
    <p>No se encontraron solicitudes que coincidan con la búsqueda.</p>
  @else
    <p>No hay solicitudes registradas en el sistema todavía.</p>
  @endif
@else
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Estudiante</th>
          <th>Cédula</th>
          <th>Tipo de Trámite</th>
          <th>Detalles/Asunto</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th style="text-align: right;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($solicitudes as $s)
          @php $estado = strtolower($s->estadoActual->eso_nombre_estado); @endphp
          <tr>
            <td>
              <strong>{{ $s->usuario->usu_primer_nombre }} {{ $s->usuario->usu_primer_apellido }}</strong>
            </td>
            <td>
              <span style="font-size: 0.85rem; display: block; color: var(--gray-700);">C.I: {{ $s->usuario->usu_numero_documento }}</span>
            </td>
            <td><span style="font-weight: 600; color: var(--red);">{{ $s->tipoSolicitud->tsi_nombre_tipo }}</span></td>
            <td style="max-width: 250px; font-size: 0.9rem;">{{ $s->sol_motivo_detallado ?? 'Sin motivo detallado' }}</td>
            <td style="font-size: 0.85rem; white-space: nowrap;">{{ \Carbon\Carbon::parse($s->sol_fecha_creacion)->format('d/m/Y') }}</td>
            <td>
              {{-- Badge con puntito de color, como en el diseño de referencia --}}
              <span class="badge-punto badge-punto--{{ $estado }}">
                <span class="badge-punto__dot"></span>
                {{ ucfirst($estado) }}
              </span>
            </td>
            <td style="text-align: right; white-space: nowrap;">
              <button
                type="button"
                class="btn btn--sm ver-detalle"
                style="background: #2563eb; color: white; margin-right: 4px;"
                data-nombre="{{ $s->usuario->usu_primer_nombre }} {{ $s->usuario->usu_primer_apellido }}"
                data-cedula="{{ $s->usuario->usu_numero_documento }}"
                data-tipo="{{ $s->tipoSolicitud->tsi_nombre_tipo }}"
                data-fecha="{{ \Carbon\Carbon::parse($s->sol_fecha_creacion)->format('d/m/Y H:i') }}"
                data-estado="{{ $estado }}"
                data-descripcion="{{ $s->sol_motivo_detallado ?? 'Sin motivo detallado' }}"
                data-aprobar-url="{{ route('admin.dashboard.estado', ['id' => $s->sol_id, 'accion' => 'aprobar']) }}"
                data-rechazar-url="{{ route('admin.dashboard.estado', ['id' => $s->sol_id, 'accion' => 'rechazar']) }}"
              >Detalle</button>
              @if($estado === 'pendiente')
                <a href="{{ route('admin.dashboard.estado', ['id' => $s->sol_id, 'accion' => 'aprobar']) }}" class="btn btn--sm" style="background: #22a35a; color: white; margin-right: 4px;">Aprobar</a>
                <a href="{{ route('admin.dashboard.estado', ['id' => $s->sol_id, 'accion' => 'rechazar']) }}" class="btn btn--danger btn--sm" onclick="return confirm('¿Seguro que deseas rechazar esta solicitud?')">Rechazar</a>
              @else
                <span style="color: var(--gray-400); font-size: 0.85rem; font-style: italic;">Sin acciones</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Paginación: la clase 'pagina-link' es la que el JS "escucha" para
       interceptar el clic y traer la página siguiente sin recargar --}}
  <div style="margin-top: 20px;">
    {{ $solicitudes->links('vendor.pagination.custom') }}
  </div>
@endif
