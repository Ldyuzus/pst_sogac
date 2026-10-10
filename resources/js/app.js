import '../css/app.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// El calendario del navegador no se puede disenar con CSS, asi que se sustituye
// por uno propio con los colores del proyecto. Mejora de forma progresiva: si
// este archivo no carga, los campos de fecha siguen siendo nativos.
import './datepicker.js';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

/*
 * La aplicación se renderiza con Blade: ninguna vista monta el div #app que
 * necesita Inertia. Aun así createInertiaApp() se ejecutaba en todas las
 * pantallas y fallaba con "Cannot read properties of null (reading
 * 'component')", dejando un error en la consola en cada página.
 *
 * El guardia mantiene el arranque intacto para el día que exista una respuesta
 * de Inertia de verdad, y hoy solo evita el error.
 */
if (document.getElementById('app')?.dataset.inertia !== undefined) {
    createInertiaApp({
        // Aquí le decimos que busque los archivos .vue dentro de la carpeta resources/js/pages/
        resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
        setup({ el, App, props, plugin }) {
            createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el);
        },
    });
}