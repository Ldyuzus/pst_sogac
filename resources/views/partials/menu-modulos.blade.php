{{--
    Modulos del menu lateral, con sus funciones anidadas.

    El panel lateral es lo unico que navega: cada modulo muestra debajo las
    acciones que ofrece, para no obligar a entrar al listado a buscar el boton.

    $ambito decide que grupo de modulos se dibuja: 'admin' para el panel del
    personal y 'estudiante' para el portal del estudiante. Sin esto, el
    administrador veria tambien los modulos del estudiante al entrar a una
    pagina de su portal.

    Todo se declara aqui una sola vez, asi que anadir una funcion a un modulo es
    agregar una linea y no editar las dos plantillas.

    @param string $ambito 'admin' o 'estudiante'
--}}
@php
    use App\Models\ChatSoporte\HiloChat;

    $ambito = $ambito ?? 'admin';

    $usuario = Auth::user();
    $esAdminOAnalista = $usuario?->esAdministrador() || $usuario?->esAnalista();

    // Pendientes del chat, para el contador del menu. El personal tiene
    // delante las consultas que NADIE ha reclamado todavia; el estudiante, su
    // propia consulta en curso. Son dos preguntas distintas y por eso dos
    // consultas distintas, no un unico 'where' con un rol adentro.
    $pendientesChat = $usuario
        ? ($usuario->esAdministrativo()
            ? HiloChat::where('hch_estado', 'pendiente')->count()
            : HiloChat::where('hch_id_usuario', $usuario->usu_id)
                ->whereIn('hch_estado', ['pendiente', 'activo', 'pendiente_cierre'])
                ->count())
        : 0;

    $modulos = [
        // --- Personal administrativo ---
        [
            'ambito' => 'admin',
            'titulo' => 'Solicitudes',
            'visible' => $usuario?->esAdministrativo(),
            'funciones' => [
                ['ruta' => 'admin.solicitudes.index', 'texto' => 'Cola de solicitudes', 'patrones' => ['admin.solicitudes.*']],
                ['ruta' => 'admin.dashboard', 'texto' => 'Todas las solicitudes', 'patrones' => ['admin.dashboard'], 'soloSinEstado' => true, 'visible' => $esAdminOAnalista],
                // Filtro rápido: abre el panel con la pestaña "Pendientes" ya
                // activa. El estado se pasa por la URL (?estado=pendiente) y el
                // botón queda resaltado solo cuando ese filtro está puesto,
                // para no confundirlo con "Todas las solicitudes".
                [
                    'ruta' => 'admin.dashboard',
                    'query' => ['estado' => 'pendiente'],
                    'texto' => 'Solicitudes pendientes',
                    'patrones' => ['admin.dashboard'],
                    'visible' => $esAdminOAnalista,
                ],
                [
                    'ruta' => 'admin.tratadas.index',
                    'texto' => 'Solicitudes tratadas',
                    'patrones' => ['admin.tratadas.*'],
                    'visible' => $esAdminOAnalista,
                ],
            ],
        ],
        [
            'ambito' => 'admin',
            'titulo' => 'Trámites',
            'visible' => $esAdminOAnalista,
            'funciones' => [
                [
                    'ruta' => 'admin.tipos-solicitud.index',
                    'texto' => 'Listar trámites',
                    // Editar, actualizar, borrar y activar/desactivar son
                    // operaciones sobre el listado, no sobre "Nuevo".
                    'patrones' => [
                        'admin.tipos-solicitud.index',
                        'admin.tipos-solicitud.edit',
                        'admin.tipos-solicitud.update',
                        'admin.tipos-solicitud.destroy',
                        'admin.tipos-solicitud.alternar-estado',
                    ],
                ],
                ['ruta' => 'admin.tipos-solicitud.create', 'texto' => 'Nuevo trámite', 'patrones' => ['admin.tipos-solicitud.create']],
            ],
        ],
        [
            'ambito' => 'admin',
            'titulo' => 'Requisitos',
            'visible' => $esAdminOAnalista,
            'funciones' => [
                [
                    'ruta' => 'admin.requisitos.index',
                    'texto' => 'Listar requisitos',
                    'patrones' => [
                        'admin.requisitos.index',
                        'admin.requisitos.show',
                        'admin.requisitos.edit',
                        'admin.requisitos.update',
                        'admin.requisitos.destroy',
                    ],
                ],
                ['ruta' => 'admin.requisitos.create', 'texto' => 'Nuevo requisito', 'patrones' => ['admin.requisitos.create']],
            ],
        ],
        [
            'ambito' => 'admin',
            'titulo' => 'Estadísticas',
            'visible' => $usuario?->esAdministrador(),
            'funciones' => [
                ['ruta' => 'admin.estadisticas', 'texto' => 'Panel del proceso', 'patrones' => ['admin.estadisticas']],
                ['ruta' => 'admin.estadisticas.exportar', 'texto' => 'Exportar CSV', 'patrones' => ['admin.estadisticas.exportar']],
            ],
        ],
        [
            'ambito' => 'admin',
            'titulo' => 'Historial de cambios',
            'visible' => $usuario?->esAdministrador(),
            'funciones' => [
                ['ruta' => 'admin.cambios.index', 'texto' => 'Bitácora', 'patrones' => ['admin.cambios.index']],
                ['ruta' => 'admin.cambios.exportar', 'texto' => 'Exportar CSV', 'patrones' => ['admin.cambios.exportar']],
            ],
        ],
        [
            'ambito' => 'admin',
            'titulo' => 'Usuarios',
            'visible' => $usuario?->esAdministrador(),
            'funciones' => [
                ['ruta' => 'admin.usuarios.index', 'texto' => 'Gestionar usuarios', 'patrones' => ['admin.usuarios.*']],
            ],
        ],
        // Ayuda del administrador: las dos pantallas con las que se atiende al
        // estudiante que escribe. Antes cada una era un modulo suelto (Preguntas
        // frecuentes aqui y Chats al final de la lista), asi que quedaban
        // separadas y habia que recordarlas: son la misma labor.
        [
            'ambito' => 'admin',
            'titulo' => 'Ayuda',
            'visible' => $usuario?->esAdministrativo(),
            'funciones' => [
                // Las FAQ se gestionan entre administrador y analista, que es
                // lo que exigen las rutas admin.preguntas.*. El taquillero entra
                // a Ayuda solo para atender el chat: sin esto el modulo entero
                // se le muestra y esos dos enlaces le rebotan con 403.
                [
                    'ruta' => 'admin.preguntas.index',
                    'texto' => 'Preguntas frecuentes',
                    'visible' => $esAdminOAnalista,
                    'patrones' => [
                        'admin.preguntas.index',
                        'admin.preguntas.edit',
                        'admin.preguntas.update',
                        'admin.preguntas.destroy',
                    ],
                ],
                [
                    'ruta' => 'admin.preguntas.create',
                    'texto' => 'Nueva pregunta',
                    'visible' => $esAdminOAnalista,
                    'patrones' => ['admin.preguntas.create'],
                ],
                [
                    'ruta' => 'admin.chat.index',
                    'texto' => 'Chats',
                    'patrones' => ['admin.chat.index'],
                    'contador' => $pendientesChat,
                    'contadorTitulo' => 'consultas sin reclamar',
                ],
            ],
        ],

        // --- Estudiante ---
        [
            'ambito' => 'estudiante',
            'titulo' => 'Mi panel',
            'funciones' => [
                ['ruta' => 'dashboard', 'texto' => 'Resumen', 'patrones' => ['dashboard']],
            ],
        ],
        [
            'ambito' => 'estudiante',
            'titulo' => 'Trámites',
            'funciones' => [
                ['ruta' => 'user.tramites.index', 'texto' => 'Ver trámites', 'patrones' => ['user.tramites.*']],
            ],
        ],
        [
            'ambito' => 'estudiante',
            'titulo' => 'Mis solicitudes',
            'funciones' => [
                // La ficha de detalle (user.historial.show) no se lista: exige el
                // id de la solicitud, asi que no se puede generar su URL. Se
                // llega desde el listado.
                ['ruta' => 'user.historial.index', 'texto' => 'Listado', 'patrones' => ['user.historial.*']],
            ],
        ],
        [
            'ambito' => 'estudiante',
            'titulo' => 'Calendario',
            'funciones' => [
                ['ruta' => 'user.citas', 'texto' => 'Mis citas', 'patrones' => ['user.citas*']],
            ],
        ],
        // Ayuda son dos pantallas que se atienden juntas: las preguntas frecuentes y
        // el chat. Van en un solo modulo desplegable, igual que el Ayuda del
        // personal, para que ambos menus se lean igual. Antes eran dos entradas
        // sueltas cruzadas entre si con un acceso rapio, y esa fila se descuadraba
        // en pantallas angostas. El contador de la consulta en curso se conserva.
        [
            'ambito' => 'estudiante',
            'titulo' => 'Ayuda',
            'funciones' => [
                [
                    'ruta' => 'user.ayuda.preguntas',
                    'texto' => 'Preguntas frecuentes',
                    'patrones' => ['user.ayuda.preguntas'],
                ],
                [
                    'ruta' => 'user.ayuda.chat.index',
                    'texto' => 'Chat de soporte',
                    'patrones' => ['user.ayuda.chat.*'],
                    'contador' => $pendientesChat,
                    'contadorTitulo' => 'consulta en curso',
                ],
            ],
        ],
    ];

    // Se descartan los modulos de otro ambito, los que el rol no puede usar
    // y sus funciones ocultas.
    $modulosVisibles = [];

    foreach ($modulos as $modulo) {
        if (($modulo['ambito'] ?? 'admin') !== $ambito) {
            continue;
        }

        if (($modulo['visible'] ?? true) === false) {
            continue;
        }

        $modulo['funciones'] = array_values(array_filter(
            $modulo['funciones'],
            fn ($funcion) => ($funcion['visible'] ?? true) !== false
        ));

        if ($modulo['funciones'] !== []) {
            $modulosVisibles[] = $modulo;
        }
    }

    /**
     * URL de una funcion del menu, incluyendo sus parametros de query.
     *
     * @param  array{ruta: string, query?: array<string, string>}  $funcion
     */
    $urlFuncion = fn (array $funcion): string => route(
        $funcion['ruta'],
        $funcion['query'] ?? []
    );

    /**
     * Una funcion esta activa si la ruta coincide y, cuando declara query
     * (p. ej. "Solicitudes pendientes" => ?estado=pendiente), ese filtro
     * tambien esta puesto. Con 'soloSinEstado' es al reves: "Todas las
     * solicitudes" solo se resalta cuando NO hay filtro de estado.
     *
     * @param  array{ruta: string, patrones?: array<int, string>, query?: array<string, string>, soloSinEstado?: bool}  $funcion
     */
    $funcionActiva = function (array $funcion): bool {
        $coincideRuta = request()->routeIs($funcion['patrones'] ?? [$funcion['ruta']]);

        if (! $coincideRuta) {
            return false;
        }

        $estadoEnUrl = (string) request('estado', '');

        if (($funcion['soloSinEstado'] ?? false) === true) {
            return $estadoEnUrl === '';
        }

        foreach (($funcion['query'] ?? []) as $clave => $valor) {
            if ((string) request($clave, '') !== (string) $valor) {
                return false;
            }
        }

        return true;
    };
