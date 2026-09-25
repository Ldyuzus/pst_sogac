@extends('layouts.plantilla_user')

@section('content')

<div class="sm:container my-3">
    <h2 class="text-center fw-bold">Panel de Solicitudes</h2>
    
    <nav class="d-flex justify-content-center p-3 btn-group">
        <a type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm border border-3" id="solicitudes-tab" data-bs-toggle="pill" href="#solicitudes" aria-controls="solicitudes" aria-selected="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#ffffff" class="icon icon-tabler icons-tabler-filled icon-tabler-message">
        	    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
        	    <path d="M18 3a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-4.724l-4.762 2.857a1 1 0 0 1 -1.508 -.743l-.006 -.114v-2h-1a4 4 0 0 1 -3.995 -3.8l-.005 -.2v-8a4 4 0 0 1 4 -4zm-4 9h-6a1 1 0 0 0 0 2h6a1 1 0 0 0 0 -2m2 -4h-8a1 1 0 1 0 0 2h8a1 1 0 0 0 0 -2" />
            </svg>
            Chat
        </a>
        
        <a type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm border border-3" id="citas-tab" data-bs-toggle="pill" href="#citas" aria-controls="citas" aria-selected="false">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#ffffff" class="icon icon-tabler icons-tabler-filled icon-tabler-calendar-week">
	            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
	            <path d="M16 2c.183 0 .355 .05 .502 .135l.033 .02c.28 .177 .465 .49 .465 .845v1h1a3 3 0 0 1 2.995 2.824l.005 .176v12a3 3 0 0 1 -2.824 2.995l-.176 .005h-12a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-12a3 3 0 0 1 2.824 -2.995l.176 -.005h1v-1a1 1 0 0 1 .514 -.874l.093 -.046l.066 -.025l.1 -.029l.107 -.019l.12 -.007q .083 0 .161 .013l.122 .029l.04 .012l.06 .023c.328 .135 .568 .44 .61 .806l.007 .117v1h6v-1a1 1 0 0 1 1 -1m3 7h-14v9.625c0 .705 .386 1.286 .883 1.366l.117 .009h12c.513 0 .936 -.53 .993 -1.215l.007 -.16z" />
	            <path d="M9.015 13a1 1 0 0 1 -1 1a1.001 1.001 0 1 1 -.005 -2c.557 0 1.005 .448 1.005 1" />
	            <path d="M13.015 13a1 1 0 0 1 -1 1a1.001 1.001 0 1 1 -.005 -2c.557 0 1.005 .448 1.005 1" />
	            <path d="M17.02 13a1 1 0 0 1 -1 1a1.001 1.001 0 1 1 -.005 -2c.557 0 1.005 .448 1.005 1" />
	            <path d="M12.02 15a1 1 0 0 1 0 2a1.001 1.001 0 1 1 -.005 -2z" />
	            <path d="M9.015 16a1 1 0 0 1 -1 1a1.001 1.001 0 1 1 -.005 -2c.557 0 1.005 .448 1.005 1" />
            </svg>
            Calendario
        </a>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive rounded shadow-sm">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Trámites de Control de estudio</th>
                        <th>Disponible hasta</th>
                        <th style="width:140px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tramites as $tramite)
                        <tr>
                            <td>
                                <strong>{{ $tramite->tsi_nombre_tipo }}</strong>
                                @if($tramite->tsi_descripcion)
                                    <div class="text-muted" style="font-size:0.85rem;">{{ $tramite->tsi_descripcion }}</div>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:0.85rem;">
                                {{ $tramite->tsi_fecha_fin?->format('d/m/Y') ?? 'Sin fecha límite' }}
                            </td>
                            <td>
                                <a href="{{ route('user.tramites.solicitar', $tramite->tsi_id) }}" class="btn btn-danger btn-sm rounded-pill">
                                    Solicitar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                No hay trámites disponibles por ahora. Vuelve a revisar más adelante.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection