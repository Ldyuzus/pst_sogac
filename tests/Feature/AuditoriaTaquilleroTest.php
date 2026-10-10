<?php

use App\Models\PreguntasFrecuentes;
use App\Models\Requisito;
use App\Models\Rol;
use App\Models\Solicitud;
use App\Models\TipoSolicitud;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
 * Que ve y que puede hacer el taquillero en su pantalla de trabajo.
 */
uses(RefreshDatabase::class);

test('el taquillero ve la cola con aprobar y rechazar, pero sin estadisticas', function () {
    estudiante();
    admin();
    solicitudConHistorial(estudiante(), tipoSolicitud(), ['pendiente']);
    Requisito::firstOrCreate(['req_nombre_requisito' => 'C'], ['req_descripcion' => 'd', 'req_estado_requisito' => 'activo']);
    PreguntasFrecuentes::firstOrCreate(['pregunta' => 'p'], ['respuesta' => 'r']);

    $taquillero = usuarioConRol(Rol::TAQUILLERO, 'V-90000070');

    $html = $this->actingAs($taquillero)->get(route('admin.solicitudes.index'))
        ->assertOk()
        ->getContent();

    $tieneAprobar = str_contains($html, 'aprobar');
    $tieneRechazar = str_contains($html, 'rechazar');

    // Sin esto el taquillo no podria hacer su trabajo: solo ver.
    expect($tieneAprobar)->toBeTrue('el taquillero debe poder aprobar');
    expect($tieneRechazar)->toBeTrue('el taquillero debe poder rechazar');

    // Las tarjetas de estadisticas son solo para administrador y analista.
    $muestraEstadisticas = view('admin.dashboard', [
        'solicitudes' => Solicitud::paginate(20),
        'tiposSolicitud' => TipoSolicitud::all(),
        'mostrarEstadisticas' => false,
        'urlBase' => route('admin.solicitudes.index'),
    ])->render();

    expect($muestraEstadisticas)->not->toContain('estadisticas');

    // Y en su pagina no debe haber enlaces a modulos reservados.
    $enlaces = array_values(array_filter(
        array_keys(enlacesDelMenu($html)),
        fn ($t) => in_array($t, ['Estadísticas', 'Historial de cambios', 'Usuarios', 'Trámites', 'Requisitos'], true)
    ));

    expect($enlaces)->toBe([]);
});

test('el taquillero ve la cola con buscar y filtrar', function () {
    estudiante();
    admin();
    solicitudConHistorial(estudiante(), tipoSolicitud(), ['pendiente']);

    $taquillero = usuarioConRol(Rol::TAQUILLERO, 'V-90000071');

    $html = $this->actingAs($taquillero)->get(route('admin.solicitudes.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="busqueda"');

    // Los botones de la tabla son enlaces GET al endpoint de estado. Se deja
    // constancia porque es la via por la que el taquillero resuelve de verdad:
    // el modal usa POST con token, pero la tabla no.
    expect($html)->toContain('/admin/dashboard/estado/');
});
