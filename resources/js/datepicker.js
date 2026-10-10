/**
 * Calendario de fechas propio.
 *
 * El <input type="date"> de Chrome dibuja su calendario fuera de la pagina, asi
 * que no se puede disenar con CSS. Este archivo lo sustituye por uno con los
 * colores del proyecto, sin instalar ninguna libreria.
 *
 * Como funciona: cada input[type=date] se convierte en un campo de texto
 * visible (dd/mm/aaaa) mas un input oculto que es el que viaja al servidor con
 * el mismo nombre y el mismo valor ISO. Asi la validacion del formulario, el
 * old() y el guardado siguen siendo exactamente los de antes: si el JavaScript
 * no llegara a cargarse, el input nativo sigue siendo un campo de fecha normal.
 */

const MESES = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
];

// Las semanas en Venezuela empiezan el lunes.
const DIAS = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

// Ojo: la opcion es month/weekday, no "type". Con "type" Intl la ignora y
// devuelve la fecha entera (1/10/2026) en vez del nombre del mes.
const NOMBRES_MES = { month: 'long', timeZone: 'UTC' };
const NOMBRES_DIA = { weekday: 'long', timeZone: 'UTC' };

/** "2026-10-06" -> Date a medianoche UTC, o null si no es una fecha valida. */
function desdeIso(iso) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(iso || '')) {
        return null;
    }

    const [anio, mes, dia] = iso.split('-').map(Number);
    const fecha = new Date(Date.UTC(anio, mes - 1, dia));

    // Descarta 2026-02-31 y similares: la fecha se correria al mes siguiente.
    if (fecha.getUTCDate() !== dia || fecha.getUTCMonth() !== mes - 1) {
        return null;
    }

    return fecha;
}

function aIso(fecha) {
    const dia = String(fecha.getUTCDate()).padStart(2, '0');
    const mes = String(fecha.getUTCMonth() + 1).padStart(2, '0');

    return `${fecha.getUTCFullYear()}-${mes}-${dia}`;
}

/** Formato que ve la persona: 06/10/2026 */
function aVisible(fecha) {
    const dia = String(fecha.getUTCDate()).padStart(2, '0');
    const mes = String(fecha.getUTCMonth() + 1).padStart(2, '0');

    return `${dia}/${mes}/${fecha.getUTCFullYear()}`;
}

/** "06/10/2026" o "6-10-26" -> "2026-10-06" */
function desdeVisible(texto) {
    const partes = String(texto).trim().split(/[\/\-\.]/);

    if (partes.length !== 3) {
        return null;
    }

    let [dia, mes, anio] = partes.map((p) => p.replace(/\D/g, ''));

    if (!dia || !mes || !anio) {
        return null;
    }

    if (anio.length <= 2) {
        anio = String(2000 + Number(anio));
    }

    return desdeIso(`${anio.padStart(4, '0')}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`)
        ? `${anio.padStart(4, '0')}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`
        : null;
}

const ICONO_CALENDARIO =
    '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
    '<rect x="3" y="5" width="18" height="16" rx="3"></rect>' +
    '<path d="M3 10h18M8 3v4M16 3v4"></path></svg>';

const ICONO_FLECHA =
    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
    'stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
    '<polyline points="%puntos%"></polyline></svg>';

/** Un calendario pegado a un campo. */
class Calendario {
    constructor(campo) {
        this.campo = campo;          // input[type=text] visible
        this.oculto = campo.__fechaOculto;
        this.min = campo.min || null;
        this.max = campo.max || null;

        const valor = desdeIso(this.oculto.value);
        const hoy = new Date();
        const base = valor || new Date(Date.UTC(hoy.getFullYear(), hoy.getMonth(), 1));

        this.anio = base.getUTCFullYear();
        this.mes = base.getUTCMonth();
        this.abierta = false;
        this.diaEnfocado = valor ? valor.getUTCDate() : hoy.getDate();

        this.construir();
        this.enlazar();
    }