@endphp

@foreach ($modulosVisibles as $modulo)
    @php
        $principal = $modulo['funciones'][0];
        $principalActiva = $funcionActiva($principal);
    @endphp

    {{-- Modulo de una sola funcion: se dibuja como enlace directo. Plegarlo
         obligaba a dos clics para llegar a la pantalla (uno para abrir el
         modulo y otro para el enlace), y en pantallas bajas el titulo quedaba
         fuera de vista. El enlace lleva a su unica funcion.

         'contador' muestra los pendientes y 'enlaceExtra' abre una pantalla
         relacionada sin salir de la entrada (de las preguntas frecuentes al
         chat). --}}
    @if ($modulo['enlaceDirecto'] ?? false)
        <div class="nav__fila">
            <a href="{{ $urlFuncion($principal) }}"
               @class(['nav__item', 'activa' => $principalActiva])
               @if ($principalActiva) aria-current="page" @endif>
                {{ $modulo['titulo'] }}

                @if (($modulo['contador'] ?? 0) > 0)
                    <span class="nav__contador"
                          title="{{ $modulo['contadorTitulo'] ?? 'pendientes' }}">{{ $modulo['contador'] }}</span>
                @endif
            </a>

            @if (! empty($modulo['enlaceExtra']))
                <a href="{{ route($modulo['enlaceExtra']['ruta']) }}"
                   class="nav__extra"
                   title="Ir al {{ strtolower($modulo['enlaceExtra']['texto']) }}">{{ $modulo['enlaceExtra']['texto'] }}</a>
            @endif
        </div>

        @continue
    @endif

    @php
        // Un modulo aparece desplegado si alguna de sus funciones es la que se
        // esta viendo; los demos arrancan plegados.
        $moduloActivo = collect($modulo['funciones'])->contains(
            fn ($funcion) => $funcionActiva($funcion)
        );
        $panelId = 'modulo-'.$loop->index;
    @endphp

    <button type="button"
            class="nav__modulo {{ $moduloActivo ? 'activo' : '' }}"
            data-modulo-btn
            aria-expanded="{{ $moduloActivo ? 'true' : 'false' }}"
            aria-controls="{{ $panelId }}">
        <span>{{ $modulo['titulo'] }}</span>
        <svg class="nav__chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true" focusable="false">
            <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
    </button>

    <div class="nav__grupo" id="{{ $panelId }}" data-modulo-panel @if (! $moduloActivo) hidden @endif>
        @foreach ($modulo['funciones'] as $funcion)
            <a href="{{ $urlFuncion($funcion) }}"
               @class([
                   'nav__item',
                   'activa' => $funcionActiva($funcion),
               ])>
                {{ $funcion['texto'] }}

                {{-- Una funcion tambien puede llevar su propio contador: en Ayuda
                     el pendiente es el de los chats, no el de las preguntas. --}}
                @if (($funcion['contador'] ?? 0) > 0)
                    <span class="nav__contador"
                          title="{{ $funcion['contadorTitulo'] ?? 'pendientes' }}">{{ $funcion['contador'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
@endforeach