/* Estado de mesa desde la vista de atención del administrador. */
(() => {
    'use strict';
    if (!window.fetch) return;
    let guardando = false;
    document.addEventListener('submit', async evento => {
        const form = evento.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('.estado-form')) return;
        const mesa = form.closest('.mesa');
        if (!mesa || evento.defaultPrevented) return;
        evento.preventDefault();
        if (guardando) return;
        guardando = true;
        const body = new FormData(form);
        const controles = [...form.querySelectorAll('button, select')];
        const previos = controles.map(control => control.disabled);
        controles.forEach(control => { control.disabled = true; });
        mesa.setAttribute('aria-busy', 'true');
        let aviso = mesa.querySelector('[data-estado-aviso]');
        if (!aviso) {
            aviso = document.createElement('p');
            aviso.dataset.estadoAviso = '';
            aviso.setAttribute('aria-live', 'polite');
            mesa.append(aviso);
        }
        aviso.textContent = 'Guardando…';
        try {
            const respuesta = await fetch(form.action, {
                method: 'POST', body, credentials: 'same-origin', redirect: 'error',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const datos = await respuesta.json().catch(() => ({}));
            if (!respuesta.ok) {
    const errores = Object.values(datos.errors || {}).flat();

    if (
        respuesta.status === 422 &&
        errores.length &&
        typeof window.avisar === 'function'
    ) {
        aviso.remove();

        window.avisar(
            errores.join(' '),
            'No se puede cambiar el estado'
        );

        return;
    }

    aviso.setAttribute('role', 'alert');

    aviso.textContent = errores.join(' ') || (
        respuesta.status === 419 || respuesta.status === 401
            ? 'La sesión venció. Recargá la página para continuar.'
            : datos.message || 'No se pudo actualizar el estado.'
    );

    return;
}
            const pagina = new DOMParser().parseFromString(datos.html || '', 'text/html');
            const nueva = [...pagina.querySelectorAll('.mesa')].find(item => item.dataset.mesaId === mesa.dataset.mesaId);
            if (!nueva) throw new Error('Vista incompleta');
            const seleccionada = mesa.classList.contains('seleccionada');
            // Conservar nodos que ya tienen listeners y la referencia del reloj sincronizado.
            const reloj = mesa.querySelector('[data-tiempo-pedido]');
            const relojNuevo = nueva.querySelector('[data-tiempo-pedido]');
            if (reloj && relojNuevo) {
                reloj.dataset.desde = relojNuevo.dataset.desde;
                relojNuevo.replaceWith(reloj);
            }
            const abrir = mesa.querySelector('[data-abrir-comanda]');
            const abrirNuevo = nueva.querySelector('[data-abrir-comanda]');
            if (abrir && abrirNuevo && abrir.dataset.abrirComanda === abrirNuevo.dataset.abrirComanda) abrirNuevo.replaceWith(abrir);
            for (const atributo of [...mesa.attributes]) {
                if (atributo.name.startsWith('data-')) mesa.removeAttribute(atributo.name);
            }
            for (const atributo of [...nueva.attributes]) {
                if (atributo.name.startsWith('data-') || atributo.name === 'class') mesa.setAttribute(atributo.name, atributo.value);
            }
            nueva.querySelectorAll('script').forEach(script => script.remove());
            mesa.replaceChildren(...nueva.childNodes);
            mesa.classList.toggle('seleccionada', seleccionada);
            if (seleccionada) mesa.click();
            for (const id of ['cantidad-mesas-libres', 'cantidad-mesas-alerta']) {
                const contador = document.getElementById(id);
                const actualizado = pagina.getElementById(id);
                if (contador && actualizado) contador.textContent = actualizado.textContent;
            }
            const mensaje = datos.mensaje || 'Estado actualizado correctamente.';

if (typeof window.avisar === 'function') {
    aviso.remove();

    window.avisar(
        mensaje,
        'Estado actualizado'
    );
} else {
    aviso.setAttribute('role', 'status');
    aviso.textContent = mensaje;
    mesa.append(aviso);
}
        } catch (_) {
            aviso.setAttribute('role', 'alert');
            aviso.textContent = 'No se pudo confirmar el resultado. Recargá y comprobá el estado antes de repetir la operación.';
        } finally {
            guardando = false;
            mesa.removeAttribute('aria-busy');
            controles.forEach((control, i) => { control.disabled = previos[i]; });
        }
    });
})();