    construir() {
        const envoltorio = document.createElement('div');
        envoltorio.className = 'fecha';

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'fecha__disparador';
        boton.setAttribute('aria-label', 'Abrir el calendario');
        boton.innerHTML = ICONO_CALENDARIO;

        const panel = document.createElement('div');
        panel.className = 'fecha__panel';
        panel.hidden = true;
        panel.innerHTML = `
            <div class="fecha__cabecera">
                <button type="button" class="fecha__nav fecha__nav--anio" data-anio="-1" aria-label="Año anterior">«</button>
                <button type="button" class="fecha__nav" data-mes="-1" aria-label="Mes anterior">${ICONO_FLECHA.replace('%puntos%', '15 18 9 12 3')}</button>
                <span class="fecha__titulo" role="status"></span>
                <button type="button" class="fecha__nav" data-mes="1" aria-label="Mes siguiente">${ICONO_FLECHA.replace('%puntos%', '9 6 15 12 21')}</button>
                <button type="button" class="fecha__nav fecha__nav--anio" data-anio="1" aria-label="Año siguiente">»</button>
            </div>
            <div class="fecha__dias-semana">${DIAS.map((d) => `<span>${d}</span>`).join('')}</div>
            <div class="fecha__dias"></div>
            <div class="fecha__pie">
                <button type="button" class="fecha__accion" data-hoy>Hoy</button>
                <button type="button" class="fecha__accion" data-borrar>Borrar</button>
            </div>`;

        this.campo.parentNode.insertBefore(envoltorio, this.campo);
        envoltorio.appendChild(this.campo);
        envoltorio.appendChild(boton);
        envoltorio.appendChild(panel);

        this.envoltorio = envoltorio;
        this.panel = panel;
        this.titulo = panel.querySelector('.fecha__titulo');
        this.rejilla = panel.querySelector('.fecha__dias');
        this.botonDisparador = boton;
    }

    enlazar() {
        this.botonDisparador.addEventListener('click', () => this.alternar());

        this.campo.addEventListener('focus', () => this.abrir());
        this.campo.addEventListener('blur', () => {
            // Un clic dentro del calendario no debe cerrar el campo.
            setTimeout(() => {
                if (!this.envoltorio.contains(document.activeElement)) {
                    this.cerrar();
                }
            }, 120);
        });

        this.campo.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.confirmarTexto();
            }
        });

        this.panel.addEventListener('click', (e) => {
            const nav = e.target.closest('[data-mes]');
            const anio = e.target.closest('[data-anio]');

            if (nav) {
                this.moverMes(Number(nav.dataset.mes));
            } else if (anio) {
                this.moverMes(Number(anio.dataset.anio) * 12);
            } else if (e.target.closest('[data-hoy]')) {
                this.elegir(new Date());
            } else if (e.target.closest('[data-borrar]')) {
                this.elegir(null);
            } else {
                const dia = e.target.closest('[data-dia]');
                if (dia) {
                    this.elegir(new Date(Date.UTC(this.anio, this.mes, Number(dia.dataset.dia))));
                }
            }
        });

        this.rejilla.addEventListener('keydown', (e) => this.teclado(e));

        document.addEventListener('click', (e) => {
            if (this.abierta && !this.envoltorio.contains(e.target)) {
                this.cerrar();
            }
        });
    }

    alternar() {
        this.abierta ? this.cerrar() : this.abrir();
    }

    abrir() {
        const valor = desdeIso(this.oculto.value);

        if (valor) {
            this.anio = valor.getUTCFullYear();
            this.mes = valor.getUTCMonth();
        }

        this.pintar();
        this.panel.hidden = false;
        this.envoltorio.classList.add('fecha--abierta');
        this.abierta = true;
    }

    cerrar() {
        this.panel.hidden = true;
        this.envoltorio.classList.remove('fecha--abierta');
        this.abierta = false;
    }

    moverMes(cantidad) {
        const total = this.anio * 12 + this.mes + cantidad;
        this.anio = Math.floor(total / 12);
        this.mes = ((total % 12) + 12) % 12;
        this.pintar();
    }

    /** Dibuja la rejilla del mes. */
    pintar() {
        const nombre = new Intl.DateTimeFormat('es-VE', NOMBRES_MES)
            .format(new Date(Date.UTC(this.anio, this.mes, 1)));
        const capital = nombre.charAt(0).toUpperCase() + nombre.slice(1);
        this.titulo.textContent = `${capital} ${this.anio}`;

        const primero = new Date(Date.UTC(this.anio, this.mes, 1));
        // getUTCDay da 0 para domingo; la semana empieza en lunes.
        const desplazamiento = (primero.getUTCDay() + 6) % 7;
        const totalDias = new Date(Date.UTC(this.anio, this.mes + 1, 0)).getUTCDate();

        const hoy = new Date();
        const isoHoy = aIso(new Date(Date.UTC(hoy.getFullYear(), hoy.getMonth(), hoy.getDate())));
        const isoElegido = this.oculto.value;

        let html = '';

        for (let i = 0; i < desplazamiento; i++) {
            html += '<span class="fecha__dia fecha__dia--vacio"></span>';
        }

        for (let dia = 1; dia <= totalDias; dia++) {
            const fecha = new Date(Date.UTC(this.anio, this.mes, dia));
            const iso = aIso(fecha);
            const bloqueada = (this.min && iso < this.min) || (this.max && iso > this.max);
            const clases = ['fecha__dia'];

            if (bloqueada) clases.push('fecha__dia--bloqueada');
            if (iso === isoHoy) clases.push('fecha__dia--hoy');
            if (iso === isoElegido) clases.push('fecha__dia--elegida');

            const etiqueta = new Intl.DateTimeFormat('es-VE', NOMBRES_DIA)
                .format(fecha);
            const completo = `${dia} de ${nombre} de ${this.anio}`;

            html += `<button type="button" class="${clases.join(' ')}" data-dia="${dia}"`
                + ` aria-label="${completo}"${bloqueada ? ' disabled' : ''}`
                + ` tabindex="-1">${dia}</button>`;
        }

        this.rejilla.innerHTML = html;

        const elegido = this.rejilla.querySelector('.fecha__dia--elegida')
            || this.rejilla.querySelector('.fecha__dia:not(.fecha__dia--bloqueada)');

        if (elegido) {
            elegido.removeAttribute('tabindex');
        }
    }

    elegir(fecha) {
        const iso = fecha ? aIso(fecha) : '';
        this.oculto.value = iso;
        this.campo.value = fecha ? aVisible(fecha) : '';
        this.campo.classList.toggle('fecha__campo--vacio', !fecha);

        // El formulario y cualquier script necesitan enter en la fecha real.
        this.oculto.dispatchEvent(new Event('change', { bubbles: true }));
        this.campo.dispatchEvent(new Event('change', { bubbles: true }));

        this.pintar();
        this.cerrar();
        this.campo.focus();
    }

    /** Acepta lo que la persona escriba y lo normaliza. */
    confirmarTexto() {
        const iso = desdeVisible(this.campo.value);

        if (!iso) {
            this.campo.classList.add('fecha__campo--error');
            this.campo.setCustomValidity('Escribe la fecha como dia/mes/año, por ejemplo 06/10/2026.');

            return;
        }

        if ((this.min && iso < this.min) || (this.max && iso > this.max)) {
            this.campo.classList.add('fecha__campo--error');
            this.campo.setCustomValidity('Esa fecha esta fuera del rango permitido.');

            return;
        }

        this.campo.classList.remove('fecha__campo--error');
        this.campo.setCustomValidity('');

        const fecha = desdeIso(iso);
        this.anio = fecha.getUTCFullYear();
        this.mes = fecha.getUTCMonth();
        this.oculto.value = iso;
        this.campo.value = aVisible(fecha);
        this.oculto.dispatchEvent(new Event('change', { bubbles: true }));

        this.cerrar();
    }

    teclado(e) {
        const teclas = {
            ArrowLeft: -1,
            ArrowRight: 1,
            ArrowUp: -7,
            ArrowDown: 7,
        };

        if (e.key in teclas) {
            e.preventDefault();
            const actual = this.rejilla.querySelector('[tabindex="0"]')
                || this.rejilla.querySelector('.fecha__dia:not([disabled])');

            if (!actual) return;

            const todas = Array.from(this.rejilla.querySelectorAll('.fecha__dia:not(.fecha__dia--vacio)'));
            const i = todas.indexOf(actual) + teclas[e.key];

            if (i >= 0 && i < todas.length) {
                actual.setAttribute('tabindex', '-1');
                todas[i].setAttribute('tabindex', '0');
                todas[i].focus();
            }
        } else if (e.key === 'Escape') {
            this.cerrar();
            this.campo.focus();
        } else if (e.key === 'PageUp' || e.key === 'PageDown') {
            e.preventDefault();
            this.moverMes(e.key === 'PageUp' ? -1 : 1);
        }
    }
}

