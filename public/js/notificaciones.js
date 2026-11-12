(() => {
function iniciar() {
    const formulario = document.getElementById('pn-eliminar');

    if (!formulario) return;
    if (formulario.dataset.pnIniciada) return;
    formulario.dataset.pnIniciada = '1';

    const todas = document.getElementById('pn-todas');
    const casillas = Array.from(document.querySelectorAll('.pn-elegir'));
    const boton = document.getElementById('pn-borrar');
    const alcance = document.getElementById('pn-alcance');
    const conteo = document.getElementById('pn-conteo');
    const total = Number(boton.dataset.total);

    function actualizar() {
        const cantidad = casillas.filter(c => c.checked).length;

        todas.checked = casillas.length > 0
            && cantidad === casillas.length;

        todas.indeterminate = cantidad > 0
            && cantidad < casillas.length;

        alcance.value = cantidad > 0 ? 'seleccionadas' : 'todas';

        boton.textContent = cantidad > 0
            ? 'Eliminar seleccionadas (' + cantidad + ')'
            : 'Eliminar todas (' + total + ')';

        if (conteo) {
            conteo.textContent = cantidad + ' seleccionadas';
        }
    }

    todas.addEventListener('change', () => {
        casillas.forEach(c => {
            c.checked = todas.checked;
        });

        actualizar();
    });

    casillas.forEach(c => {
        c.addEventListener('change', actualizar);
    });

    formulario.addEventListener('submit', evento => {
        actualizar();

        const cantidad = casillas.filter(c => c.checked).length;

        const mensaje = cantidad > 0
            ? '¿Eliminar las ' + cantidad
                + ' notificaciones seleccionadas? Esta acción no se puede deshacer.'
            : '¿Eliminar TODAS las ' + total
                + ' notificaciones, incluidas las de otras páginas? '
                + 'Esta acción no se puede deshacer.';

        if (!window.confirm(mensaje)) {
            evento.preventDefault();
        }
    });

    actualizar();
}
iniciar();
document.addEventListener('pb:contenido', iniciar);
})();
