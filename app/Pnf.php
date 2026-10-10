<?php

namespace App;

/**
 * Programas Nacionales de Formacion y trayectos de la UPTP.
 *
 * Viven aqui, y no repetidos en cada vista, porque los necesitan tres sitios:
 * el formulario de solicitud (las opciones), el controlador (validar que lo
 * que llega sea real) y el panel administrativo (volver a mostrarlo con
 * nombre bonito). Si la lista se duplicara, un cambio en un sitio dejaria los
 * otros dos desactualizados y la validacion rechazaria al estudiante.
 */
class Pnf
{
    /**
     * Valor guardado => nombre que se muestra.
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return [
            'Informatica' => 'Informática',
            'Agroalimentacion' => 'Agroalimentación',
            'Administracion' => 'Administración',
            'Mantenimiento' => 'Mantenimiento',
            'Electricidad' => 'Electricidad',
        ];
    }

    /**
     * Nombres de los trayectos que puede elegir el estudiante.
     *
     * @return array<string, string>
     */
    public static function trayectos(): array
    {
        return [
            'Inicial' => 'Trayecto Inicial',
            '1' => 'Trayecto I',
            '2' => 'Trayecto II',
            '3' => 'Trayecto III',
            '4' => 'Trayecto IV',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function valores(): array
    {
        return array_map('strval', array_keys(self::opciones()));
    }

    /**
     * Valores validos del trayecto, como texto.
     *
     * PHP convierte a entero cualquier clave que sea un numero, asi que las
     * claves de trayectos() ('1', '2'...) son int, no string. Aqui se devuelve
     * el texto tal cual se guarda en la base, para que un in_array() estricto
     * de '3' contra '3' de verdad funcione y no contra 3.
     *
     * @return array<int, string>
     */
    public static function valoresTrayecto(): array
    {
        return array_map('strval', array_keys(self::trayectos()));
    }

    /**
     * Nombre legible de un valor guardado. Devuelve el propio valor si no
     * esta en la lista, para no perder informacion de datos viejos.
     */
    public static function nombreCarrera(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin registrar';
        }

        return self::opciones()[$valor] ?? $valor;
    }

    public static function nombreTrayecto(?string $valor): string
    {
        if ($valor === null || $valor === '') {
            return 'Sin registrar';
        }

        return self::trayectos()[$valor] ?? $valor;
    }
}
