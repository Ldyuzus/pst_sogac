<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Cuentas del personal administrativo. Van antes que los seeders de
        // solicitudes porque los datos de prueba necesitan un administrador al
        // que atribuirlos, y al final para que quede disponible aunque alguien
        // corra solo este seeder.
        $this->call(PersonalAdministrativoSeeder::class);

        $this->call(SolicitudesDePruebaSeeder::class);
        $this->call(SolicitudesPanelEstadisticasSeeder::class);
        $this->call(PreguntasFrecuentesDemoSeeder::class);

        // Configuración de los datos que el estudiante precarga en su perfil.
        // Va antes de la bitácora para que los cambios del admin sobre estos
        // campos también queden reflejados en ella.
        $this->call(DatosPrecargaConfigSeeder::class);

        // Va al final porque reconstruye la bitacora a partir de lo que dejaron
        // los seeders anteriores: necesita solicitudes con historial y catálogo
        // ya cargados.
        $this->call(HistorialCambiosDemoSeeder::class);
    }
}
