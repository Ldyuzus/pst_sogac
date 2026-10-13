@extends('layouts.plantilla_user')

@section('title', 'Solicitud '.$solicitud->sol_id_seguimiento)

@section('content')
    @php
        use Illuminate\Support\Carbon;

        $estado = strtolower($solicitud->estadoActual->eso_nombre_estado);
    @endphp

    @include('partials.avisos')

    @include('partials.miga', [
        'miga' => [
            ['texto' => 'Inicio', 'ruta' => route('dashboard')],
            ['texto' => 'Mis solicitudes', 'ruta' => route('user.historial.index')],
            ['texto' => $solicitud->sol_id_seguimiento],
        ],
    ])

    @unless ($esPropia)
        <div class="alert alert--info">
            Estás viendo la solicitud de <strong>{{ $solicitud->usuario?->nombre_completo ?? 'un estudiante' }}</strong>
            en modo administrador: puedes consultarla, pero no modificarla desde aquí.
        </div>
    @endunless

    <div class="pagina-head">
        <div>
            <h1 class="pagina-head__titulo">{{ $solicitud->tipoSolicitud->tsi_nombre_tipo }}</h1>
            <p class="pagina-head__desc">
                Solicitud <strong>{{ $solicitud->sol_id_seguimiento }}</strong> enviada el
                {{ $solicitud->sol_fecha_creacion->format('d/m/Y') }} a las
                {{ $solicitud->sol_fecha_creacion->format('H:i') }}.
            </p>
        </div>

        <div class="pagina-head__acciones">
            <span class="badge badge--{{ $estado }}" style="font-size: 0.9rem; padding: 8px 16px;">
                {{ ucfirst($estado) }}
            </span>

            {{-- Revisión continua: el personal salta a la siguiente pendiente
                 de la cola sin volver al listado. --}}
            @if ($esPropia === false && $siguientePendiente)
                <a href="{{ route('user.historial.show', $siguientePendiente->sol_id) }}" class="btn btn--primary">
                    Siguiente pendiente
                    <span style="opacity: 0.8;">({{ $siguientePendiente->sol_id_seguimiento }})</span>
                </a>
            @endif

            <a href="{{ route('user.historial.index') }}" class="btn btn--ghost">Volver al historial</a>
        </div>
    </div>

    <div class="panel-grid">
        {{-- Datos de la solicitud --}}
        <section class="panel">
            <h2 class="panel__title">Datos de la solicitud</h2>

            <dl class="datos">
                <div class="datos__fila">
                    <dt>Código de seguimiento</dt>
                    <dd style="font-family: ui-monospace, monospace;">{{ $solicitud->sol_id_seguimiento }}</dd>
                </div>

                <div class="datos__fila">
                    <dt>Trámite</dt>
                    <dd>{{ $solicitud->tipoSolicitud->tsi_nombre_tipo }}</dd>
                </div>

                <div class="datos__fila">
                    <dt>Periodo académico</dt>
                    <dd>{{ $solicitud->lapsoAcademico?->lac_id_lapso ?? '—' }}</dd>
                </div>

                <div class="datos__fila">
                    <dt>Prioridad</dt>
                    <dd>{{ $solicitud->sol_prioridad ?? 'sin prioridad asignada' }}</dd>
                </div>

                <div class="datos__fila">
                    <dt>Enviada</dt>
                    <dd>{{ $solicitud->sol_fecha_creacion->format('d/m/Y H:i') }}</dd>
                </div>

                @if ($solicitud->sol_fecha_resolucion)
                    <div class="datos__fila">
                        <dt>Resuelta</dt>
                        <dd>{{ $solicitud->sol_fecha_resolucion->format('d/m/Y H:i') }}</dd>
                    </div>
                @endif

                @php
                    // Quien tiene la solicitud ahora mismo: el ultimo movimiento
                    // del historial. El estudiante debe poder ver en todo momento
                    // quien la esta atendiendo y con que rol.
                    $atendidaPor = $historial
                        ->sortByDesc('hes_fecha_cambio')
                        ->first()?->responsable;
                @endphp

                <div class="datos__fila">
                    <dt>Atendida por</dt>
                    <dd>
                        @if ($atendidaPor)
                            {{ $atendidaPor->nombre_completo }}
                            <span style="color: var(--gray-500);">({{ $atendidaPor->rol_etiqueta }})</span>
                        @else
                            <span style="color: var(--gray-400);">Aún sin asignar</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="panel__hint" style="margin-top: 18px;">
                <p class="panel__hint-titulo">Ficha del estudiante</p>
                <p class="panel__hint-texto">
                    Número de ficha: <strong>{{ $solicitud->usuario?->numero_ficha ?? '—' }}</strong>
                    · Carrera/PNF: {{ $solicitud->usuario?->usu_pnf ?: 'sin asignar' }}
                    · Trayecto: {{ $solicitud->usuario?->usu_trayecto ?: 'sin asignar' }}
                </p>
            </div>

            <div class="panel__hint" style="margin-top: 18px;">
                <p class="panel__hint-titulo">Motivo que enviaste</p>
                <p class="panel__hint-texto">{{ $solicitud->sol_motivo_detallado ?? 'No escribiste un motivo.' }}</p>
            </div>
        </section>

        {{-- Adjuntos y cita de validación física.

             El aviso de arriba resume el estado de los adjuntos: si ya hay
             documentos validados no hace falta volver a cargarlos; si hay
             pendientes o no hay ninguno, se explica qué esperar. La lista y
             la cita se muestran SIEMPRE, para que el estudiante sepa qué
             tiene adjunto y cuándo debe presentarse físicamente. --}}
        <section class="panel">
            <h2 class="panel__title">Adjuntos y cita</h2>

            @php
                $documentos = $solicitud->documentaciones;
                $tieneValidados = $documentos->contains(fn ($d) => $d->doc_estado_validacion === 'validada');
                $tienePendientes = $documentos->isNotEmpty() && ! $tieneValidados;
            @endphp

            @if ($tieneValidados)
                <div class="alert alert--success" style="margin-bottom: 16px;">
                    Tus documentos ya están validados: no necesitas cargarlos de nuevo.
                    Si alguno venció, la oficina de admisiones te citará para revalidarlo.
                </div>
            @elseif ($tienePendientes)
                <div class="alert alert--warning" style="margin-bottom: 16px;">
                    Tienes {{ $documentos->count() }} documento(s) adjunto(s) pendiente(s) de validación.
                    El personal los revisa en el orden en que llegaron; este aviso desaparece al validarse.
                </div>
            @else
                <div class="alert alert--info" style="margin-bottom: 16px;">
                    Esta solicitud todavía no tiene documentos adjuntos. Si el trámite los requiere,
                    la oficina de admisiones te indicará cómo entregarlos.
                </div>
            @endif

            @if ($documentos->isEmpty())
                <p class="estado-vacio" style="padding: 12px 0; text-align: left;">
                    No hay documentos adjuntos a esta solicitud.
                </p>
            @else
                <ul class="lista-simple">
                    @foreach ($documentos as $documento)
                        <li>
                            <span class="lista-simple__titulo">{{ $documento->doc_nombre_original_archivo }}</span>
                            <span class="lista-simple__detalle">
                                {{ strtoupper((string) $documento->doc_formato_archivo) }}
                                &middot;
                                <span class="chip {{ $documento->doc_estado_validacion === 'validada' ? 'chip--ok' : 'chip--neutro' }}">
                                    {{ $documento->doc_estado_validacion }}
                                </span>
                                @if ($documento->doc_fecha_subida)
                                    &middot; {{ Carbon::parse($documento->doc_fecha_subida)->format('d/m/Y') }}
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="panel__hint" style="margin-top: 18px;">
                <p class="panel__hint-titulo">Cita de validación física</p>

                @if ($solicitud->cita)
                    <p class="panel__hint-texto">
                        {{ $solicitud->cita->cit_fecha_hora->format('d/m/Y \a \l\a\s H:i') }}
                        @if ($solicitud->cita->cit_motivo)
                            &middot; {{ $solicitud->cita->cit_motivo }}
                        @endif
                        @if (\Illuminate\Support\Facades\Schema::hasColumn('citas', 'cit_lugar') && $solicitud->cita->cit_lugar)
                            &middot; {{ $solicitud->cita->cit_lugar }}
                        @endif
                        &middot;
                        <span class="chip {{ $solicitud->cita->cit_estado === 'agendada' ? 'chip--neutro' : 'chip--ok' }}">
                            {{ ucfirst((string) $solicitud->cita->cit_estado) }}
                        </span>
                    </p>
                @else
                    <p class="panel__hint-texto">
                        Esta solicitud no tiene cita asignada.
                        @if ($solicitud->tipoSolicitud->tsi_nombre_tipo)
                            Los trámites que requieren validación en persona se agendan desde la oficina de admisiones.
                        @endif
                    </p>
                @endif
            </div>
        </section>
    </div>

    {{-- Recorrido por los estados --}}
    <section class="panel" style="margin-bottom: 24px;">
        <h2 class="panel__title">Recorrido de la solicitud</h2>
        <p class="panel__hint-texto" style="margin-bottom: 20px;">
            Cada cambio de estado queda anotado con la fecha, la persona responsable y el comentario que dejó.
        </p>

        @include('partials.solicitud.timeline', ['solicitud' => $solicitud, 'historial' => $historial])
    </section>

    {{-- Bitácora de cambios de esta solicitud --}}
    <section class="panel">
        <h2 class="panel__title">Cambios registrados</h2>
        <p class="panel__hint-texto" style="margin-bottom: 20px;">
            Cada modificación que se ha hecho sobre esta solicitud, con el valor que tenía antes y el que tiene
            ahora. La bitácora la escribe el sistema sola: no se puede editar ni borrar.
        </p>

        @if ($cambios->isEmpty())
            <p class="estado-vacio">
                Todavía no hay cambios registrados sobre esta solicitud.
            </p>
        @else
            <div class="cambios">
                @foreach ($cambios as $cambio)
                    <article class="cambio">
                        <header class="cambio__cabecera">
                            <span class="chip {{ $cambio->accion_chip }}">{{ $cambio->accion_etiqueta }}</span>
                            <strong>{{ $cambio->hcm_resumen }}</strong>
                            <span class="cambio__meta">
                                {{ $cambio->hcm_fecha->format('d/m/Y H:i') }}
                                &middot; {{ $cambio->autor_nombre }}
                            </span>
                        </header>

                        @include('partials.cambios.diff', ['cambios' => $cambio->diferencias])
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection