/* Actualiza productos de la comanda sin navegar. Los formularios conservan su envío normal sin JS. */
(() => {
    'use strict';
    const pendientes = new WeakSet();
    const estados = {
        libre: ['libre', 'Libre'],
        ocupada: ['espera', 'En espera de pedido'],
        reservada: ['atendida', 'Mesa atendida'],
        pendiente_de_cierre: ['alerta', 'Alerta de atención'],
    };

    function avisar(modal, mensaje, error = false) {
        const aviso = modal.querySelector('[data-comanda-aviso]');
        aviso.hidden = false;
        aviso.textContent = mensaje;
        aviso.setAttribute('role', error ? 'alert' : 'status');
        aviso.style.cssText = `padding:12px;margin:12px 0;border-radius:8px;background:${error ? '#fce8e6' : '#e9f3e8'};color:${error ? '#8a2520' : '#245528'};`;
    }

    function actualizarMesa(datos) {
        const mesa = document.querySelector(`.mesa[data-mesa-id="${datos.id}"]`);
        if (!mesa) return;
        const [clase, etiqueta] = estados[datos.estado_visual] || estados[datos.estado] || estados.libre;
        [mesa, ...mesa.querySelectorAll('.estado, .mesa-icono, .mesa-total')].forEach(elemento => {
            elemento.classList.remove('libre', 'espera', 'atendida', 'alerta');
            elemento.classList.add(clase);
        });
        mesa.dataset.mesaEstado = etiqueta;
        mesa.dataset.inicioEspera = datos.inicio_espera;
        mesa.dataset.alertaEsperaEn = datos.alerta_espera_en;
        mesa.querySelector('.estado').textContent = etiqueta;
        mesa.querySelector('.mesa-total strong').textContent = datos.total;
        const selector = mesa.querySelector('[name="estado"]');
        if (selector) selector.value = datos.estado;
        const reloj = mesa.querySelector('[data-tiempo-pedido]');
        if (reloj) reloj.dataset.desde = datos.desde ?? '';
        if (!datos.inicio_espera) mesa.querySelector('.tiempo-espera')?.closest('.info-linea')?.remove();
        const contador = document.getElementById('cantidad-mesas-alerta');
        if (contador) contador.textContent = document.querySelectorAll('.mesas-grid .mesa.alerta').length;
        if (mesa.classList.contains('seleccionada')) mesa.click();
    }

    document.addEventListener('submit', async evento => {
        const form = evento.target;
        if (!(form instanceof HTMLFormElement)) return;
        const modal = form.closest('.modal-comanda');
        if (!modal) return;
        if (pendientes.has(modal)) {
            evento.preventDefault();
            evento.stopImmediatePropagation();
            return;
        }
        const region = form.closest('[data-comanda-detalles]');
        if (!region || !window.fetch) return;
        evento.preventDefault();
        evento.stopImmediatePropagation();
        pendientes.add(modal);
        const body = new FormData(form);
        const foco = document.activeElement;
        const focoId = foco?.id;
        const esAgregar = form.classList.contains('comanda-agregar');
        const controles = [...modal.querySelectorAll('button[type="submit"], input:not([type="hidden"]), select')];
        const anteriores = controles.map(control => control.disabled);
        const boton = evento.submitter;
        const textoBoton = boton?.innerHTML;
        controles.forEach(control => { control.disabled = true; });
        if (boton) boton.textContent = 'Guardando…';
        region.setAttribute('aria-busy', 'true');
        modal.querySelectorAll('.comanda-errores').forEach(error => error.remove());
        avisar(modal, 'Guardando…');
        let guardado = false;
        let actualizado = false;
        try {
            // Laravel reconoce _method y _token del formulario original.
            const respuesta = await fetch(form.action, {
                method: 'POST', body, credentials: 'same-origin', redirect: 'error',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const datos = await respuesta.json().catch(() => ({}));
            if (!respuesta.ok) {
                const errores = Object.values(datos.errors || {}).flat();
                const mensaje = respuesta.status === 419 || respuesta.status === 401
                    ? 'La sesión venció. Recargá la página e ingresá de nuevo.'
                    : respuesta.status === 403
                        ? 'No tenés permiso para modificar esta comanda.'
                        : errores.join(' ') || 'No se pudo guardar. Revisá la comanda antes de intentar nuevamente.';
                avisar(modal, mensaje, true);
                return;
            }
            guardado = true;
            if (typeof datos.html !== 'string' || !datos.mesa) throw new Error('Respuesta incompleta');
            region.innerHTML = datos.html;
            actualizarMesa(datos.mesa);
            // El stock también cambia en los selectores de otras comandas abiertas.
            const opciones = region.querySelector('select[name="producto_id"]');
            if (opciones) {
                document.querySelectorAll('.modal-comanda select[name="producto_id"]').forEach(select => {
                    const valor = select.value;
                    if (select !== opciones) select.innerHTML = opciones.innerHTML;
                    const opcion = [...select.options].find(item => item.value === valor && !item.disabled);
                    select.value = opcion ? valor : '';
                });
            }
            avisar(modal, datos.mensaje || 'Cambios guardados.');
            actualizado = true;
        } catch (_) {
            avisar(modal, guardado
                ? 'El cambio se guardó, pero no se pudo actualizar la vista. Recargá la página para ver el resultado.'
                : 'No se pudo confirmar el resultado. Revisá la conexión y recargá la página antes de repetir la operación.', true);
        } finally {
            pendientes.delete(modal);
            region.removeAttribute('aria-busy');
            controles.forEach((control, indice) => { control.disabled = anteriores[indice]; });
            if (boton?.isConnected) boton.innerHTML = textoBoton;
            if (modal.classList.contains('activo') && (document.activeElement === foco || document.activeElement === document.body)) {
                const destino = actualizado
                    ? (esAgregar ? region.querySelector('[name="producto_id"]') : focoId ? document.getElementById(focoId) : null)
                    : foco;
                (destino?.isConnected ? destino : modal.querySelector('[data-cerrar-modal]'))?.focus();
            }
        }
    }, true);
})();
