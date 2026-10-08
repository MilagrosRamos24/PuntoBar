/* Productos (admin): actualización masiva de precios.
   La cuenta la hace el servidor: la vista previa y el "aplicar" usan el mismo servicio,
   así que lo que se ve en pantalla es exactamente lo que se guarda. */
(() => {
  'use strict';

  const config = document.getElementById('pp-config');
  const dialog = document.getElementById('dlg-masiva');
  const form = document.getElementById('form-masiva');
  const confirmar = document.getElementById('dlg-confirmar');
  if (!config || !dialog || !form || !confirmar) return;

  const el = (id) => document.getElementById(id);
  const items = Array.from(document.querySelectorAll('#m-lista .pp-check'));
  const checkbox = (item) => item.querySelector('input');
  const valor = form.elements.valor;

  const REGLA = {
    porcentaje: /^\d{1,4}([.,]\d{1,2})?$/,
    monto: /^\d{1,9}$/,
  };

  const AYUDA_LISTA = {
    todos: 'Desmarcá los productos que querés dejar afuera de la actualización.',
    categoria: 'Se muestran los productos de la categoría. Desmarcá los que no querés modificar.',
    seleccionados: 'Marcá uno por uno los productos que querés modificar.',
  };

  const manual = new Set(); // ids marcados a mano en el modo "productos seleccionados"
  let ultimo = { cantidad: 0, aplicable: false };
  let temporizador = null;
  let numeroPedido = 0;
  let cancelar = null;

  const leer = () => ({
    dir: Number(form.elements.direccion.value),
    tipo: form.elements.tipo.value,
    alcance: form.elements.alcance.value,
    categoria: form.elements.categoria.value,
    valor: valor.value.trim(),
  });

  const pesos = (texto) => {
    const [enteros, centavos = '00'] = String(texto).split('.');
    const miles = enteros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return '$ ' + miles + (centavos !== '00' ? ',' + centavos : '');
  };

  const valorValido = ({ tipo, valor: v }) => REGLA[tipo].test(v) && Number(v.replace(',', '.')) > 0;

  const ajusteTexto = ({ dir, tipo, valor: v }) => {
    const signo = dir > 0 ? '+' : '−';
    return tipo === 'porcentaje' ? signo + v.replace('.', ',') + '%' : signo + pesos(v);
  };

  const textoContador = (n) =>
    n === 0 ? 'Ningún producto será modificado' : n === 1 ? '1 producto será modificado' : n + ' productos serán modificados';

  const aviso = (texto) => {
    el('m-aviso').textContent = texto || '';
  };
  const mensaje = (texto) => {
    const p = el('m-mensaje');
    p.hidden = !texto;
    p.textContent = texto || '';
  };
  const permitirAplicar = (si) => {
    el('m-aplicar').disabled = !si;
  };

  function actualizarRotulos() {
    const { dir, tipo } = leer();
    const porcentaje = tipo === 'porcentaje';
    el('m-valor-label').textContent = porcentaje
      ? dir > 0 ? 'Porcentaje de aumento' : 'Porcentaje de descuento'
      : dir > 0 ? 'Monto a sumar' : 'Monto a restar';
    valor.placeholder = porcentaje ? '15' : '500';
    el('m-valor-wrap').className = 'pp-valor ' + (porcentaje ? 'is-porcentaje' : 'is-monto');
  }

  // Qué productos se ven y cuáles quedan marcados según el alcance elegido.
  function aplicarAlcance() {
    const { alcance, categoria } = leer();
    el('m-categorias').hidden = alcance !== 'categoria';
    el('m-lista-ayuda').textContent = AYUDA_LISTA[alcance];

    items.forEach((item) => {
      const visible = alcance !== 'categoria' || item.dataset.categoria === categoria;
      const cb = checkbox(item);
      item.hidden = !visible;
      if (item.dataset.bloqueado === '1') {
        cb.checked = false; // el bloqueo manda: nunca se marca
        return;
      }
      cb.checked = visible && (alcance === 'seleccionados' ? manual.has(cb.value) : true);
    });
  }

  function seleccion() {
    const visibles = items.filter((i) => !i.hidden && i.dataset.bloqueado !== '1');
    return {
      marcados: visibles.filter((i) => checkbox(i).checked),
      desmarcados: visibles.filter((i) => !checkbox(i).checked),
    };
  }

  function cuerpo(estado, sel) {
    const datos = { alcance: estado.alcance, tipo: estado.tipo, direccion: estado.dir, valor: estado.valor };
    if (estado.alcance === 'categoria') datos.categoria = estado.categoria;
    if (estado.alcance === 'seleccionados') datos.ids = sel.marcados.map((i) => Number(i.dataset.id));
    else datos.excluidos = sel.desmarcados.map((i) => Number(i.dataset.id));
    return datos;
  }

  function celda(texto, clase) {
    const td = document.createElement('td');
    td.textContent = texto;
    if (clase) td.className = clase;
    return td;
  }

  function dibujar(filas) {
    el('m-filas').replaceChildren(
      ...filas.map((f) => {
        const tr = document.createElement('tr');
        tr.append(
          celda(f.nombre, 'pp-prev-nombre'),
          celda(f.actual, 'pp-right pp-muted'),
          celda(f.ajuste, 'pp-right ' + f.ajusteClase),
          celda(f.nuevo, 'pp-right pp-prev-nuevo ' + f.nuevoClase)
        );
        return tr;
      })
    );
  }

  function actualizar() {
    clearTimeout(temporizador);
    numeroPedido += 1; // descarta respuestas de pedidos anteriores
    if (cancelar) cancelar.abort();

    const estado = leer();
    const sel = seleccion();
    const n = sel.marcados.length;

    ultimo = { cantidad: n, aplicable: false };
    el('m-contador').textContent = textoContador(n);
    el('m-contador').classList.toggle('is-empty', n === 0);
    permitirAplicar(false);
    aviso('');

    if (n === 0) {
      dibujar([]);
      mensaje('No hay productos para modificar con la selección actual.');
      aviso('Marcá al menos un producto para continuar.');
      return;
    }

    if (!valorValido(estado)) {
      dibujar(
        sel.marcados.map((i) => ({
          nombre: i.dataset.nombre,
          actual: pesos(i.dataset.precio),
          ajuste: '—',
          ajusteClase: '',
          nuevo: '—',
          nuevoClase: 'pp-muted',
        }))
      );
      mensaje('Ingresá un valor para calcular los nuevos precios.');
      return;
    }

    mensaje('');
    temporizador = setTimeout(() => pedirVistaPrevia(estado, sel), 250);
  }

  async function pedirVistaPrevia(estado, sel) {
    const numero = numeroPedido;
    cancelar = new AbortController();

    try {
      const respuesta = await fetch(config.dataset.urlVistaPrevia, {
        method: 'POST',
        signal: cancelar.signal,
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': form.elements._token.value,
        },
        body: JSON.stringify(cuerpo(estado, sel)),
      });
      const datos = await respuesta.json().catch(() => ({}));
      if (numero !== numeroPedido) return;

      if (respuesta.status === 422) {
        const primero = Object.values(datos.errors || {}).flat()[0];
        aviso(primero || 'Revisá los datos ingresados.');
        return;
      }
      if (!respuesta.ok || !Array.isArray(datos.filas)) {
        aviso('No se pudo calcular la vista previa. Recargá la página e intentá de nuevo.');
        return;
      }

      dibujar(
        datos.filas.map((f) => ({
          nombre: f.nombre,
          actual: pesos(f.precio_actual),
          ajuste: ajusteTexto(estado),
          ajusteClase: estado.dir > 0 ? 'pp-ajuste-up' : 'pp-ajuste-down',
          nuevo: f.valido ? pesos(f.precio_nuevo) : 'Inválido',
          nuevoClase: f.valido ? '' : 'is-invalid',
        }))
      );
      el('m-contador').textContent = textoContador(datos.cantidad);
      el('m-contador').classList.toggle('is-empty', datos.cantidad === 0);

      ultimo = { cantidad: datos.cantidad, aplicable: datos.cantidad > 0 && !datos.hay_invalidos };
      if (datos.hay_invalidos) aviso('Con este valor, algún producto quedaría con precio $ 0 o negativo.');
      permitirAplicar(ultimo.aplicable);
    } catch (error) {
      if (error.name === 'AbortError') return;
      if (numero !== numeroPedido) return;
      aviso('No se pudo calcular la vista previa. Revisá tu conexión e intentá de nuevo.');
    }
  }

  function sanearValor() {
    let v = valor.value;
    if (form.elements.tipo.value === 'porcentaje') {
      v = v.replace(/[^\d.,]/g, '').replace(/\./g, ',');
      const i = v.indexOf(',');
      if (i !== -1) v = v.slice(0, i + 1) + v.slice(i + 1).replace(/,/g, '').slice(0, 2);
      v = v.slice(0, 7);
    } else {
      v = v.replace(/\D/g, '').slice(0, 9);
    }
    valor.value = v;
  }

  function abrir() {
    form.reset(); // vuelve a Aumentar, Porcentaje, Todos los productos y valor vacío
    manual.clear();
    actualizarRotulos();
    aplicarAlcance();
    actualizar();
    dialog.showModal();
    valor.focus();
  }

  document.querySelectorAll('[data-abrir-masiva]').forEach((b) => b.addEventListener('click', abrir));
  form.querySelectorAll('[data-cerrar-masiva]').forEach((b) => b.addEventListener('click', () => dialog.close()));

  valor.addEventListener('input', () => {
    sanearValor();
    actualizar();
  });

  form.addEventListener('change', (e) => {
    const campo = e.target;
    if (campo === valor) return;

    if (campo.name === 'tipo') valor.value = '';
    if (campo.name === 'tipo' || campo.name === 'direccion') actualizarRotulos();
    if (campo.name === 'alcance' || campo.name === 'categoria') aplicarAlcance();
    if (campo.closest('#m-lista') && leer().alcance === 'seleccionados') {
      if (campo.checked) manual.add(campo.value);
      else manual.delete(campo.value);
    }
    actualizar();
  });

  // Confirmación antes de aplicar
  el('m-aplicar').addEventListener('click', () => {
    if (!ultimo.aplicable) return;
    const e = leer();
    const n = ultimo.cantidad;
    el('dlg-confirmar-titulo').textContent =
      '¿Confirmás ' + (e.dir > 0 ? 'el aumento' : 'la baja') + ' de precios para ' + n + ' ' + (n === 1 ? 'producto' : 'productos') + '?';
    el('dlg-confirmar-detalle').textContent =
      (e.dir > 0 ? 'Aumento' : 'Baja') + ' de ' + ajusteTexto(e).slice(1) +
      '. Solo se modifican los precios: el stock y el estado de los productos no cambian.';
    confirmar.showModal();
  });

  el('c-volver').addEventListener('click', () => confirmar.close());

  el('c-confirmar').addEventListener('click', () => {
    const e = leer();
    const sel = seleccion();

    form.querySelectorAll('input[data-generado]').forEach((n) => n.remove());
    const agregar = (nombre, v) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = nombre;
      input.value = v;
      input.dataset.generado = '1';
      form.append(input);
    };
    if (e.alcance === 'seleccionados') sel.marcados.forEach((i) => agregar('ids[]', i.dataset.id));
    else sel.desmarcados.forEach((i) => agregar('excluidos[]', i.dataset.id));

    el('c-confirmar').disabled = true;
    form.submit();
  });

  window.addEventListener('pageshow', () => {
    el('c-confirmar').disabled = false;
  });
})();