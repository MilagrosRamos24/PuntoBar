<section class="ay-seccion" id="panel">
    <h2>Panel de mesas</h2>
    <p>Es la primera pantalla después de ingresar. Arriba muestra tres contadores: cantidad de mesas, mesas libres y alertas de atención (las mesas que esperan demasiado). Debajo hay una tarjeta por mesa, con su estado, el mozo asignado, las personas y el total.</p>
    <p>En cada tarjeta podés:</p>
    <ul>
        <li><strong>Ver com<li><strong>Ver comanda:</strong> ver y modificar la comanda abierta de la mesa. Si todavía no tiene comanda, el botón dice «Abrir comanda».</li>anda:</strong> consultar lo que consume la mesa.</li>
        <li><strong>Estado de la mesa:</strong> elegir el nuevo estado en el desplegable y tocar <strong>Actualizar</strong>.</li>
        <li><strong>Consultar</strong> y <strong>Editar:</strong> ver o modificar los datos de la mesa.</li>
    </ul>
    <p>El menú de arriba lleva a Mesas, Mozos, Productos, Comandas, Descuento, Ayuda y a la campana de notificaciones. Abajo hay una barra fija con <strong>Ver comanda</strong>, <strong>Cerrar comanda</strong> y <strong>Salir</strong>, que actúan sobre la mesa seleccionada. El botón El botón <strong>Administrar</strong> despliega tres opciones: <strong>Agregar mesa</strong>, <strong>Gestionar mesas</strong> y <strong>Gestionar mozos</strong>. abre la gestión de mesas.</p>
</section>

<section class="ay-seccion" id="gestion-mesas">
    <h2>Gestionar mesas</h2>
    <p>Desde Desde <strong>Administrar</strong>, en <strong>Gestionar mesas</strong>, se abre la lista de mesas se abre la lista de mesas, con su estado, el mozo asignado y la cantidad de personas.</p>
    <ol>
        <li>Para crear una mesa, tocá <strong>+ Nueva mesa</strong>.</li>
        <li>Completá los datos: número, estado, mozo asignado y cantidad de personas.</li>
        <li>Guardá.</li>
    </ol>
    <p>Para cambiar una mesa existente usá <strong>Editar</strong>. <strong>Consultar</strong> muestra sus datos sin modificarlos. <strong>Eliminar</strong> pide confirmación y no se puede deshacer. El sistema rechaza datos inválidos, como un número de mesa repetido o una cantidad de personas negativa.</p>
</section>

<section class="ay-seccion" id="mozos">
    <h2>Gestionar mozos</h2>
    <p>El menú <strong>Mozos</strong> muestra la lista de mozos. Desde ahí podés crear, editar, dar de baja y reactivar.</p>
    <p>Para crear un mozo:</p>
    <ol>
        <li>Tocá <strong>Nuevo mozo</strong>.</li>
        <li>Completá <strong>Nombre y apellido</strong>, <strong>DNI (usuario de acceso)</strong>, <strong>Correo electrónico</strong>, <strong>Contraseña</strong> (mínimo 8 caracteres) y <strong>Confirmar contraseña</strong>.</li>
        <li>Tocá <strong>Crear mozo</strong>. La cuenta queda activa.</li>
    </ol>
    <p>El DNI identifica la cuenta y no se puede repetir. El mozo no lo escribe al ingresar: elige su nombre en la lista y el sistema usa el DNI por detrás. Conviene no usar el DNI como contraseña.</p>
    <p>Un mozo dado de baja deja de aparecer en la lista de ingreso. Si vuelve a trabajar, reactivalo desde el listado.</p>
</section>

