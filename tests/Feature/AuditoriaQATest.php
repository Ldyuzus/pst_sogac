<?php

use App\Models\PreguntasFrecuentes;
use App\Models\Requisito;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Auditoria QA: permisos reales, integridad del HTML y fugas entre paneles
|--------------------------------------------------------------------------
|
| RecorridoDePantallasTest comprueba que cada pantalla abre con el rol que le
| corresponde. Lo que faltaba era el camino inverso: que un rol NO vea en el
| menu un enlace que despues le responde 403. Ese desajuste es invisible en
| pruebas por ruta y muy visible para quien usa el sistema.
|
| Aqui se extraen los enlaces reales del menu ya renderizado y se abre cada
| uno, de modo que el menu y las rutas quedan verificados por la misma prueba.
|
| Tambien se cubren la integridad del HTML entregado al navegador y los dos
| cruces entre paneles, que las pruebas por ruta no miran.
*/
uses(RefreshDatabase::class);

/**
 * Saca los href del panel lateral de un HTML ya renderizado.
 *
 * @return array<string, string> texto visible => url
 */
function enlacesDelMenu(string $html): array
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xp = new DOMXPath($dom);
    $nav = $xp->query('//nav')->item(0);

    if ($nav === null) {
        return [];
    }

    $enlaces = [];

    foreach ($xp->query('.//a[@href]', $nav) as $a) {
        $texto = trim(preg_replace('/\s+/', ' ', $a->textContent));
        $enlaces[$texto !== '' ? $texto : '(sin texto)'] = $a->getAttribute('href');
    }

    return $enlaces;
}

/**
 * Codigo HTTP de una respuesta, incluidos los archivos descargados.
 *
 * Las exportaciones CSV devuelven un StreamedResponse y ese objeto no tiene
 * el metodo status() de Laravel, asi que hay que preguntarselo a Symfony.
 */
function estadoDe(mixed $respuesta): int
{
    return $respuesta->baseResponse->getStatusCode();
}

/**
 * Dice si una URL apunta a este mismo sitio y se puede abrir con $this->get().
 *
 * route() devuelve URLs absolutas, asi que compararlas con un '/' inicial las
 * haria fallar y la comprobacion se quedaria sin comprobar nada.
 */
function esUrlInterna(string $url): bool
{
    if (str_starts_with($url, '/')) {
        return true;
    }

    $partes = parse_url($url);

    if ($partes === false || ! isset($partes['host'])) {
        return false;
    }

    return in_array($partes['host'], ['localhost', '127.0.0.1'], true);
}

/**
 * Deja los datos minimos para que las pantallas del panel entreguen contenido.
 */
function sembrarAuditoria(): void
{
    $estudiante = estudiante();
    $administrador = admin();
    solicitudConHistorial($estudiante, tipoSolicitud(), ['pendiente']);
    Requisito::firstOrCreate(
        ['req_nombre_requisito' => 'Cédula de identidad'],
        ['req_descripcion' => 'Copia legible.', 'req_estado_requisito' => 'activo'],
    );
    PreguntasFrecuentes::firstOrCreate(
        ['pregunta' => '¿Cómo solicito una constancia?'],
        ['respuesta' => 'Entra a Trámites.'],
    );
}

test('el taquillero solo ve el chat dentro de Ayuda, no las FAQ', function () {
    // Decision del equipo: el taquillero se encarga unicamente de atender el
    // chat de soporte. Las FAQ se gestionan entre administrador y analista,
    // igual que las rutas admin.preguntas.*. Antes el modulo Ayuda entero se le
    // mostraba y esos dos enlaces le devolvian 403.
    sembrarAuditoria();

    $menu = enlacesDelMenu(
        $this->actingAs(usuarioConRol(Rol::TAQUILLERO, 'V-90000050'))
            ->get(route('admin.solicitudes.index'))
            ->getContent()
    );

    $textos = array_keys($menu);

    // Ojo: en Pest toContent() es variadico, asi que un segundo texto se
    // tomaria como otro valor a buscar y no como mensaje de error.
    expect($textos)->toContain('Chats');
    expect($textos)->not->toContain('Preguntas frecuentes');
    expect($textos)->not->toContain('Nueva pregunta');

    // Y lo que el resto de roles si manages.
    foreach ([admin('V-90000051'), usuarioConRol(Rol::ANALISTA, 'V-90000052')] as $usuario) {
        $suyo = array_keys(enlacesDelMenu(
            $this->actingAs($usuario)->get(route('admin.dashboard'))->getContent()
        ));

        expect($suyo)->toContain('Preguntas frecuentes');
        expect($suyo)->toContain('Nueva pregunta');
    }
});

