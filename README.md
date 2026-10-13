<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Solicítalo — Sistema de Solicitudes Estudiantiles

Gestión del proceso de solicitudes de una institución: el estudiante presenta trámites, el
administrador los aprueba o rechaza, y el sistema deja constancia de todo lo que pasa.

### Módulos

| Módulo | Dónde | Documentación |
| --- | --- | --- |
| Panel estadístico (admin) | `/admin/estadisticas` | Métricas del proceso, con exportación a CSV |
| **Historial de solicitudes** (estudiante) | `/user/historial` | [docs/historial-de-solicitudes.md](docs/historial-de-solicitudes.md) |
| **Historial de cambios** (admin) | `/admin/cambios` | [docs/historial-de-cambios.md](docs/historial-de-cambios.md) |
| Preguntas frecuentes y chat | `/user/ayuda/*` | Dos sectores separados dentro de la ayuda |

Los dos historiales son módulos **independientes**: el primero cuenta las solicitudes de cada
estudiante y su recorrido por los estados; el segundo es una bitácora de todo lo que se modifica
en el sistema, con su autor y los valores antes y después.

### Homepage

`/` sirve el **homepage institucional** de Control de Estudios (UPTP "Juan de Jesús Montilla"), que
es la página principal del sitio. Se muestra siempre, haya sesión o no; quien ya está dentro del
sistema tiene un acceso directo en el botón «Mi panel».

El homepage vive en `resources/views/portada.blade.php` y sus recursos en `public/portada/`
(logotipo, imágenes del carrusel, estilos y script). Es una página estática con Bootstrap por CDN,
así que no pasa por Vite.

El enlace entre los dos es doble: el homepage ofrece **«Entrar al sistema»** en la barra y en el pie,
y el sistema devuelve al homepage con **«Ir al sitio»**, además del logo de la franja institucional.

### Identidad visual

El sistema toma la paleta del homepage para que ambos se vean como una sola marca:

| | Valor |
| --- | --- |
| Rojo institucional | `#c8102e` (`--red`), oscuro `#8b0a1f`, suave `#ffe5ea` |
| Cabecera | Franja blanca con el logo y la insignia «Control de Estudios», y debajo la barra roja |
| Contraste | Texto blanco sobre la barra roja: 5.88:1, por encima del 4.5:1 que pide WCAG AA |

### Puesta en marcha

El proyecto corre sobre **Laravel Sail**. Con `up -d` ya queda todo listo: el compose aplica
las migraciones y llena la base, así que no hay pasos manuales que alguien se salte.

```bash
# 1. Preparar el .env (en Linux: cp .env.example .env)
copy .env.example .env

# 2. Sail necesita vendor/ en el equipo
composer install

# 3. Levantar (en Linux: ./vendor/bin/sail up -d)
vendor\bin\sail.bat up -d
```

El sitio queda en **http://localhost** y la base en el puerto **5432**.

Si tu `.env` se queda sin `APP_KEY`, en el primer arranque:

```bash
vendor\bin\sail.bat artisan key:generate
```

Para trabajar con recarga en caliente de Vite, en otra terminal:

```bash
vendor\bin\sail.bat npm run dev     # http://localhost:5173
```

Tests:

```bash
vendor\bin\sail.bat artisan test
```

#### Cuentas de ejemplo

Las crea `db:seed`, que corre solo al arrancar. La contraseña de todas es `password`.

| Rol | Correo |
|---|---|
| Administrador | `admin@uptp.test` |
| Analista | `analista@uptp.test` |
| Taquillero | `taquillero@uptp.test` |
| Estudiantes | `maria.perez@ejemplo.com`, `carlos.gomez@ejemplo.com`, `andreina.rojas@ejemplo.com`, `luis.fernandez@ejemplo.com` |

Son datos de desarrollo, no reales. Para una base nueva:

```bash
vendor\bin\sail.bat down -v          # borra la base y la vuelve a crear
vendor\bin\sail.bat up -d
```

#### Si algo falla

**`Connection refused` o `no se pudo resolver el host pgsql`.** El `DB_HOST` quedó en
`127.0.0.1`, que dentro del contenedor es el propio contenedor. Debe ser `pgsql`.

**El puerto 5432 ya está ocupado.** Sail publica la base ahí por defecto. Si tienes un
PostgreSQL local, agrega `FORWARD_DB_PORT=5433` a tu `.env` y ya. El 5433 es solo el
puerto del host; dentro del contenedor sigue siendo 5432 y no hay que cambiar nada más.

**Faltan tablas o datos.** Revisa que los servicios de una sola vez hayan terminado bien:

```bash
vendor\bin\sail.bat ps -a          # migrate y seed deben salir con exit 0
```

Si un PR nuevo trajo migraciones y quieres aplicarlas sin reiniciar todo:

```bash
vendor\bin\sail.bat artisan migrate --force
vendor\bin\sail.bat artisan db:seed --force
```

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
