@extends('layouts.plantilla_admin')

@section('title', 'Panel de Administración')

@section('content')
<div class="container main">
    <section class="hero" style="background: linear-gradient(135deg, #111 0%, #222 100%); border-left: 6px solid var(--red);">
      <h1>Panel de Control Administrativo</h1>
      <p>Revisa la documentación adjunta, aprueba o rechaza los trámites académicos en tiempo real.</p>
    </section>

    {{-- Manejo de mensajes de éxito enviados desde el Controlador --}}
    @if(session('success'))
        <div class="alert alert--success">{{ session('success') }}</div>
    @endif

    <div class="stats">
      {{-- Cada tarjeta es un link que filtra directamente por ese estado. El "Total" limpia el filtro de estado. --}}
      <a href="{{ route('admin.dashboard') }}" class="stat" style="text-decoration:none; color:inherit; {{ !request('estado') ? 'outline: 2px solid var(--red);' : '' }}">
        <div class="stat__label">Total Solicitudes</div><div class="stat__value">{{ $stats['total'] }}</div>
      </a>
      <a href="{{ route('admin.dashboard', ['estado' => 'pendiente']) }}" class="stat" style="text-decoration:none; color:inherit; border-left-color: #ffd6d6; {{ request('estado') === 'pendiente' ? 'outline: 2px solid var(--red);' : '' }}">
        <div class="stat__label">Pendientes</div><div class="stat__value">{{ $stats['pendiente'] }}</div>
      </a>
      <a href="{{ route('admin.dashboard', ['estado' => 'aprobada']) }}" class="stat" style="text-decoration:none; color:inherit; border-left-color: #d6f5e3; {{ request('estado') === 'aprobada' ? 'outline: 2px solid var(--red);' : '' }}">
        <div class="stat__label">Aprobadas</div><div class="stat__value">{{ $stats['aprobada'] }}</div>
      </a>
      <a href="{{ route('admin.dashboard', ['estado' => 'rechazada']) }}" class="stat" style="text-decoration:none; color:inherit; border-left-color: var(--red); {{ request('estado') === 'rechazada' ? 'outline: 2px solid var(--red);' : '' }}">
        <div class="stat__label">Rechazadas</div><div class="stat__value">{{ $stats['rechazada'] }}</div>
      </a>
    </div>

    <div class="card">
      <h2 class="card__title">Listado de Solicitudes Estudiantiles</h2>
      <p class="card__sub">Administra las peticiones ingresadas al sistema por los estudiantes.</p>

      {{-- Estilos propios de esta barra: tabs tipo píldora y badges con punto de color,
           inspirados en paneles tipo Stripe/Vercel. Se quedan aquí (no en style_admin.css)
           para no afectar otras páginas del proyecto. --}}
      <style>
        .buscador-caja { position: relative; flex: 1; min-width: 240px; }
        .buscador-caja input {
          width: 100%; padding: 10px 14px 10px 38px; border-radius: 10px;
          border: 1.5px solid var(--gray-200); font-size: 0.95rem;
        }
        .buscador-caja input:focus { border-color: var(--red); outline: none; }
        .buscador-caja i {
          position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
          color: var(--gray-400); font-size: 15px;
        }
        .tabs-estado { display: flex; background: var(--gray-100); border-radius: 10px; padding: 4px; gap: 2px; }
        .tab-pill {
          text-decoration: none; padding: 7px 16px; border-radius: 8px; font-size: 0.85rem;
          font-weight: 600; color: var(--gray-700); transition: all 0.15s ease;
        }
        .tab-pill.activa { background: white; color: var(--black); box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
        .badge-punto {
          display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px;
          border-radius: 999px; font-size: 0.78rem; font-weight: 600;
        }
        .badge-punto__dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
        .badge-punto--pendiente { background: #fff3d6; color: #8a5a00; }
        .badge-punto--pendiente .badge-punto__dot { background: #d69a00; }
        .badge-punto--aprobada { background: #d6f5e3; color: #14683a; }
        .badge-punto--aprobada .badge-punto__dot { background: #22a35a; }
        .badge-punto--rechazada { background: #fdd6d6; color: var(--red-dark); }
        .badge-punto--rechazada .badge-punto__dot { background: var(--red); }

        /* Mismo look que el buscador, para el select "Todos los trámites" */
        #tipo_solicitud {
          padding: 10px 14px; border-radius: 10px; border: 1.5px solid var(--gray-200); font-size: 0.95rem;
        }
        #tipo_solicitud:focus { border-color: var(--red); outline: none; }

        /* Modal de detalle con barra de color arriba según el estado */
        .modal-overlay {
          position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000;
          display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-caja {
          background: white; border-radius: 12px; width: 100%; max-width: 540px;
          max-height: 85vh; overflow-y: auto; position: relative;
        }
        .modal-barra { height: 6px; border-radius: 12px 12px 0 0; }
        .modal-barra--pendiente { background: #d69a00; }
        .modal-barra--aprobada { background: #22a35a; }
        .modal-barra--rechazada { background: var(--red); }
        .modal-cuerpo { padding: 24px 28px 28px; }
        .modal-cerrar {
          position: absolute; top: 16px; right: 16px; background: var(--gray-100); border: none;
          width: 30px; height: 30px; border-radius: 50%; font-size: 18px; cursor: pointer; color: var(--gray-700);
        }
        .modal-fila { display: flex; flex-direction: column; gap: 2px; margin-bottom: 14px; }
        .modal-fila strong { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-400); }
        .modal-fila span { font-size: 0.95rem; color: var(--black); }
        .modal-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 22px; gap: 10px; }
      </style>

      {{-- Barra de búsqueda. Ya NO se envía como formulario tradicional:
           el JS de más abajo intercepta cada acción (escribir, cambiar el select,
           hacer clic en una pestaña o en la paginación) y pide los datos con fetch(),
           sin recargar la página. --}}
      <form method="GET" action="{{ route('admin.dashboard') }}" id="form-busqueda" style="margin-bottom: 24px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;" onsubmit="return false;">
        <div class="buscador-caja">
          <i class="ti ti-search" aria-hidden="true"></i>
          <input
            type="text"
            id="busqueda"
            name="busqueda"
            placeholder="Buscar por cédula o nombre de estudiante..."
            value="{{ request('busqueda') }}"
          >
        </div>

        <select id="tipo_solicitud" name="tipo_solicitud" style="min-width: 180px;">
          <option value="">Todos los trámites</option>
          @foreach ($tiposSolicitud as $tipo)
            <option value="{{ $tipo->tsi_id }}" @selected(request('tipo_solicitud') == $tipo->tsi_id)>
              {{ $tipo->tsi_nombre_tipo }}
            </option>
          @endforeach
        </select>

        <div class="tabs-estado">
          @php
            $estadoActivo = request('estado');
            $pestanasEstado = ['' => 'Todas', 'pendiente' => 'Pendientes', 'aprobada' => 'Aprobadas', 'rechazada' => 'Rechazadas'];
          @endphp
          @foreach ($pestanasEstado as $valor => $etiqueta)
            <a
              href="#"
              class="tab-pill tab-estado {{ $estadoActivo == $valor ? 'activa' : '' }}"
              data-estado="{{ $valor }}"
            >{{ $etiqueta }}</a>
          @endforeach
        </div>
      </form>

      {{-- Modal de detalle. Vive FUERA de #resultados-wrapper para que no se borre
           cada vez que se reemplaza la tabla al buscar/filtrar. --}}
      <div id="modal-detalle" class="modal-overlay" style="display:none;">
        <div class="modal-caja">
          <div id="modal-barra" class="modal-barra"></div>
          <div class="modal-cuerpo">
            <button type="button" id="cerrar-modal" class="modal-cerrar" aria-label="Cerrar">&times;</button>
            <h3 id="modal-titulo" style="margin: 0 0 2px; font-size: 1.2rem;"></h3>
            <p id="modal-cedula" style="color: var(--gray-700); font-size: 0.85rem; margin: 0 0 20px;"></p>

            <div class="modal-fila"><strong>Tipo de trámite</strong><span id="modal-tipo"></span></div>
            <div class="modal-fila"><strong>Fecha de la solicitud</strong><span id="modal-fecha"></span></div>
            <div class="modal-fila"><strong>Estado actual</strong><span id="modal-estado"></span></div>
            <div class="modal-fila"><strong>Descripción completa</strong><span id="modal-descripcion" style="white-space: pre-wrap;"></span></div>

            <div id="modal-acciones" style="margin-top: 16px; display: flex; gap: 10px;"></div>

            <div class="modal-nav">
              <button type="button" id="modal-anterior" class="btn btn--sm" style="background: var(--gray-200); color: var(--black);">&laquo; Anterior</button>
              <span id="modal-contador" style="font-size: 0.8rem; color: var(--gray-400);"></span>
              <button type="button" id="modal-siguiente" class="btn btn--sm" style="background: var(--gray-200); color: var(--black);">Siguiente &raquo;</button>
            </div>
          </div>
        </div>
      </div>

      {{-- Este div es lo único que se reemplaza cuando se busca/filtra/pagina --}}
      <div id="resultados-wrapper">
        @include('admin.partials.resultados')
      </div>

      <script>
        (function () {
          const inputBusqueda = document.getElementById('busqueda');
          const selectTipo = document.getElementById('tipo_solicitud');
          const wrapper = document.getElementById('resultados-wrapper');
          const tabs = document.querySelectorAll('.tab-estado');
          const urlBase = "{{ route('admin.dashboard') }}";

          // Estado actual de los filtros (arranca con lo que ya viene en la URL)
          let estadoActual = new URLSearchParams(window.location.search).get('estado') || '';

          // Pide al servidor solo el pedazo de la tabla (fetch = "pedido en segundo plano",
          // no navega a otra página, por eso no hay recarga ni parpadeo).
          function buscar(url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(function (respuesta) { return respuesta.text(); })
              .then(function (html) {
                wrapper.innerHTML = html;
                history.pushState(null, '', url); // Actualiza la URL sin recargar (para poder compartir el link o recargar F5 y mantener el filtro)
              });
          }

          function construirUrlYBuscar() {
            const params = new URLSearchParams();
            if (inputBusqueda.value) params.set('busqueda', inputBusqueda.value);
            if (selectTipo.value) params.set('tipo_solicitud', selectTipo.value);
            if (estadoActual) params.set('estado', estadoActual);
            const query = params.toString();
            buscar(urlBase + (query ? '?' + query : ''));
          }

          // Búsqueda en vivo: espera 400ms después de que el usuario deja de escribir
          let temporizador;
          inputBusqueda.addEventListener('input', function () {
            clearTimeout(temporizador);
            temporizador = setTimeout(construirUrlYBuscar, 400);
          });

          selectTipo.addEventListener('change', construirUrlYBuscar);

          tabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
              e.preventDefault();
              estadoActual = this.dataset.estado;
              tabs.forEach(function (t) { t.classList.remove('activa'); });
              this.classList.add('activa');
              construirUrlYBuscar();
            });
          });

          // Los links de paginación se recrean cada vez que se reemplaza el HTML,
          // así que "escuchamos" los clics en el contenedor padre (delegación de eventos)
          // en vez de engancharlos uno por uno.
          wrapper.addEventListener('click', function (e) {
            const link = e.target.closest('a.pagina-link');
            if (link) {
              e.preventDefault();
              buscar(link.getAttribute('href'));
              return;
            }

            const boton = e.target.closest('.ver-detalle');
            if (boton) {
              const filas = Array.from(wrapper.querySelectorAll('.ver-detalle'));
              const indice = filas.indexOf(boton);
              abrirModal(filas, indice);
            }
          });

          // --- Modal de detalle, con Anterior/Siguiente entre las solicitudes de la página actual ---
          const modal = document.getElementById('modal-detalle');
          const modalBarra = document.getElementById('modal-barra');
          const modalTitulo = document.getElementById('modal-titulo');
          const modalCedula = document.getElementById('modal-cedula');
          const modalTipo = document.getElementById('modal-tipo');
          const modalFecha = document.getElementById('modal-fecha');
          const modalEstado = document.getElementById('modal-estado');
          const modalDescripcion = document.getElementById('modal-descripcion');
          const modalAcciones = document.getElementById('modal-acciones');
          const modalContador = document.getElementById('modal-contador');
          const btnAnterior = document.getElementById('modal-anterior');
          const btnSiguiente = document.getElementById('modal-siguiente');

          let listaActual = [];
          let indiceActual = 0;

          function abrirModal(filas, indice) {
            listaActual = filas;
            indiceActual = indice;
            pintarModal();
            modal.style.display = 'flex';
          }

          function pintarModal() {
            const data = listaActual[indiceActual].dataset;

            modalBarra.className = 'modal-barra modal-barra--' + data.estado;
            modalTitulo.textContent = data.nombre;
            modalCedula.textContent = 'C.I: ' + data.cedula;
            modalTipo.textContent = data.tipo;
            modalFecha.textContent = data.fecha;
            modalEstado.textContent = data.estado.charAt(0).toUpperCase() + data.estado.slice(1);
            modalDescripcion.textContent = data.descripcion;
            modalContador.textContent = (indiceActual + 1) + ' de ' + listaActual.length + ' (esta página)';

            if (data.estado === 'pendiente') {
              modalAcciones.innerHTML =
                '<button type="button" id="modal-btn-aprobar" class="btn" style="background:#22a35a; color:white; flex:1;">Aprobar</button>' +
                '<button type="button" id="modal-btn-rechazar" class="btn btn--danger" style="flex:1;">Rechazar</button>';

              document.getElementById('modal-btn-aprobar').addEventListener('click', function () {
                resolverDesdeModal(data.aprobarUrl, 'aprobada');
              });
              document.getElementById('modal-btn-rechazar').addEventListener('click', function () {
                if (confirm('¿Seguro que deseas rechazar esta solicitud?')) {
                  resolverDesdeModal(data.rechazarUrl, 'rechazada');
                }
              });
            } else {
              modalAcciones.innerHTML = '<p style="color:var(--gray-400); font-size:0.85rem; font-style:italic; margin:0;">Esta solicitud ya fue resuelta.</p>';
            }

            btnAnterior.disabled = indiceActual === 0;
            btnAnterior.style.opacity = btnAnterior.disabled ? '0.4' : '1';
            btnSiguiente.disabled = indiceActual === listaActual.length - 1;
            btnSiguiente.style.opacity = btnSiguiente.disabled ? '0.4' : '1';
          }

          function cerrarModal() {
            modal.style.display = 'none';
          }

          // Aprueba/rechaza SIN recargar la página ni cerrar el modal.
          // Actualiza el estado en el botón de la fila (fuente de la verdad)
          // y vuelve a pintar el modal con el color/estado nuevo.
          function resolverDesdeModal(url, nuevoEstado) {
            const botonesAccion = modalAcciones.querySelectorAll('button');
            botonesAccion.forEach(function (b) { b.disabled = true; b.style.opacity = '0.6'; });

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(function (respuesta) { return respuesta.json(); })
              .then(function () {
                // Actualiza el data-estado del botón "Detalle" de esa fila en la tabla de fondo
                listaActual[indiceActual].dataset.estado = nuevoEstado;
                // Repinta el modal: cambia la barra de color y quita Aprobar/Rechazar
                pintarModal();
                // Actualiza también la fila visualmente (badge + columna de acciones)
                actualizarFilaEnTabla(listaActual[indiceActual]);
              })
              .catch(function () {
                alert('No se pudo actualizar la solicitud. Intenta de nuevo.');
                botonesAccion.forEach(function (b) { b.disabled = false; b.style.opacity = '1'; });
              });
          }

          // Actualiza el badge de estado y la columna de Acciones de la fila en la tabla,
          // sin volver a pedirle nada al servidor (ya sabemos el resultado).
          function actualizarFilaEnTabla(botonDetalle) {
            const fila = botonDetalle.closest('tr');
            if (!fila) return;
            const nuevoEstado = botonDetalle.dataset.estado;
            const etiqueta = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);

            const badge = fila.querySelector('.badge-punto');
            if (badge) {
              badge.className = 'badge-punto badge-punto--' + nuevoEstado;
              badge.innerHTML = '<span class="badge-punto__dot"></span>' + etiqueta;
            }

            // Como ya no está pendiente, quitamos los botones Aprobar/Rechazar de la fila
            const celdaAcciones = fila.querySelector('td:last-child');
            if (celdaAcciones) {
              const aprobar = celdaAcciones.querySelector('a[style*="22a35a"]');
              const rechazar = celdaAcciones.querySelector('a.btn--danger');
              if (aprobar) aprobar.remove();
              if (rechazar) rechazar.remove();
            }
          }

          btnAnterior.addEventListener('click', function () {
            if (indiceActual > 0) { indiceActual--; pintarModal(); }
          });
          btnSiguiente.addEventListener('click', function () {
            if (indiceActual < listaActual.length - 1) { indiceActual++; pintarModal(); }
          });

          document.getElementById('cerrar-modal').addEventListener('click', cerrarModal);
          modal.addEventListener('click', function (e) { if (e.target === modal) cerrarModal(); });
          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarModal();
            if (modal.style.display === 'flex' && e.key === 'ArrowRight') btnSiguiente.click();
            if (modal.style.display === 'flex' && e.key === 'ArrowLeft') btnAnterior.click();
          });
        })();
      </script>
    </div>
</div>
@endsection