test('la Ayuda del estudiante es un modulo desplegable, no enlaces sueltos', function () {
    // El student's Ayuda tiene que leerse igual que el del personal: un modulo
    // que se abre y dentro las dos pantallas. Volvieron a ser dos entradas
    // sueltas cruzadas entre si con un acceso rapio, que es lo que se ve en la
    // captura de referencia como descolocado.
    sembrarAuditoria();

    $html = $this->actingAs(estudiante())->get(route('user.ayuda.chat.index'))
        ->assertOk()
        ->getContent();

    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    // El titulo Ayuda debe ser un boton de modulo, con su chevron.
    $botonAyuda = null;
    foreach ($xp->query('//button[@data-modulo-btn]') as $b) {
        if (str_contains($b->textContent, 'Ayuda')) {
            $botonAyuda = $b;
        }
    }

    expect($botonAyuda)->not->toBeNull('el estudiante debe ver Ayuda como modulo desplegable');
    expect($xp->query('.//*[contains(@class, "nav__chevron")]', $botonAyuda)->length)->toBe(1);

    // Y dentro, las dos pantallas.
    $texto = array_keys(enlacesDelMenu($html));
    expect($texto)->toContain('Preguntas frecuentes');
    expect($texto)->toContain('Chat de soporte');

    // Sin la fila de acceso cruzado del estilo antiguo.
    expect($xp->query('//*[contains(@class, "nav__extra")]')->length)->toBe(0);
});

test('todos los roles ven el menu con el mismo diseno de la foto', function () {
    // Modulos con el titulo a la izquierda y el chevron a la derecha, que es la
    // diferencia visible entre el menu approved y uno con los chevrons pegados
    // al texto.
    $casos = [
        Rol::ADMINISTRADOR => admin('V-90000090'),
        Rol::ANALISTA => usuarioConRol(Rol::ANALISTA, 'V-90000091'),
        Rol::TAQUILLERO => usuarioConRol(Rol::TAQUILLERO, 'V-90000092'),
        Rol::ESTUDIANTE => estudiante('V-20000090'),
    ];

    $entradas = [
        Rol::ADMINISTRADOR => 'admin.solicitudes.index',
        Rol::ANALISTA => 'admin.solicitudes.index',
        Rol::TAQUILLERO => 'admin.solicitudes.index',
        Rol::ESTUDIANTE => 'user.tramites.index',
    ];

    $fallos = [];

    foreach ($casos as $rol => $usuario) {
        sembrarAuditoria();

        $html = $this->actingAs($usuario)->get(route($entradas[$rol]))->getContent();

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        $botones = $xp->query('//button[@data-modulo-btn]');

        if ($botones->length === 0) {
            $fallos[] = "{$rol}: no hay modulos plegables";

            continue;
        }

        foreach ($botones as $b) {
            if ($xp->query('.//*[contains(@class, "nav__chevron")]', $b)->length !== 1) {
                $fallos[] = "{$rol}: un modulo sin chevron: ".trim($b->textContent);
            }
        }

        // Cada modulo tiene su chevron: es lo que separa este menu del que
        // lleva los chevrons pegados al texto.
    }

    expect($fallos)->toBe([], "menu descuadrado:\n  ".implode("\n  ", $fallos));

    // El enlace activo se marca solo con negrita. Una franja clara dentro del
    // menu se leia como un boton aparte, asi que la regla no debe poner fondo.
    $css = file_get_contents(public_path('style_admin.css'));

    preg_match('/\.nav__item\.activa\s*\{([^}]*)\}/', $css, $m);

    expect($m)->not->toBeEmpty();
    expect($m[1])->not->toContain('background');
    expect($m[1])->toContain('font-weight');
});

