<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function mostrarFormulario()
    {
        return view('auth.register');
    }

    public function registrar(Request $request)
    {
        // Validamos que los campos obligatorios cumplan con los límites de la base de datos
        $request->validate([
            'nombre'    => 'required|string|max:50',
            'apellido'  => 'required|string|max:50',
            'documento' => 'required|string|max:20|unique:usuarios,usu_numero_documento',
            'email'     => 'required|email|max:100|unique:usuarios,usu_correo_electronico',
            'password'  => 'required|min:6'
        ]);

        // Antes: 'usu_tdo_id' => 1 (fijo). Eso asumía que el ID 1 de
        // tipo_documentos SIEMPRE es "Cédula" — pero ese número puede
        // cambiar entre entornos (SQLite vs PostgreSQL, orden del seeder,
        // etc.). Buscamos por NOMBRE, y si no existe todavía, lo creamos
        // (así el registro nunca se rompe por esto).
        $tipoCedula = TipoDocumento::firstOrCreate(
            ['tdo_abreviatura' => 'V'],
            ['tdo_nombre_documento' => 'Cédula de Identidad']
        );

        // Creamos el usuario mapeando los inputs del formulario con las columnas de tu tabla
        $usuario = Usuario::create([
            'usu_rol'              => 'estudiante', // Rol por defecto para nuevos registros
            'usu_tdo_id'           => $tipoCedula->tdo_id,
            'usu_primer_nombre'    => $request->nombre,
            'usu_primer_apellido'  => $request->apellido,
            'usu_numero_documento' => $request->documento,
            'usu_correo_electronico' => $request->email,
            'usu_contrasena_hash'  => Hash::make($request->password), // Encriptación segura de contraseña
            'usu_estado_cuenta'    => 'activo'
        ]);

        // Iniciamos sesión automáticamente tras el registro exitoso
        Auth::login($usuario);

        return redirect()->route('dashboard');
    }
}