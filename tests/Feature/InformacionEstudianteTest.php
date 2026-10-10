<?php

use App\Models\Usuario;
use App\Pnf;
use Illuminate\Foundation\Testing\RefreshDatabase;

/*
|--------------------------------------------------------------------------
| Lo que el estudiante escribe en el registro y lo que queda guardado
|--------------------------------------------------------------------------
|
| El bug de fondo era que el registro pedia telefono, PNF y trayecto, los daba
| por obligatorios y los escribia en columnas que no existian ('usu_telefono',
| 'usu_pnf', 'usu_trayecto'). Como tampoco estaban en el $fillable del modelo,
| Laravel los descartaba en silencio: el registro terminaba bien y el dato no
| existia en ninguna parte.
|
| La migracion 2026_10_06_000100 (de Omar) creo las columnas en usuarios, asi
| que la carrera y el trayecto quedan en la ficha del estudiante y no en cada
| solicitud: son datos de la persona, no del tramite. Estas pruebas vigilan que
| los tres datos se guarden de verdad.
*/
uses(RefreshDatabase::class);

/**
 * Datos validos de un registro de estudiante.
 *
 * @return array<string, string>
 */
function datosRegistroEstudiante(array $cambios = []): array
{
    return array_merge([
        'primer_nombre' => 'Andrea',
        'segundo_nombre' => 'Sofia',
        'primer_apellido' => 'Rojas',
        'segundo_apellido' => 'Perez',
        'nacionalidad' => 'V',
        'cedula' => '27999877',
        'telefono' => '04141234567',
        'pnf' => 'Informatica',
        'trayecto' => '3',
        'email' => 'andrea.test@example.com',
        'password' => 'clave-de-prueba-1',
        'password_confirmation' => 'clave-de-prueba-1',
    ], $cambios);
}

test('el telefono que escribe el estudiante si se guarda', function () {
    $this->post(route('register.post'), datosRegistroEstudiante())
        ->assertRedirect(route('dashboard'));

    $usuario = Usuario::where('usu_numero_documento', '27999877')->firstOrFail();

    // Antes esto daba NULL: se escribia en 'usu_telefono', que no existe.
    expect($usuario->usu_numero_telefono)->toBe('04141234567');
});

test('la carrera y el semestre si se guardan en la ficha del estudiante', function () {
    $this->post(route('register.post'), datosRegistroEstudiante())
        ->assertRedirect(route('dashboard'));

    $usuario = Usuario::where('usu_numero_documento', '27999877')->firstOrFail();

    // Antes se perdian: las columnas no existian.
    expect($usuario->usu_pnf)->toBe('Informatica');
    expect($usuario->usu_trayecto)->toBe('3');
});

test('el registro sigue pidiendo carrera y semestre', function () {
    $html = $this->get(route('register'))->assertOk()->getContent();

    expect($html)->toContain('name="pnf"');
    expect($html)->toContain('name="trayecto"');
});

test('el telefono, la carrera y el semestre siguen siendo obligatorios', function () {
    foreach (['telefono', 'pnf', 'trayecto'] as $campo) {
        $this->post(route('register.post'), datosRegistroEstudiante([$campo => '']))
            ->assertSessionHasErrors($campo);
    }

    expect(Usuario::where('usu_numero_documento', '27999877')->exists())->toBeFalse();
});

test('los trayectos se devuelven como texto y no como numero', function () {
    // PHP convierte a entero las claves numericas de un array, asi que la clave
    // '3' acaba siendo el entero 3. Como lo que se guarda en la base es texto,
    // los valores tienen que salir como texto o la comparacion estricta falla.
    foreach (Pnf::valoresTrayecto() as $valor) {
        expect($valor)->toBeString();
    }

    expect(in_array('3', Pnf::valoresTrayecto(), true))->toBeTrue();
    expect(in_array('1', Pnf::valoresTrayecto(), true))->toBeTrue();
    expect(in_array('Inicial', Pnf::valoresTrayecto(), true))->toBeTrue();

    foreach (Pnf::valores() as $valor) {
        expect($valor)->toBeString();
    }
});

test('el personal ve la carrera y el semestre en el detalle de la solicitud', function () {
    $estudiante = estudiante();
    solicitudConHistorial($estudiante, tipoSolicitud(), ['pendiente']);

    // La carrera viene de la ficha del estudiante, no de la solicitud.
    $estudiante->forceFill([
        'usu_pnf' => 'Electricidad',
        'usu_trayecto' => '3',
    ])->save();

    $html = $this->actingAs(admin())->get(route('admin.solicitudes.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-carrera="Electricidad"');
    expect($html)->toContain('data-semestre="Trayecto III"');

    $this->actingAs(admin())->get(route('admin.dashboard'))->assertOk();
});

test('un estudiante sin carrera muestra Sin registrar, no una celda vacia', function () {
    solicitudConHistorial(estudiante(), tipoSolicitud(), ['pendiente']);

    $html = $this->actingAs(admin())->get(route('admin.solicitudes.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-carrera="Sin registrar"');
});

test('la carrera se muestra con el nombre de la persona no con el valor guardado', function () {
    $estudiante = estudiante();
    solicitudConHistorial($estudiante, tipoSolicitud(), ['pendiente']);

    // En la base queda 'Agroalimentacion' (sin tilde) y en pantalla debe verse
    // 'Agroalimentación'.
    $estudiante->forceFill(['usu_pnf' => 'Agroalimentacion', 'usu_trayecto' => '2'])->save();

    $html = $this->actingAs(admin())->get(route('admin.solicitudes.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('data-carrera="Agroalimentación"');
    expect($html)->toContain('data-semestre="Trayecto II"');
});