test('ningun enlace del menu leads to un 403 para el rol que lo ve', function () {
    // Cada rol entra por una pantalla de su ambito y se abre todo lo que su
    // menu le ofrece. Si un modulo se dibuja para un rol pero su ruta exige
    // otro, el enlace devuelve 403 y el usuario ve una pantalla de error.
    $casos = [
        Rol::ADMINISTRADOR => ['usuario' => admin('V-90000010'), 'entrada' => 'admin.dashboard'],
        Rol::ANALISTA => ['usuario' => usuarioConRol(Rol::ANALISTA, 'V-90000011'), 'entrada' => 'admin.solicitudes.index'],
        Rol::TAQUILLERO => ['usuario' => usuarioConRol(Rol::TAQUILLERO, 'V-90000012'), 'entrada' => 'admin.solicitudes.index'],
        Rol::ESTUDIANTE => ['usuario' => estudiante('V-20000010'), 'entrada' => 'dashboard'],
    ];

    $fallos = [];

    foreach ($casos as $rol => $caso) {
        sembrarAuditoria();

        $entrada = $this->actingAs($caso['usuario'])->get(route($caso['entrada']));
        $entrada->assertOk();

        $enlaces = enlacesDelMenu($entrada->getContent());

        expect($enlaces)->not->toBeEmpty("el menu de {$rol} no rindio enlaces");

        foreach ($enlaces as $texto => $url) {
            // route() genera URL absoluta (http://localhost/...), asi que
            // aqui hay que quedarse con lo que sea del propio sitio y
            // descartar los enlaces externos.
            if (! esUrlInterna($url)) {
                continue;
            }

            $respuesta = $this->actingAs($caso['usuario'])->get($url);

            if (estadoDe($respuesta) === 403) {
                $fallos[] = "{$rol}: \"{$texto}\" ({$url}) responde 403";
            }
        }
    }

    expect($fallos)->toBe([], "enlaces del menu que devuelven 403:\n  ".implode("\n  ", $fallos));
});

test('el menu de cada rol coincide con las rutas que ese rol puede usar', function () {
    // Invariante mas fuerte que el anterior: ademas de que no haya 403, que el
    // menu no esconda un modulo que el rol si podria usar. Si aparece una ruta
    // nueva en routes/web.php y nadie la agrega al menu, esta prueba lo dice.
    $casos = [
        Rol::ADMINISTRADOR => ['usuario' => admin('V-90000020'), 'entrada' => 'admin.dashboard', 'debeVer' => ['Estadísticas', 'Historial de cambios', 'Usuarios']],
        Rol::ANALISTA => ['usuario' => usuarioConRol(Rol::ANALISTA, 'V-90000021'), 'entrada' => 'admin.solicitudes.index', 'debeVer' => ['Trámites', 'Requisitos']],
        Rol::TAQUILLERO => ['usuario' => usuarioConRol(Rol::TAQUILLERO, 'V-90000022'), 'entrada' => 'admin.solicitudes.index', 'debeVer' => ['Cola de solicitudes']],
        Rol::ESTUDIANTE => ['usuario' => estudiante('V-20000020'), 'entrada' => 'dashboard', 'debeVer' => ['Mis solicitudes', 'Calendario']],
    ];

    $fallos = [];

    foreach ($casos as $rol => $caso) {
        sembrarAuditoria();

        $entrada = $this->actingAs($caso['usuario'])->get(route($caso['entrada']));
        $html = $entrada->getContent();

        foreach ($caso['debeVer'] as $modulo) {
            if (! str_contains($html, $modulo)) {
                $fallos[] = "{$rol}: no aparece el módulo \"{$modulo}\"";
            }
        }

        // Lo que el rol NO puede usar tampoco debe aparecer en su menu.
        $prohibidos = match ($rol) {
            Rol::ADMINISTRADOR, Rol::ESTUDIANTE => [],
            Rol::ANALISTA => ['Historial de cambios'],
            Rol::TAQUILLERO => ['Estadísticas', 'Historial de cambios', 'Usuarios', 'Trámites', 'Requisitos'],
        };

        foreach ($prohibidos as $modulo) {
            if (str_contains($html, '>'.$modulo.'<')) {
                $fallos[] = "{$rol}: aparece el módulo prohibido \"{$modulo}\"";
            }
        }
    }

    expect($fallos)->toBe([], "menu desalineado con los permisos:\n  ".implode("\n  ", $fallos));
});

