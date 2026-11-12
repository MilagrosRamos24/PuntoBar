/* Mejora progresiva del administrador. Laravel sigue procesando y validando los formularios. */
(() => {
    'use strict';
    if (window.PuntoBarAdmin || !window.fetch || !window.DOMParser) return;
    const selector = '[data-admin-fluida]';
    const inicial = document.querySelector(selector);
    if (!inicial) return;
    const modulo = inicial.dataset.adminFluida;
    const coincidencia = location.pathname.match(/^(.*\/admin\/)(mesas|mozos|productos|notificaciones|descuento|comandas)(?:\/|$)/);
    if (!coincidencia || coincidencia[2] !== modulo) return;
    const prefijo = coincidencia[1] + modulo;
    let ocupado = false;
    let pendienteHistorial = null;

    function pertenece(url) {
        return url.origin === location.origin &&
            (url.pathname === prefijo || url.pathname.startsWith(prefijo + '/'));
    }

    function raiz() { return document.querySelector(selector); }
    function aviso(texto, error = false, form = null) {
        const contenedor = form?.closest('dialog[open]') || raiz();
        if (!contenedor) return;
        let panel = contenedor.querySelector('[data-admin-aviso]');
        if (!panel) {
            panel = document.createElement('div');
            panel.dataset.adminAviso = '';
            panel.tabIndex = -1;
            panel.setAttribute('aria-live', 'polite');
            contenedor.prepend(panel);
        }
        panel.textContent = texto;
        panel.setAttribute('role', error ? 'alert' : 'status');
        panel.style.cssText = 'padding:12px 16px;margin-bottom:16px;border-radius:8px;' +
            (error ? 'background:#fce8e6;color:#842029;' : 'background:#e9f3e8;color:#245528;');
        if (error) panel.focus({ preventScroll: true });
    }

    async function actualizar(url, opciones, form = null, cambiarHistorial = true) {
        if (ocupado) return;
        ocupado = true;
        const anterior = raiz();
        const posicion = { x: window.scrollX, y: window.scrollY };
        const marcadas = [...anterior.querySelectorAll('.pn-elegir:checked')].map(input => input.value);
        const controles = [...anterior.querySelectorAll('button, input, select, textarea')];
        const deshabilitados = controles.map(control => control.disabled);
        const botones = form ? [...form.querySelectorAll('button[type="submit"], button:not([type])')] : [];
        const textos = botones.map(boton => boton.innerHTML);
        anterior.setAttribute('aria-busy', 'true');
        aviso(form ? 'Guardando…' : 'Cargando…', false, form);
        controles.forEach(control => { control.disabled = true; });
        botones.forEach(boton => { boton.textContent = 'Guardando…'; });
        try {
            const respuesta = await fetch(url.href, {
                credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                ...opciones,
            });
            if (!respuesta.ok) {
                const texto = respuesta.status === 419 || respuesta.status === 401
                    ? 'La sesión venció. Recargá la página e ingresá nuevamente.'
                    : respuesta.status === 403
                        ? 'No tenés permiso para realizar esta acción.'
                        : 'No se pudo completar la operación. Recargá y comprobá el resultado antes de repetirla.';
                aviso(texto, true, form);
                return;
            }
            const destino = new URL(respuesta.url || url.href, location.href);
            if (!pertenece(destino)) {
                aviso('La sesión o la pantalla cambió. Recargá la página para continuar.', true, form);
                return;
            }
            const pagina = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
            const nuevo = pagina.querySelector(selector);
            if (!nuevo || nuevo.dataset.adminFluida !== modulo) {
                aviso('No se pudo actualizar la vista. Recargá y comprobá el resultado antes de repetir la operación.', true, form);
                return;
            }
            // No ejecutar scripts de las respuestas: los módulos tienen inicializadores explícitos.
            nuevo.querySelectorAll('script').forEach(script => script.remove());
            if (pendienteHistorial) return;
            document.dispatchEvent(new CustomEvent('pb:antes-contenido'));
            anterior.querySelectorAll('dialog[open]').forEach(dialogo => dialogo.close());
            anterior.replaceWith(document.importNode(nuevo, true));
            document.title = pagina.title || document.title;
            const titulo = document.querySelector('.pb-admin-heading h1');
            const tituloNuevo = pagina.querySelector('.pb-admin-heading h1');
            if (titulo && tituloNuevo) titulo.textContent = tituloNuevo.textContent;
            // Actualizar el contador de notificaciones también cuando está fuera del contenido.
            const campanas = [...document.querySelectorAll('.pn-campana')];
            const campanasNuevas = [...pagina.querySelectorAll('.pn-campana')];
            campanas.forEach((campana, i) => {
                const nueva = campanasNuevas[i];
                if (nueva) campana.replaceWith(document.importNode(nueva, true));
            });
            if (cambiarHistorial && destino.href !== location.href) {
                history.pushState({ puntoBarAdmin: true }, '', destino.href);
            }
            document.dispatchEvent(new CustomEvent('pb:contenido'));
            const actual = raiz();
            actual.querySelectorAll('.pn-elegir').forEach(input => { input.checked = marcadas.includes(input.value); });
            actual.querySelector('.pn-elegir')?.dispatchEvent(new Event('change', { bubbles: true }));
            const dialogoAbierto = actual.querySelector('dialog[open]');
            if (!dialogoAbierto) {
                const error = actual.querySelector('[role="alert"], .errores');
                const foco = error || actual;
                if (error) error.tabIndex = -1;
                foco.focus({ preventScroll: true });
            }
            window.scrollTo(posicion.x, posicion.y);
        } catch (_) {
            aviso(form
                ? 'No se pudo confirmar el resultado. Revisá la conexión y recargá antes de repetir la operación.'
                : 'No se pudo cargar el contenido. Revisá tu conexión e intentá nuevamente.', true, form);
        } finally {
            ocupado = false;
            anterior.removeAttribute('aria-busy');
            controles.forEach((control, i) => { control.disabled = deshabilitados[i]; });
            botones.forEach((boton, i) => { boton.innerHTML = textos[i]; });
            if (pendienteHistorial) {
                const destino = pendienteHistorial;
                pendienteHistorial = null;
                actualizar(destino, { method: 'GET' }, null, false);
            }
        }
    }

    function enviar(form, boton = null) {
        if (!(form instanceof HTMLFormElement) || !form.closest(selector) || form.enctype === 'multipart/form-data') return false;
        const url = new URL(boton?.hasAttribute('formaction') ? boton.formAction : form.action || location.href, location.href);
        if (!pertenece(url) || form.target && form.target !== '_self') return false;
        if (ocupado) return true;
        if (!form.checkValidity()) { form.reportValidity(); return true; }
        const datos = new FormData(form);
        if (boton?.name) datos.append(boton.name, boton.value);
        const metodo = (form.getAttribute('method') || 'GET').toUpperCase();
        if (metodo === 'DIALOG') return false;
        if (metodo === 'GET') {
            url.search = new URLSearchParams(datos).toString();
            actualizar(url, { method: 'GET' });
        } else {
            actualizar(url, { method: 'POST', body: datos }, form);
        }
        return true;
    }

    function iniciarMesa() {
        if (modulo !== 'mesas') return;
        const estado = raiz()?.querySelector('#estado');
        const mozo = raiz()?.querySelector('#mozo_id');
        const personas = raiz()?.querySelector('#cantidad_personas');
        if (!estado || !mozo || !personas || estado.dataset.requisitosIniciados) return;
        estado.dataset.requisitosIniciados = '1';
        const actualizarRequisitos = () => {
            const estadosConMozo = JSON.parse(estado.dataset.estadosConMozo || '["ocupada","pendiente_de_cierre"]');
            const enEspera = estadosConMozo.includes(estado.value);
            mozo.required = enEspera;
            personas.min = enEspera ? '1' : '0';
        };
        estado.addEventListener('change', actualizarRequisitos);
        actualizarRequisitos();
    }
    iniciarMesa();
    document.addEventListener('pb:contenido', iniciarMesa);

    window.PuntoBarAdmin = { enviar };
    // En burbujeo: respeta validaciones y confirmaciones que ya frenaron el formulario.
    document.addEventListener('submit', evento => {
        if (!evento.defaultPrevented && enviar(evento.target, evento.submitter)) evento.preventDefault();
    });
    document.addEventListener('click', evento => {
        const enlace = evento.target.closest('a[href]');
        if (!enlace || !enlace.closest(selector) || evento.defaultPrevented || evento.button !== 0 ||
            evento.ctrlKey || evento.metaKey || evento.shiftKey || evento.altKey || enlace.hasAttribute('download') ||
            enlace.target && enlace.target !== '_self') return;
        const url = new URL(enlace.href, location.href);
        if (!pertenece(url) || url.hash) return;
        evento.preventDefault();
        actualizar(url, { method: 'GET' });
    });
    window.addEventListener('popstate', () => {
        const url = new URL(location.href);
        if (!pertenece(url)) return;
        if (ocupado) pendienteHistorial = url;
        else actualizar(url, { method: 'GET' }, null, false);
    });
})();
