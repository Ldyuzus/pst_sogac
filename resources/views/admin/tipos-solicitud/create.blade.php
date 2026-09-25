@extends('layouts.plantilla_admin')

@section('title', 'Nuevo Trámite')

@section('content')
<div class="container main">
    @if($errors->any())
        <div class="alert alert--error">
            <ul style="margin:0; padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <h2 class="card__title">Nuevo Trámite</h2>
        <p class="card__sub">Define el trámite, cuándo estará disponible para los estudiantes, y qué deben entregar.</p>

        <form action="{{ route('admin.tipos-solicitud.store') }}" method="POST" class="form">
            @csrf

            <div class="field">
                <label for="tsi_nombre_tipo">Nombre del trámite</label>
                <input type="text" name="tsi_nombre_tipo" id="tsi_nombre_tipo"
                       placeholder="Ej: Cambio de Carrera"
                       value="{{ old('tsi_nombre_tipo') }}" required />
            </div>

            <div class="field">
                <label for="tsi_descripcion">Descripción</label>
                <textarea name="tsi_descripcion" id="tsi_descripcion"
                          placeholder="Explica brevemente en qué consiste este trámite (opcional)">{{ old('tsi_descripcion') }}</textarea>
            </div>

            <div class="field">
                <label for="tsi_tiempo_estimado_dias">Tiempo estimado de respuesta (días)</label>
                <input type="number" min="0" name="tsi_tiempo_estimado_dias" id="tsi_tiempo_estimado_dias"
                       placeholder="Ej: 7" value="{{ old('tsi_tiempo_estimado_dias') }}" />
            </div>

            <div style="display:flex; align-items:center; gap:8px; margin: 8px 0 16px;">
                <input type="checkbox" name="tsi_requiere_aprobacion_especial" id="tsi_requiere_aprobacion_especial"
                       value="1" {{ old('tsi_requiere_aprobacion_especial') ? 'checked' : '' }} />
                <label for="tsi_requiere_aprobacion_especial" style="margin:0;">Requiere aprobación especial (más allá del admin normal)</label>
            </div>

            {{-- Aquí está lo que pediste: la ventana de fechas en la que el trámite
                 estará disponible para los estudiantes ("de la semana 1 a la semana 4") --}}
            <div style="margin-top:8px;">
                <label style="font-size:0.85rem; font-weight:600; color:var(--gray-900); text-transform:uppercase; letter-spacing:0.5px;">
                    Ventana de disponibilidad
                </label>
                <p style="color:var(--gray-700); font-size:0.88rem; margin-bottom:12px;">
                    Deja los campos vacíos si el trámite estará disponible indefinidamente (sin fecha de cierre).
                </p>
                <div class="form__row">
                    <div class="field">
                        <label for="tsi_fecha_inicio">Disponible desde</label>
                        <input type="date" name="tsi_fecha_inicio" id="tsi_fecha_inicio" value="{{ old('tsi_fecha_inicio') }}" />
                    </div>
                    <div class="field">
                        <label for="tsi_fecha_fin">Disponible hasta</label>
                        <input type="date" name="tsi_fecha_fin" id="tsi_fecha_fin" value="{{ old('tsi_fecha_fin') }}" />
                    </div>
                </div>
            </div>

            <div style="margin-top:8px;">
                <label style="font-size:0.85rem; font-weight:600; color:var(--gray-900); text-transform:uppercase; letter-spacing:0.5px;">
                    Requisitos que debe entregar el estudiante
                </label>
                <p style="color:var(--gray-700); font-size:0.88rem; margin-bottom:12px;">
                    Marca qué documentos se piden para este trámite. Marca "Obligatorio" si es indispensable.
                </p>

                @if($requisitos->isEmpty())
                    <p style="color:var(--gray-400);">
                        Aún no hay requisitos en el catálogo.
                        <a href="{{ route('admin.requisitos.create') }}" target="_blank">Crea uno aquí</a> (se abre en otra pestaña).
                    </p>
                @else
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px;">
                        @foreach ($requisitos as $req)
                            <label style="display:flex; gap:10px; align-items:center; padding:12px 14px; border:1.5px solid var(--gray-200); border-radius:var(--radius-sm); cursor:pointer;">
                                <input type="checkbox" name="requisitos[{{ $req->req_id }}]"
                                       value="1"
                                       {{ old("requisitos.{$req->req_id}") ? 'checked' : '' }} />
                                <span style="flex:1; font-size:0.92rem;">{{ $req->req_nombre_requisito }}</span>
                                <span style="display:flex; gap:6px; align-items:center; font-size:0.8rem; color:var(--gray-700);">
                                    <input type="checkbox" name="obligatorios[{{ $req->req_id }}]" value="1"
                                           {{ old("obligatorios.{$req->req_id}") ? 'checked' : '' }} />
                                    Obligatorio
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Elegir si se crea ya activo (visible para estudiantes) o como borrador --}}
            <div style="display:flex; align-items:center; gap:8px; margin: 20px 0;">
                <input type="checkbox" name="activar_ahora" id="activar_ahora" value="1" {{ old('activar_ahora') ? 'checked' : '' }} />
                <label for="activar_ahora" style="margin:0;">Activar de inmediato (Apenas lo vean los estudiantes lo guarden)</label>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn--primary">Crear Trámite</button>
                <a href="{{ route('admin.tipos-solicitud.index') }}" class="btn btn--dark">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