/** Convierte los input[type=date] que aun no tengan calendario propio. */
function enhancing() {
    document.querySelectorAll('input[type="date"]').forEach((original) => {
        if (original.dataset.fechaListo === '1') {
            return;
        }

        original.dataset.fechaListo = '1';

        const oculto = document.createElement('input');
        oculto.type = 'hidden';
        oculto.name = original.name;
        oculto.value = original.value;

        if (original.required) {
            // required no sirve en un input oculto: el navegador no lo enfoca.
            oculto.dataset.requerido = '1';
        }

        original.__fechaOculto = oculto;
        original.parentNode.insertBefore(oculto, original);

        const valor = desdeIso(original.value);
        original.type = 'text';
        original.name = `__fecha_visible_${original.name}`;
        original.removeAttribute('min');
        original.removeAttribute('max');
        original.setAttribute('inputmode', 'numeric');
        original.setAttribute('autocomplete', 'off');
        original.setAttribute('placeholder', 'dd/mm/aaaa');
        original.classList.add('fecha__campo');

        if (valor) {
            original.value = aVisible(valor);
        }

        new Calendario(original);
    });
}

function iniciar() {
    enhancing();

    // Los formularios pueden añadir campos despues (por ejemplo al abrir un
    // modal), asi que se revisa tambien cuando cambia el foco.
    document.addEventListener('focusin', enhancing);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}