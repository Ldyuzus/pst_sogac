@extends('layouts.plantilla_soporte')

@section('title', 'Centro de Soporte')

@section('content')
<div class="container main" style="padding: 2rem 0;">
    @if($errors->any())
        <div class="alert alert--error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <strong>Error:</strong> {{ $errors->first() }}
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- VISTA DEL ADMINISTRADOR --}}
    {{-- ========================================================= --}}
    @if(auth()->user()->usu_rol === 'admin')
    
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
        <div>
            <h2 style="font-size: 2rem; color: var(--black); margin-bottom: 8px;">Panel de Soporte Técnico</h2>
            <p style="color: var(--gray-700); margin: 0;">Bandeja de entrada y gestión de tickets de estudiantes.</p>
        </div>
        
        <!-- Botón para gestionar Preguntas Frecuentes -->
        <div>
            <a href="{{ route('admin.soporte.index') }}" class="btn btn--dark" style="background: var(--black); color: white; text-decoration: none; padding: 10px 18px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: background 0.3s ease;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                Gestionar Preguntas Frecuentes
            </a>
        </div>
    </div>
        <!-- Sección 1: Bandeja de Entrada (Pendientes) -->
        <h3 style="color: var(--red); border-bottom: 2px solid var(--red); padding-bottom: 10px; margin-bottom: 20px;">
            Tickets Nuevos (Pendientes)
        </h3>
        
        @if($hilosPendientes->count() > 0)
            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">ID</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estudiante</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Fecha</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hilosPendientes as $pendiente)
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">#{{ $pendiente->hch_id }}</td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $pendiente->usuario->usu_primer_nombre ?? 'Usuario' }} {{ $pendiente->usuario->usu_primer_apellido ?? '' }}
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">{{ $pendiente->created_at->diffForHumans() }}</td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <form action="{{ route('admin.chat.reclamar', $pendiente->hch_id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn--primary btn--sm" style="background: #28a745; border: none; cursor: pointer;">Reclamar y Atender</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert" style="background: #e3f2fd; color: #004085; padding: 15px; border-radius: 8px; margin-bottom: 40px;">
                ¡Excelente trabajo! No hay tickets pendientes en este momento.
            </div>
        @endif
        <!-- Sección 2: Mis Chats en Curso (Para el Admin) -->
        @if($hilosActivosAdmin->count() > 0)
            <h3 style="color: #17a2b8; border-bottom: 2px solid #17a2b8; padding-bottom: 10px; margin-bottom: 20px; margin-top: 40px;">
                Mis Chats en Curso
            </h3>
            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 40px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">ID</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estudiante</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estado</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hilosActivosAdmin as $activo)
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">#{{ $activo->hch_id }}</td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $activo->usuario->usu_primer_nombre ?? 'Usuario' }} {{ $activo->usuario->usu_primer_apellido ?? '' }}
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                <strong>{{ ucfirst(str_replace('_', ' ', $activo->hch_estado)) }}</strong>
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <a href="{{ route('admin.chat.mostrar', $activo->hch_id) }}" class="btn btn--primary btn--sm" style="background: #17a2b8; border: none; text-decoration: none; color: white; padding: 6px 12px; border-radius: 4px;">Continuar Chat</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif


    {{-- ========================================================= --}}
    {{-- VISTA DEL ESTUDIANTE (USUARIO) --}}
    {{-- ========================================================= --}}
    @else
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="font-size: 2rem; color: var(--black); margin-bottom: 8px;">Mis Consultas</h2>
                <p style="color: var(--gray-700);">Ponte en contacto con el soporte técnico.</p>
            </div>
            
            <!-- Botón Inteligente: Crear o ir al activo -->
            <div>
                @if($hiloActivo)
                    <a href="{{ route('user.chat.mostrar', $hiloActivo->hch_id) }}" class="btn btn--primary" style="background: #17a2b8; text-decoration: none; display: inline-block;">
                        Ir a mi consulta actual
                    </a>
                @else
                    <button type="button" onclick="document.getElementById('modalNuevoTicket').style.display='block'" class="btn btn--primary" style="background: var(--red); border: none; cursor: pointer;">
                        + Crear Nuevo Ticket
                    </button>
                @endif
            </div>
        </div>

        @if($hiloActivo)
            <div class="alert" style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 40px;">
                <strong>Aviso:</strong> Tienes un ticket en curso (Estado: {{ ucfirst($hiloActivo->hch_estado) }}). Resuélvelo antes de abrir uno nuevo.
            </div>
        @endif

    @endif


    {{-- ========================================================= --}}
    {{-- HISTORIAL DE TICKETS CERRADOS (COMPARTIDO) --}}
    {{-- ========================================================= --}}
    
    @if($hilos->count() > 0)
        <!-- Acordeón del Historial -->
        <details class="acordeon-historial" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-top: 40px; margin-bottom: 20px;">
            
            <summary style="padding: 15px 20px; font-weight: 600; font-size: 1.2rem; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; border-bottom: 1px solid #dee2e6; color: var(--gray-700);">
                Historial de Tickets Resueltos
                <svg class="icono-flecha-historial" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </summary>
            
            <div style="padding: 20px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">ID</th>
                            @if(auth()->user()->usu_rol === 'admin')
                                <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Tema / Etiqueta</th>
                                <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Estudiante</th>
                            @endif
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Atendido por</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">Fecha Cierre</th>
                            <th style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hilos as $cerrado)
                        <tr>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">#{{ $cerrado->hch_id }}</td>
                            
                            @if(auth()->user()->usu_rol === 'admin')
                                <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                    <span style="background: #e2e3e5; padding: 3px 8px; border-radius: 12px; font-size: 0.85rem;">
                                        {{ $cerrado->hch_etiqueta_tema ?? 'Sin etiqueta' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                    {{ $cerrado->usuario->usu_primer_nombre }}
                                </td>
                            @endif
                            
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $cerrado->admin->usu_primer_nombre ?? 'Sistema' }}
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6;">
                                {{ $cerrado->updated_at->format('d/m/Y') }}
                            </td>
                            <td style="padding: 12px 15px; border-bottom: 1px solid #dee2e6; text-align: center;">
                                <a href="{{ route(auth()->user()->usu_rol === 'admin' ? 'admin.chat.mostrar' : 'user.chat.mostrar', $cerrado->hch_id) }}" style="color: #007bff; text-decoration: none; font-weight: bold;">Ver Chat</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <!-- Paginación nativa de Laravel -->
                <div style="margin-top: 20px;">
                    {{ $hilos->links() }}
                </div>
            </div>
        </details>

        <!-- Estilos exclusivos para la animación de este acordeón -->
        <style>
            .acordeon-historial > summary::-webkit-details-marker {
                display: none;
            }
            .icono-flecha-historial {
                transition: transform 0.3s ease-in-out;
            }
            .acordeon-historial[open] .icono-flecha-historial {
                transform: rotate(180deg);
            }
        </style>
    @endif

</div>

<!-- Modal Nuevo Ticket -->
<div id="modalNuevoTicket" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000;">
    <div class="card modal-content" style="position: relative; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 500px; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; color: var(--black);">Nuevo Ticket de Soporte</h3>
        <p style="color: var(--gray-700); font-size: 0.9rem; margin-bottom: 20px;">Describe tu problema con detalle para abrir el chat con un administrador.</p>
        
        <!-- Apunta a la misma ruta de iniciar chat, pero con el formulario enriquecido -->
        <form action="{{ route('user.chat.iniciar') }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 15px;">
            @csrf
            <div>
                <label style="font-size: 0.85rem; font-weight: bold; color: var(--black);">Mensaje:</label>
                <textarea name="mch_cuerpo" rows="4" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; box-sizing: border-box; margin-top: 5px;" placeholder="Ej: Tengo un problema con el trámite..."></textarea>
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: bold; color: var(--black);">Adjuntar evidencia (Opcional):</label><br>
                <input type="file" name="imagen" accept="image/*" style="margin-top: 5px;">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                <button type="button" onclick="document.getElementById('modalNuevoTicket').style.display='none'" class="btn" style="background: #e2e3e5; color: #333; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn btn--primary" style="background: var(--red); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">Enviar y Crear Ticket</button>
            </div>
        </form>
    </div>
</div>
@endsection