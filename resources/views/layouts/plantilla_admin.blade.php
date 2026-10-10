<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Solicítalo — @yield('title', 'Panel Admin')</title>

  {{-- El JS del panel resuelve solicitudes por fetch y necesita el token para
       el POST con CSRF. Sin esta etiqueta la petición sale con 419. --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="stylesheet" href="{{ asset('style_admin.css') }}" />

  <style>
    /* Quien esta dentro y el botón salir viven en la barra roja. */
    .topbar__usuario {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-left: auto;
      padding-left: 16px;
    }
    .topbar__nombre {
      color: var(--white);
      font-weight: 600;
      font-size: 0.92rem;
      white-space: nowrap;
    }
    .topbar__rol {
      background: rgba(255, 255, 255, 0.2);
      color: var(--white);
      border-radius: 4px;
      padding: 3px 9px;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      white-space: nowrap;
    }
    .topbar__salir {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.45);
      border-radius: 8px;
      color: var(--white);
      font-family: inherit;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 7px 14px;
      cursor: pointer;
      white-space: nowrap;
      transition: background 0.2s ease;
    }
    .topbar__salir:hover { background: rgba(255, 255, 255, 0.16); }

    /* Botón hamburguesa: las tres rayas se convierten en una X.
       Va pegado a la izquierda, antes de la marca. */
    .menu-btn {
      display: flex;
      margin: 0 16px 0 0;
      width: 42px;
      height: 42px;
      padding: 0 10px;
      flex-direction: column;
      justify-content: center;
      gap: 5px;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 8px;
      cursor: pointer;
      flex-shrink: 0;
    }
    .menu-btn__linea {
      display: block;
      height: 2px;
      width: 100%;
      background: var(--white);
      border-radius: 2px;
      transition: transform 0.25s ease, opacity 0.25s ease;
    }
    html.menu-abierto .menu-btn__linea:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    html.menu-abierto .menu-btn__linea:nth-child(2) { opacity: 0; }
    html.menu-abierto .menu-btn__linea:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    /* La cabecera se mantiene por encima del panel lateral. */
    .cabecera {
      position: sticky;
      top: 0;
      z-index: 950;
    }

    /* Con el boton a la izquierda, la marca debe quedarse a su lado y no al
       extremo opuesto: space-between las separaria. El menu lateral es
       position:fixed, asi que no ocupa sitio en la fila. */
    .topbar__inner { justify-content: flex-start; }

    /* Panel lateral: entra desde la izquierda con los mismos colores de la
       barra roja, sin ocupar toda la pagina. */
    .nav {
      position: fixed;
      top: var(--alto-cabecera, 110px);
      bottom: 0;
      left: 0;
      width: 280px;
      max-width: 84vw;
      display: flex;
      flex-direction: column;
      /* Sin esto el excedente se repartia en columnas nuevas hacia la
         derecha: column + wrap envuelve en horizontal, no hace scroll. */
      flex-wrap: nowrap;
      align-items: stretch;
      gap: 2px;
      padding: 16px 12px;
      margin: 0;
      background: var(--gradient-red);
      box-shadow: 8px 0 26px rgba(0, 0, 0, 0.3);
      overflow-y: auto;
      transform: translateX(-100%);
      transition: transform 0.28s ease-in-out;
      z-index: 900;
    }
    html.menu-abierto .nav { transform: translateX(0); }

    .nav__group {
      flex-direction: column;
      align-items: stretch;
      gap: 0;
    }
    .nav__sep { display: none; }
    .nav__sitio { margin: 6px 10px; }

    /* El contenido se aparta para que el panel no lo tape. */
    .contenido { transition: padding-left 0.28s ease-in-out; }
    html.menu-abierto .contenido { padding-left: 304px; }

    /* En pantallas estrechas el panel se superpone en vez de empujar. */
    @media (max-width: 860px) {
      html.menu-abierto .contenido { padding-left: 24px; }
    }
  </style>
</head>
<body>
  @php $usuario = Auth::user(); @endphp

  <div class="cabecera" id="cabecera-global">
    @include('partials.cabecera')

    <header class="topbar">
      <div class="container topbar__inner">
        <button type="button" id="menu-btn" class="menu-btn" aria-controls="nav-principal" aria-expanded="false" aria-label="Abrir el menú">
          <span class="menu-btn__linea"></span>
          <span class="menu-btn__linea"></span>
          <span class="menu-btn__linea"></span>
        </button>

        <a href="{{ route('admin.solicitudes.index') }}" class="brand" style="text-decoration:none;">
          <span class="brand__mark">S</span>
          <span class="brand__name">Solicítalo</span>
        </a>

        {{-- Quién esta dentro y como salir: vive en la barra, no en el menu --}}
        <div class="topbar__usuario">
          <span class="topbar__nombre">
            {{ $usuario->usu_primer_nombre }} {{ $usuario->usu_primer_apellido }}
          </span>
          <span class="topbar__rol">{{ $usuario->usu_rol }}</span>

          <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="topbar__salir">Salir</button>
          </form>
        </div>

{{-- Un solo menu: los modulos con sus funciones anidadas, filtradas por rol --}}
        <nav class="nav" id="nav-principal">
          @include('partials.menu-modulos', ['ambito' => 'admin'])

          {{-- Vuelta al homepage, que es la pagina principal del sitio --}}
          <div class="nav__grupo">
            <a href="{{ route('portada') }}" class="nav__sitio">
              <i class="bi bi-house-door"></i> Ir al sitio
            </a>
          </div>
        </nav>
      </div>
    </header>
  </div>

  <main class="main container contenido">
    @yield("content")
  </main>

  <footer class="footer">
    <div class="container">
      <p>&copy; {{ now()->year }} Sistema de Solicitudes Estudiantiles — UPTP "Juan de Jesús Montilla"</p>
    </div>
  </footer>

  @stack('scripts')
  @include('partials.modal')

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const boton = document.getElementById('menu-btn');
      const nav = document.getElementById('nav-principal');
      const cabecera = document.getElementById('cabecera-global');
      if (!boton || !nav) return;

      // El panel arranca justo por debajo de la cabecera, que cambia de alto
      // segun el ancho de la pantalla.
      function medirCabecera() {
        if (cabecera) {
          document.documentElement.style.setProperty('--alto-cabecera', cabecera.offsetHeight + 'px');
        }
      }

      function alternarMenu() {
        const abierto = document.documentElement.classList.toggle('menu-abierto');
        boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        boton.setAttribute('aria-label', abierto ? 'Cerrar el menú' : 'Abrir el menú');
      }

      medirCabecera();
      window.addEventListener('resize', medirCabecera);

      boton.addEventListener('click', function (evento) {
        evento.stopPropagation();
        alternarMenu();
      });

      // Al navegar a otra pagina el menu debe empezar cerrado.
      nav.addEventListener('click', function (evento) {
        if (evento.target.closest('a')) {
          alternarMenu();
        }
      });

      // Un clic fuera del panel lo cierra.
      document.addEventListener('click', function (evento) {
        if (!document.documentElement.classList.contains('menu-abierto')) return;
        if (nav.contains(evento.target) || boton.contains(evento.target)) return;
        alternarMenu();
      });

      // Escape tambien lo cierra.
      document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && document.documentElement.classList.contains('menu-abierto')) {
          alternarMenu();
        }
      });

      // Modulos plegables: las funciones de un modulo solo se ven al abrirlo.
      // El modulo de la pagina actual ya llega desplegado desde el servidor.
      document.querySelectorAll('[data-modulo-btn]').forEach(function (titulo) {
        titulo.addEventListener('click', function () {
          const panel = document.getElementById(titulo.getAttribute('aria-controls'));
          if (!panel) {
            return;
          }

          const abierto = titulo.getAttribute('aria-expanded') === 'true';
          titulo.setAttribute('aria-expanded', abierto ? 'false' : 'true');
          panel.hidden = abierto;
        });
      });
    });
  </script>
</body>
</html>