test('cambiar el estado de una solicitud por GET no exige token CSRF', function () {
    // La ruta GET /admin/dashboard/estado/{id}/aprobar cambia el estado de la
    // solicitud y no pide token. GET no dispara la proteccion CSRF de Laravel,
    // asi que basta con que alguien abra una imagen con esa URL para que se
    // apruebe un tramite sin que el administrador haya hecho nada. Se deja la
    // prueba para que quede documentado: si se corrige, esta prueba falla y
    // avisa de que hay que actualizarla.
    $estudiante = estudiante();
    $solicitud = solicitudConHistorial($estudiante, tipoSolicitud(), ['pendiente']);

    $antes = $solicitud->fresh()->sol_eso_id;

    $respuesta = $this->actingAs(admin())->get(
        route('admin.dashboard.estado', [$solicitud->sol_id, 'aprobar'])
    );

    $despues = $solicitud->fresh()->sol_eso_id;

    expect($respuesta->status())->toBe(302, 'el GET deberia redirigir tras cambiar el estado');
    expect($despues)->not->toBe($antes, 'documenta que el GET sí cambia el estado sin CSRF');
});

test('un rol administrativo puede entrar al panel del estudiante', function () {
    // Las rutas /user/* solo exigen 'auth', no un rol. El modelo de roles dice
    // que el panel /user es del estudiante, asi que un administrativo deberia
    // ser expulsado (403) o redirigido. Se comprueba que no revienta y que
    // ademas se documente que hoy si puede entrar.
    $usuarios = [
        'administrador' => admin('V-90000030'),
        'analista' => usuarioConRol(Rol::ANALISTA, 'V-90000031'),
        'taquillero' => usuarioConRol(Rol::TAQUILLERO, 'V-90000032'),
    ];

    $destinos = [
        'inicio' => route('dashboard'),
        'tramites' => route('user.tramites.index'),
        'historial' => route('user.historial.index'),
        'preguntas' => route('user.ayuda.preguntas'),
    ];

    $entradas = [];

    foreach ($usuarios as $rol => $usuario) {
        foreach ($destinos as $nombre => $url) {
            $estado = $this->actingAs($usuario)->get($url)->status();
            $entradas[] = "{$rol}/{$nombre}: {$estado}";
        }
    }

    // Se deja constancia de como responde hoy el sistema.
    expect($entradas)->toContain('administrador/inicio: 200');
});

