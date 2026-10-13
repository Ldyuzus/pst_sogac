@extends('layouts.plantilla_admin')

@section('title', 'Ficha de solicitud tratada')

@section('content')
<div class="container main">

    {{-- Migas --}}
    <nav class="breadcrumb" style="margin-bottom: 20px; font-size: 0.85rem; color: var(--gray-500);">
        <a href="{{ route('admin.dashboard') }}" style="color: var(--gray-500);">Inicio</a>
        <span style="margin: 0 8px;">/</span>
        <a href="{{ route('admin.tratadas.index') }}">Tratadas</a>
        <span style="margin: 0 8px;">/</span>
        <span>{{ $solicitud->sol_id_seguimiento }}</span>
    </nav>

    <div class="card" style="background: white; border-radius: var(--radius); padding: 28px; box-shadow: var(--shadow-md); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 1.4rem; font-weight: 700; margin: 0 0 4px;">{{ $solicitud->tipoSolicitud->tsi_nombre_tipo }}</h1>
                <p style="margin: 0; color: var(--gray-500); font-size: 0.95rem;">
                    Código: <strong>{{ $solicitud->sol_id_seguimiento }}</strong>
                    &nbsp;|&nbsp; Periodo: {{ $solicitud->lapsoAcademico->lac_id_lapso ?? '—' }}
                </p>
            </div>
            @php
                $est = strtolower($solicitud->estadoActual->eso_nombre_estado);
                $cls = match($est) {
                    'aprobada'  => 'badge-punto--aprobada',
                    'rechazada' => 'badge-punto--rechazada',
                    default     => 'badge-punto--pendiente',
                };
            @endphp
            <span class="badge-punto {{ $cls }}" style="font-size: 0.9rem; padding: 8px 16px;">
                <span class="badge-punto__dot"></span>
                {{ ucfirst($solicitud->estadoActual->eso_nombre_estado) }}
            </span>
            @if ($siguientePendiente)
                <a href="{{ route('admin.tratadas.show', $siguientePendiente->sol_id) }}"
                   class="btn btn--primary btn--sm"
                   style="margin-left: 8px;"
                   title="Ir a la siguiente solicitud pendiente de la cola ({{ $siguientePendiente->sol_id_seguimiento }})">
                    Siguiente pendiente
                </a>
            @endif
        </div>

        {{-- Datos principales --}}
        <div class="datos" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--gray-200);">
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Estudiante</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px;">{{ $solicitud->usuario->nombre_completo }}</div>
                <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: 2px;">{{ $solicitud->usuario->documento_completo }}</div>
                {{-- Ficha del estudiante: número, carrera y trayecto, para
                     ubicar al estudiante en las oficinas sin abrir su perfil. --}}
                <div style="font-size: 0.8rem; color: var(--gray-500); margin-top: 2px;">
                    Ficha: {{ $solicitud->usuario->numero_ficha }}
                    · {{ $solicitud->usuario->usu_pnf ?: 'Sin PNF' }}
                    · {{ $solicitud->usuario->usu_trayecto ?: 'sin trayecto' }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Trámite</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px;">{{ $solicitud->tipoSolicitud->tsi_nombre_tipo }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Prioridad</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px; text-transform: capitalize;">{{ $solicitud->sol_prioridad }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Enviada</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px;">{{ $solicitud->sol_fecha_creacion->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Resuelta</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px;">
                    @if($solicitud->sol_fecha_resolucion)
                        {{ $solicitud->sol_fecha_resolucion->format('d/m/Y H:i') }}
                    @else
                        <span style="color: var(--gray-400);">—</span>
                    @endif
                </div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Tiempo de resolución</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px; color: {{ $tiempoResolucion ? 'var(--black)' : 'var(--gray-400)' }};">
                    {{ $tiempoResolucion ?? '—' }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Primer responsable</div>
                <div style="font-size: 0.95rem; font-weight: 600; margin-top: 2px;">
                    @if($primerResponsable)
                        {{ $primerResponsable->nombre_completo }}
                        <span style="font-weight: 400; color: var(--gray-500);">({{ $primerResponsable->rol_etiqueta }})</span>
                    @else
                        <span style="color: var(--gray-400);">—</span>
                    @endif
                </div>
            </div>
            <div>
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">Última observación</div>
                <div style="font-size: 0.9rem; margin-top: 2px; color: {{ $ultimaObservacion ? 'var(--black)' : 'var(--gray-400)' }}; max-width: 280px;">
                    {{ $ultimaObservacion ?? '—' }}
                </div>
            </div>
        </div>

        {{-- Motivo detallado --}}
        @if($solicitud->sol_motivo_detallado)
            <div style="margin-bottom: 24px; padding: 16px; background: var(--gray-100); border-radius: var(--radius-sm);">
                <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px; margin-bottom: 8px;">Motivo detallado</div>
                <div style="white-space: pre-wrap;">{{ $solicitud->sol_motivo_detallado }}</div>
            </div>
        @endif

        {{-- Línea de tiempo de estados --}}
        <div style="margin-bottom: 24px;">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 2px solid var(--gray-200);">Línea de tiempo de estados</h3>
            <div class="timeline" style="position: relative; padding-left: 28px;">
                {{-- Línea vertical --}}
                <div style="position: absolute; left: 6px; top: 0; bottom: 0; width: 2px; background: var(--gray-200);"></div>

                @foreach($historial as $i => $h)
                    @php
                        $esPrimera   = $i === 0;
                        $esUltima    = $i === $historial->count() - 1;
                        $esResolucion = in_array($h->estadoNuevo?->eso_nombre_estado ?? '', ['aprobada', 'rechazada']);
                        $colorPunto  = $esResolucion
                            ? ($h->estadoNuevo->eso_nombre_estado === 'aprobada' ? '#22a35a' : 'var(--red)')
                            : ($esPrimera ? 'var(--red)' : '#d69a00');
                        $colorLinea  = !$esUltima ? 'var(--gray-200)' : 'transparent';
                    @endphp
                    <div class="timeline__item" style="position: relative; padding: 12px 0 12px 24px; min-height: 60px;">
                        {{-- Punto --}}
                        <div style="position: absolute; left: -28px; top: 16px; width: 14px; height: 14px; border-radius: 50%; background: {{ $colorPunto }}; border: 3px solid white; box-shadow: 0 0 0 2px {{ $colorPunto }}; z-index: 1;"></div>

                        {{-- Línea hacia el siguiente --}}
                        @if(!$esUltima)
                            <div style="position: absolute; left: -21px; top: 30px; bottom: -12px; width: 2px; background: {{ $colorLinea }};"></div>
                        @endif

                        <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--gray-400); font-weight: 600; letter-spacing: 0.5px;">
                            {{ $h->hes_fecha_cambio->format('d/m/Y H:i') }}
                        </div>
                        <div style="font-weight: 600; margin-top: 2px; color: {{ $colorPunto }};">
                            {{ ucfirst($h->estadoNuevo->eso_nombre_estado ?? 'desconocido') }}
                            @if($esPrimera && !$esResolucion)
                                <span style="font-size: 0.7rem; font-weight: 400; color: var(--gray-400); margin-left: 8px;">(estado inicial)</span>
                            @endif
                        </div>
                        <div style="font-size: 0.82rem; color: var(--gray-600); margin-top: 2px;">
                            Por: {{ $h->responsable->nombre_completo ?? 'Sistema' }}@if($h->responsable) <span style="color: var(--gray-400);">({{ $h->responsable->rol_etiqueta }})</span>@endif
                        </div>
                        @if($h->hes_observaciones_comentarios)
                            <div style="margin-top: 8px; padding: 10px 12px; background: var(--red-soft); border-radius: var(--radius-sm); border-left: 3px solid var(--red); font-size: 0.82rem;">
                                <strong>Observación:</strong> {{ $h->hes_observaciones_comentarios }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Adjuntos --}}
        @if($solicitud->documentaciones->count())
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 12px;">Documentos adjuntos</h3>
                <ul class="lista-simple" style="list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                    @foreach($solicitud->documentaciones as $doc)
                        <li style="background: var(--gray-100); padding: 12px; border-radius: var(--radius-sm); display: flex; align-items: center; gap: 10px;">
                            <i class="bi bi-file-earmark" style="font-size: 1.4rem; color: var(--red);"></i>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $doc->doc_nombre_original_archivo }}</div>
                                <div style="font-size: 0.75rem; color: var(--gray-500);">
                                    {{ strtoupper((string) $doc->doc_formato_archivo) }}
                                    @if($doc->doc_tamano_bytes)
                                        · {{ number_format($doc->doc_tamano_bytes / 1024, 1) }} KB
                                    @endif
                                    @if($doc->doc_estado_validacion)
                                        · <span style="text-transform: capitalize;">{{ $doc->doc_estado_validacion }}</span>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Cita de validación. El PR #20 cambio el modelo: ahora la cita es del
     estudiante (cit_usu_id) y su texto libre esta en cit_motivo. El lugar se
     guarda en cit_lugar, columna que esa migracion dejo de crear, asi que solo
     se muestra si la columna sigue existiendo en la base de quien tenga el
     esquema viejo. --}}
        @if($solicitud->cita)
            <div style="margin-bottom: 24px; padding: 16px; background: #e8f5e9; border-radius: var(--radius); border-left: 4px solid #22a35a;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: #14683a;">Cita de validación asignada</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px; font-size: 0.88rem;">
                    <div><strong>Fecha y hora:</strong> {{ $solicitud->cita->cit_fecha_hora->format('d/m/Y H:i') }}</div>
                    <div><strong>Motivo:</strong> {{ $solicitud->cita->cit_motivo ?? '—' }}</div>
                    @if (\Illuminate\Support\Facades\Schema::hasColumn('citas', 'cit_lugar'))
                        <div><strong>Lugar:</strong> {{ $solicitud->cita->cit_lugar ?? '—' }}</div>
                    @endif
                    <div><strong>Estado:</strong> <span style="text-transform: capitalize;">{{ $solicitud->cita->cit_estado }}</span></div>
                </div>
            </div>
        @endif
    </div>

    {{-- Volver --}}
    <div style="margin-top: 16px;">
        <a href="{{ route('admin.tratadas.index') }}" class="btn btn--ghost" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>
@endsection