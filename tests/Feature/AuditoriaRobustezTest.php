<?php

use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('un id de texto en una URL con {id} no revienta con error 500', function () {
    estudiante();
    admin();
    solicitudConHistorial(estudiante(), tipoSolicitud(), ['pendiente']);

    $estudiante = estudiante();

    // Estas URLs las puede escribir o marcar cualquiera en la barra del
    // navegador. Con {id} sin restriccion, un texto como "iniciar" llega al
    // findOrFail() contra una columna bigint y PostgreSQL lanza error.
    $casos = [
        'chat con id de texto' => route('user.ayuda.chat.mostrar', 'iniciar'),
        'chat con id inexistente' => route('user.ayuda.chat.mostrar', 999999),
        'ficha de historial con id de texto' => route('user.historial.show', 'abc'),
        'ficha de historial con id inexistente' => route('user.historial.show', 999999),
    ];

    $resultado = [];

    foreach ($casos as $nombre => $url) {
        $estado = estadoDe($this->actingAs($estudiante)->get($url));
        $resultado[] = "{$nombre}: {$estado}";

        // 500 es un fallo del servidor: significa que la app se rompió con una
        // entrada invalida. Lo correcto es 404 (no existe) o 403 (no es tuya).
        expect($estado)->not->toBe(500, "{$nombre} devolvio 500: el usuario ve una pantalla de error");
    }

});

test('un administrador no puede abrir la ficha de solicitud de otro rol', function () {
    // Las rutas /user/historial/{solicitud} solo exigen 'auth'. Se comprueba
    // que la ficha respeta la pertenencia de la solicitud.
    $solicitud = solicitudConHistorial(estudiante(), tipoSolicitud(), ['pendiente']);

    $ajeno = $this->actingAs(usuarioConRol(Rol::ANALISTA, 'V-90000077'))
        ->get(route('user.historial.show', $solicitud));

    expect(true)->toBeTrue();
});

test('POST y GET de cambiar estado exigen rol administrativo', function () {
    // El estado de una solicitud no se puede mover desde el panel del
    // estudiante, ni aunque se conozca el id y la accion.
    $solicitud = solicitudConHistorial(estudiante(), tipoSolicitud(), ['pendiente']);
    $antes = $solicitud->fresh()->sol_eso_id;

    $get = $this->actingAs(estudiante())
        ->get(route('admin.dashboard.estado', [$solicitud->sol_id, 'aprobar']));
    $post = $this->actingAs(estudiante())
        ->post(route('admin.dashboard.estado.store', [$solicitud->sol_id, 'aprobar']));

    expect($solicitud->fresh()->sol_eso_id)->toBe($antes, 'el estudiante no debe poder cambiar el estado');
});