<section class="ay-seccion" id="productos-stock">
    <h2>Productos y stock</h2>
    <p>El menú <strong>Productos</strong> muestra el catálogo con el precio, el stock y el estado de cada producto. Se puede filtrar por categoría (Todos, Bebidas, Comidas y Postres), y cada filtro indica cuántos productos tiene.</p>
    <p>En la tabla, cada producto muestra:</p>
    <ul>
        <li>Un <strong>candado</strong> junto al nombre si no permite actualización masiva de precios.</li>
        <li>Su <strong>stock</strong> en unidades, con el aviso <strong>Stock bajo</strong> (5 unidades o menos) o <strong>Sin stock</strong>. Las preparaciones muestran <strong>Sin control</strong>.</li>
        <li>Su <strong>estado</strong>: activo o inactivo.</li>
    </ul>
    <p>Para crear un producto:</p>
    <ol>
        <li>Tocá <strong>+ Nuevo producto</strong>.</li>
        <li>Completá el nombre, la categoría y el precio.</li>
        <li>Elegí el tipo: <strong>Por unidad</strong> (lleva stock, e indicás cuántas unidades hay) o <strong>Preparación</strong> (no lleva stock).</li>
        <li>Dejá activadas las opciones que correspondan: <strong>Activo</strong> y <strong>Permitir actualización masiva</strong>.</li>
        <li>Tocá <strong>Crear producto</strong>.</li>
    </ol>
    <p>Para modificarlo usá <strong>Editar</strong>. Para sacarlo de la carta sin perderlo, usá <strong>Dar de baja</strong>; <strong>Dar de alta</strong> lo vuelve a habilitar. Los productos no se eliminan. Un producto dado de baja o sin stock no se puede cargar en una comanda.</p>
    <p>El stock de los productos por unidad baja solo cuando un mozo los carga en una comanda, y vuelve a subir si los quita. Para reponer stock, editá el producto y cargá la nueva cantidad. Reponer stock no reactiva un producto dado de baja.</p>
</section>

<section class="ay-seccion" id="masiva">
    <h2>Actualización masiva de precios</h2>
    <p>Sirve para cambiar varios precios a la vez. Solo modifica precios: el stock, el estado y la categoría no se tocan.</p>
    <ol>
        <li>Tocá <strong>Actualizar precios</strong>.</li>
        <li>Elegí la acción: <strong>Aumentar</strong> o <strong>Disminuir</strong>.</li>
        <li>Elegí el tipo de ajuste, <strong>Porcentaje (%)</strong> o <strong>Monto fijo ($)</strong>, y escribí el valor.</li>
        <li>En <strong>Aplicar a</strong>, elegí <strong>Todos los productos</strong>, <strong>Una categoría</strong> o <strong>Productos seleccionados</strong>.</li>
        <li>En la lista, desmarcá los productos que querés dejar afuera.</li>
        <li>Mirá la vista previa: muestra el precio actual, el ajuste y el nuevo precio de cada producto.</li>
        <li>Tocá <strong>Aplicar actualización</strong> y confirmá.</li>
    </ol>
    <p>Los productos con candado nunca se modifican, aunque estén dentro del alcance elegido. Si con el valor ingresado algún precio quedara en cero o en negativo, el sistema no cambia ninguno. Con porcentajes, el nuevo precio se redondea al peso más cercano.</p>
</section>

<section class="ay-seccion" id="descuento-admin">
    <h2>Descuento por consumo</h2>
    <p>El menú <strong>Descuento</strong> configura un descuento automático para las comandas con mucho consumo. El sistema lo aplica cuando el subtotal de la comanda supera el tope que definas; si el subtotal es igual o menor, no se aplica.</p>
    <p>Desde esa pantalla guardás la configuración y podés desactivar el descuento cuando no lo quieras usar. El sistema rechaza valores inválidos. Al cambiar la configuración se recalculan las comandas abiertas; las cerradas no se modifican.</p>
</section>

<section class="ay-seccion" id="historial">
    <h2>Historial de comandas</h2>
    <p>El menú <strong>Comandas</strong> lista todas las comandas con su mesa, el mozo, el estado y el total, de la más reciente a la más antigua. Se puede filtrar por estado, por fecha y por mozo, y quitar los filtros para volver a ver todo. Si ninguna comanda coincide, el sistema lo avisa.</p>
    <p>Al abrir una comanda la consultás completa. Una comanda cerrada no se puede modificar.</p>
</section>

<section class="ay-seccion" id="notificaciones">
    <h2>Notificaciones de stock</h2>
    <p>Cuando un producto por unidad se queda sin stock, el sistema avisa al administrador de tres maneras:</p>
    <ul>
        <li>La <strong>campana</strong> del menú muestra un número con las notificaciones sin leer.</li>
        <li>En el panel de mesas aparece un recuadro naranja con las últimas notificaciones. Desaparece cuando las marcás como leídas.</li>
        <li>La pantalla <strong>Notificaciones</strong>, que se abre desde la campana, guarda el historial: el nombre del producto, cuándo se agotó y si ya se repuso.</li>
    </ul>
    <p>Para marcar una notificación como leída, tocá <strong>Marcar como leída</strong>. La notificación sigue en el historial y el stock no cambia. Hay un solo aviso por agotamiento: si el producto sigue en cero no se repite, y si se repone y vuelve a agotarse, se crea uno nuevo.</p>
</section>