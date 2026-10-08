/* Productos (admin): catálogo, alta, edición y avisos. */
(() => {
  'use strict';

  const config = document.getElementById('pp-config');
  const dialog = document.getElementById('dlg-producto');
  const form = document.getElementById('form-producto');
  if (!config || !dialog || !form) return;

  const el = (id) => document.getElementById(id);
  const soloDigitos = (input) =>
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '');
    });

  const AYUDA_TIPO = {
    unidad: 'Lleva control de stock: se descuenta automáticamente cada vez que se carga en una comanda.',
    preparacion: 'No lleva control de stock. Para que no se pueda cargar en comandas, dala de baja.',
  };

  const VACIO = {
    id: null,
    url: null,
    nombre: '',
    categoria: 'bebida',
    tipo: 'unidad',
    stock: '',
    precio: '',
    activo: true,
    masiva: true,
  };

  const campos = {
    metodo: form.elements._method,
    editando: form.elements._editando,
    nombre: form.elements.nombre,
    categoria: form.elements.categoria,
    tipo: form.elements.tipo,
    stock: form.elements.stock,
    precio: form.elements.precio,
    activo: form.elements.activo,
    masiva: form.elements.permite_actualizacion_masiva,
  };

  function actualizarTipo() {
    const unidad = campos.tipo.value === 'unidad';
    el('fp-stock-campo').hidden = !unidad;
    campos.stock.disabled = !unidad;
    el('fp-tipo-ayuda').textContent = AYUDA_TIPO[campos.tipo.value] || '';
  }

  function limpiarErrores() {
    form.querySelectorAll('.pp-error').forEach((n) => n.remove());
    form.querySelectorAll('.is-invalid').forEach((n) => n.classList.remove('is-invalid'));
  }

  function abrir(datos) {
    const d = { ...VACIO, ...datos };
    const editando = d.id !== null;

    form.action = editando ? d.url : config.dataset.urlStore;
    campos.metodo.value = editando ? 'PUT' : 'POST';
    campos.editando.value = editando ? d.id : '';
    campos.nombre.value = d.nombre;
    campos.categoria.value = d.categoria;
    campos.tipo.value = d.tipo;
    campos.stock.value = d.stock === null ? '' : d.stock;
    campos.precio.value = d.precio;
    campos.activo.checked = Boolean(d.activo);
    campos.masiva.checked = Boolean(d.masiva);

    el('dlg-producto-titulo').textContent = editando ? 'Editar producto' : 'Nuevo producto';
    el('dlg-producto-sub').textContent = editando
      ? 'Modificá los datos del producto'
      : 'Completá los datos para sumarlo al catálogo';
    el('fp-guardar').textContent = editando ? 'Guardar cambios' : 'Crear producto';
    el('fp-guardar').disabled = false;

    limpiarErrores();
    actualizarTipo();
    dialog.showModal();
    campos.nombre.focus();
  }

  document.querySelectorAll('[data-nuevo]').forEach((b) => b.addEventListener('click', () => abrir({})));
  document
    .querySelectorAll('[data-editar]')
    .forEach((b) => b.addEventListener('click', () => abrir(JSON.parse(b.dataset.editar))));

  form.querySelectorAll('input[name="tipo"]').forEach((r) => r.addEventListener('change', actualizarTipo));
  form.querySelectorAll('[data-cerrar]').forEach((b) => b.addEventListener('click', () => dialog.close()));
  dialog.addEventListener('click', (e) => {
    if (e.target === dialog) dialog.close();
  });

  soloDigitos(campos.precio);
  soloDigitos(campos.stock);

  form.addEventListener('submit', () => {
    el('fp-guardar').disabled = true;
  });
  window.addEventListener('pageshow', () => {
    el('fp-guardar').disabled = false;
  });

  // Si el servidor rechazó el formulario, se vuelve a abrir con los errores a la vista.
  if (config.dataset.reabrir === '1') {
    actualizarTipo();
    dialog.showModal();
  }

  // Avisos: se cierran solos a los 5 segundos o con la cruz.
  document.querySelectorAll('[data-toast]').forEach((aviso) => {
    const ocultar = () => {
      aviso.hidden = true;
    };
        setTimeout(ocultar, Number(aviso.dataset.toast) || 5000);
    const cruz = aviso.querySelector('button');
    if (cruz) cruz.addEventListener('click', ocultar);
  });
})();