<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Cuentas del personal administrativo para desarrollo.
 *
 * Antes estas cuentas vivian solo en la base local de cada quien, asi que al
 * montar el proyecto desde cero no habia forma de entrar al panel
 * administrativo: los seeders creaban estudiantes y nada mas. Un companero
 * recien clonado se registraba como estudiante y no podia ver nada del lado
 * del personal.
 *
 * Se crean con correo de ejemplo y clave conocida. Son solo para desarrollo:
 * no son datos reales y no deben usarse en un entorno de pruebas reales ni
 * en produccion. Corre con: php artisan db:seed
 */
class PersonalAdministrativoSeeder extends Seeder
{
    /**
     * La clave de todas las cuentas de ejemplo. Es la misma que usan los
     * estudiantes de prueba, para que nadie tenga que memorizar otra.
     */
    private const CLAVE = 'password';

    public function run(): void
    {
        $cedula = TipoDocumento::firstOrCreate(
            ['tdo_abreviatura' => 'V'],
            ['tdo_nombre_documento' => 'Cédula de Identidad'],
        );

        // Un usuario por cada rol de la jerarquia administrativa. El
        // estudiante lo crea SolicitudesDePruebaSeeder, asi que aqui no hace
        // falta repetirlo.
        $cuentas = [
            [
                'documento' => 'V-00000001',
                'nombre' => 'Admin',
                'apellido' => 'Prueba',
                'correo' => 'admin@uptp.test',
                'rol' => Rol::ADMINISTRADOR,
            ],
            [
                'documento' => 'V-00000002',
                'nombre' => 'Analista',
                'apellido' => 'Prueba',
                'correo' => 'analista@uptp.test',
                'rol' => Rol::ANALISTA,
            ],
            [
                'documento' => 'V-00000003',
                'nombre' => 'Taquillero',
                'apellido' => 'Prueba',
                'correo' => 'taquillero@uptp.test',
                'rol' => Rol::TAQUILLERO,
            ],
        ];

        foreach ($cuentas as $cuenta) {
            // firstOrCreate por documento: correr el seeder dos veces no
            // duplica cuentas ni cambia la clave de las que ya existen.
            Usuario::firstOrCreate(
                ['usu_numero_documento' => $cuenta['documento']],
                [
                    'usu_rol' => $cuenta['rol'],
                    'usu_tdo_id' => $cedula->tdo_id,
                    'usu_primer_nombre' => $cuenta['nombre'],
                    'usu_primer_apellido' => $cuenta['apellido'],
                    'usu_correo_electronico' => $cuenta['correo'],
                    'usu_contrasena_hash' => bcrypt(self::CLAVE),
                    'usu_estado_cuenta' => 'activo',
                    'usu_numero_telefono' => '04140000000',
                    'usu_fecha_registro' => now(),
                ],
            );
        }
    }
}
