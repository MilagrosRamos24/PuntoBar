<section class="ay-seccion" id="mesas">
    <h2>Entender las mesas</h2>
    <p>Cada mesa se muestra como una tarjeta con su número, su estado, el mozo asignado, la cantidad de personas y el total consumido. El color del estado indica qué hay que hacer.</p>

    <ul class="ay-estados">
        <li><span class="ay-chip ay-chip--libre">Libre</span><span>No hay nadie en la mesa.</span></li>
        <li><span class="ay-chip ay-chip--espera">En espera de pedido</span><span>Hay clientes esperando que se tome el pedido.</span></li>
        <li><span class="ay-chip ay-chip--atendida">Mesa atendida</span><span>El pedido ya fue tomado.</span></li>
        <li><span class="ay-chip ay-chip--alerta">Alerta de atención</span><span>La mesa espera hace demasiado tiempo y necesita atención.</span></li>
    </ul>

    <p>Podés ver las mesas libres y las de otros mozos. La opción <strong>Mis mesas</strong>, en el menú, muestra solo las tuyas. La comanda de una mesa que atiende otro mozo no se puede abrir.</p>
    <p>Mientras una mesa espera su primer pedido, la tarjeta muestra cuánto tiempo lleva esperando. Si ese tiempo pasa el límite, la mesa cambia sola a <strong>Alerta de atención</strong>.</p>
</section>

<section class="ay-seccion" id="tomar-mesa">
    <h2>Tomar una mesa libre</h2>
    <ol>
        <li>Tocá la tarjeta de una mesa libre para seleccionarla.</li>
        <li>En la barra de abajo, escribí en <strong>Personas</strong> cuántos clientes hay.</li>
        <li>Tocá <strong>Registrar atención</strong>.</li>
    </ol>
    <p>La mesa queda a tu cargo. Solo podés tomar una mesa libre sin encargado, o una que ya esté a tu nombre.</p>
</section>

<section class="ay-seccion" id="estado">
    <h2>Cambiar el estado de la mesa</h2>
    <p>En la tarjeta de tu mesa, elegí el nuevo estado en el desplegable <strong>Estado de la mesa</strong> y tocá <strong>Actualizar</strong>. Una mesa no se puede marcar como atendida si su comanda no tiene productos.</p>
</section>

<section class="ay-seccion" id="comanda">
    <h2>Abrir una comanda</h2>
    <ol>
        <li>Elegí la mesa que vas a atender.</li>
        <li>En la tarjeta de la mesa, tocá <strong>Abrir comanda</strong>. Si la mesa ya tiene una comanda abierta, el botón dice <strong>Ver comanda</strong>.</li>
        <li>Se abre la ventana de la comanda, con los productos cargados, el subtotal y el total.</li>
    </ol>
    <p>Una mesa solo puede tener una comanda abierta a la vez, y la puede abrir el mozo asignado a la mesa o el administrador.</p>
</section>

<section class="ay-seccion" id="productos">
    <h2>Cargar productos</h2>
    <ol>
        <li>En la ventana de la comanda, buscá el producto en la lista <strong>Producto</strong>. Están agrupados por categoría: bebidas, comidas y postres.</li>
        <li>Escribí la cantidad.</li>
        <li>Tocá <strong>Agregar</strong>.</li>
    </ol>
    <p>Si cargás un producto que ya está en la comanda, el sistema suma la cantidad en la misma línea. El precio queda fijado al momento de cargarlo: si el administrador cambia el precio después, lo ya cargado no se modifica.</p>
    <p>Los productos sin stock aparecen en la lista, pero no se pueden elegir y figuran como «Sin stock». Los que llevan stock indican cuántas unidades quedan. Los productos dados de baja no aparecen. Si pedís más unidades de las que hay, el sistema no las carga y lo avisa.</p>
</section>

<section class="ay-seccion" id="cantidad">
    <h2>Cambiar la cantidad o quitar un producto</h2>
    <p>Cada línea de la comanda tiene dos acciones:</p>
    <ul>
        <li><strong>Actualizar:</strong> cambia la cantidad. El stock se ajusta solo, tanto si subís como si bajás la cantidad.</li>
        <li><strong>Quitar:</strong> saca el producto de la comanda y devuelve las unidades al stock.</li>
    </ul>
</section>

<section class="ay-seccion" id="descuento">
    <h2>Descuento por consumo</h2>
    <p>Si el administrador activó el descuento, el sistema lo aplica solo cuando el subtotal supera el tope definido. Se muestra en la ventana de la comanda y se quita automáticamente si el subtotal vuelve a quedar por debajo del tope.</p>
</section>

<section class="ay-seccion" id="cerrar">
    <h2>Cerrar la comanda</h2>
    <ol>
        <li>Revisá que los productos y las cantidades sean los correctos.</li>
        <li>Tocá <strong>Cerrar comanda</strong>.</li>
        <li>El sistema pregunta si querés cerrarla: confirmá con <strong>Cerrar comanda</strong>, o tocá <strong>Volver</strong> para seguir cargando.</li>
    </ol>
    <p>Al cerrar, el sistema calcula el total, la mesa queda libre y la comanda pasa al historial. No se puede cerrar una comanda sin productos, y una comanda cerrada ya no se puede modificar: si te equivocaste, avisale al administrador. Para salir de la ventana sin cerrar la comanda, tocá <strong>Listo</strong>: la comanda sigue abierta.</p>
</section>