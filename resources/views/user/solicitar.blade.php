@extends('layouts.plantilla_user')

@section('content')

<div class="sm:container my-3">
    <div class="container" style="max-width: 640px;">

        <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted" style="font-size:0.9rem;">&larr; Volver a mis trámites</a>

        <h2 class="fw-bold mt-2 mb-1">{{ $tramite->tsi_nombre_tipo }}</h2>
        @if($tramite->tsi_descripcion)
            <p class="text-muted">{{ $tramite->tsi_descripcion }}</p>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Requisitos que el admin le asignó a ESTE trámite específico --}}
        <div class="shadow-sm rounded p-3 mb-3" style="background:#f8f9fa;">
            <strong style="font-size:0.9rem;">Documentos que debes tener listos:</strong>
            @if($tramite->requisitos->isEmpty())
                <p class="text-muted mb-0 mt-1" style="font-size:0.9rem;">Este trámite no pide requisitos adicionales.</p>
            @else
                <ul class="mb-0 mt-2" style="font-size:0.9rem;">
                    @foreach ($tramite->requisitos as $req)
                        <li>
                            {{ $req->req_nombre_requisito }}
                            @if($req->pivot->tsr_es_obligatorio)
                                <span class="badge bg-danger">Obligatorio</span>
                            @else
                                <span class="badge bg-secondary">Opcional</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <form action="{{ route('user.tramites.store', $tramite->tsi_id) }}" method="POST" class="shadow-sm rounded p-3">
            @csrf

            <div class="mb-3">
                <label for="motivo" class="form-label fw-semibold">Cuéntanos qué necesitas</label>
                <textarea name="motivo" id="motivo" rows="5" class="form-control" placeholder="Describe tu solicitud con el mayor detalle posible..." required>{{ old('motivo') }}</textarea>
            </div>

            <button type="submit" class="btn btn-danger rounded-pill px-4">Enviar solicitud</button>
        </form>
    </div>
</div>
@endsection