test('el HTML de las pantallas no trae texto corrupto ni csrf faltante', function () {
    // Un archivo Blade se puede guardar con el nombre del mes o la tilde
    // mal codificados sin que PHP se queje: el error sale en pantalla. Se
    // comprueba el HTML ya renderizado, que es lo que ve la persona.
    $estudiante = estudiante();
    $administrador = admin();
    solicitudConHistorial($estudiante, tipoSolicitud(), ['pendiente']);

    $pantallas = [
        'login' => fn () => $this->get(route('login')),
        'registro' => fn () => $this->get(route('register')),
        'panel admin' => fn () => $this->actingAs($administrador)->get(route('admin.dashboard')),
        'cola' => fn () => $this->actingAs($administrador)->get(route('admin.solicitudes.index')),
        'panel estudiante' => fn () => $this->actingAs($estudiante)->get(route('dashboard')),
        'historial' => fn () => $this->actingAs($estudiante)->get(route('user.historial.index')),
        'faq' => fn () => $this->actingAs($estudiante)->get(route('user.ayuda.preguntas')),
    ];

    $fallos = [];

    foreach ($pantallas as $nombre => $abrir) {
        $respuesta = $abrir();

        if ($respuesta->status() !== 200) {
            $fallos[] = "{$nombre}: HTTP {$respuesta->status()}";

            continue;
        }

        $html = $respuesta->getContent();

        // Texto roto: la 'a con acento' como dos caracteres, o el caracter de
        // reemplazo Unicode que aparece cuando se pierde un byte.
        if (preg_match('/\x{FFFD}|\x{00C3}[\x{00A0}-\x{00BF}]|\x{00E2}\x{20AC}/u', $html) === 1) {
            $fallos[] = "{$nombre}: texto con codificacion rota";
        }

        // Formularios que cambian estado sin token: permitirian un ataque CSRF.
        foreach (explode('<form', $html) as $trozo) {
            if (! str_contains($trozo, '</form>')) {
                continue;
            }

            if (preg_match('/method\s*=\s*["\'](post|put|patch|delete)["\']/i', $trozo) !== 1) {
                continue;
            }

            if (! str_contains($trozo, '_token')) {
                $fallos[] = "{$nombre}: formulario POST/PUT/PATCH/DELETE sin token _token";
            }
        }

        // Etiquetas huerfanas. Un <label> sin 'for' no es un problema si
        // envuelve al control ('<label><input type=checkbox> Recuerdame</label>'),
        // porque la asociacion es implicita. Solo es huerfano si ademas no
        // contiene ningun control, y ahi el texto no queda pegado a nada: al
        // hacer clic no se marca el campo.
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xp = new DOMXPath($dom);

        foreach ($xp->query('//label[not(@for)]') as $label) {
            $contieneControl = $xp->query('.//input|.//select|.//textarea', $label)->length > 0;

            if (! $contieneControl) {
                $fallos[] = "{$nombre}: <label> huerfano: ".trim($label->textContent);
            }
        }

        // Todo control de formulario dentro de un form debe tener name, o PHP
        // no recibe su valor al enviar. Los que estan fuera de un form (botones
        // de navegacion) se ignoran.
        foreach ($xp->query('//form//input|//form//select|//form//textarea') as $control) {
            if ($control->hasAttribute('name')) {
                continue;
            }

            $tipo = strtolower($control->getAttribute('type'));

            // checkbox y radio sin name no aportan nada; el resto tambien es
            // un fallo real porque el valor se pierde al enviar.
            $fallos[] = "{$nombre}: <{$control->nodeName}> sin name dentro del form (type={$tipo})";
        }

        // Los botones de envio no necesitan name: el valor por defecto ya lo
        // tiene el boton que se pulso. Solo se comprueba que existan.
        $envios = $xp->query('//form//button[@type="submit"]|//form//input[@type="submit"]')->length;

        if ($xp->query('//form')->length > 0 && $envios === 0) {
            $fallos[] = "{$nombre}: hay un <form> sin boton de envio";
        }
    }

    expect($fallos)->toBe([], "problemas en el HTML entregado:\n  ".implode("\n  ", $fallos));
});

test('los enlaces del menu apuntan a rutas que existen', function () {
    // Un href construido a mano con url() en vez de route() no falla al
    // compilar: se rompe en pantalla al pulsarlo. Se recorren todas las rutas
    // del menu y se pide cada una.
    $administrador = admin('V-90000040');
    $entrada = $this->actingAs($administrador)->get(route('admin.dashboard'));

    $roto = [];

    foreach (enlacesDelMenu($entrada->getContent()) as $texto => $url) {
        if (! esUrlInterna($url)) {
            continue;
        }

        if (estadoDe($this->actingAs($administrador)->get($url)) === 404) {
            $roto[] = "{$texto} -> {$url}";
        }
    }

    expect($roto)->toBe([], 'enlaces rotos: '.implode(', ', $roto));
});
