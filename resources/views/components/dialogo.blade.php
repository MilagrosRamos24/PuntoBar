{{--
    Ventanas de aviso y confirmación con el estilo de Punto Bar.
    Reemplazan a las ventanas del navegador (alert y confirm).

    Se incluye UNA vez por pantalla, antes de </body>:
        <x-dialogo />

    Cómo usarlo:

    1) Confirmar un formulario (sin escribir JavaScript):
        <form method="POST" action="..."
              data-confirmar="¿Dar de baja este producto?"
              data-confirmar-titulo="Dar de baja"
              data-confirmar-aceptar="Dar de baja">
        Solo data-confirmar es obligatorio; los demás son opcionales.

    2) Desde JavaScript:
        avisar('Primero seleccioná una mesa.');
        confirmar({ titulo: '...', mensaje: '...' }).then(function (acepto) { ... });

    Además, los alert() que ya existan se muestran con esta ventana.
--}}

<dialog id="dialogo-sistema" class="dialogo" aria-labelledby="dialogo-titulo" aria-describedby="dialogo-mensaje">
    <h2 id="dialogo-titulo" class="dialogo-titulo"></h2>
    <p id="dialogo-mensaje" class="dialogo-mensaje"></p>
    <div class="dialogo-acciones">
        <button type="button" class="dialogo-btn" data-respuesta="no">Cancelar</button>
        <button type="button" class="dialogo-btn principal" data-respuesta="si">Aceptar</button>
    </div>
</dialog>

<style>
    .dialogo {
        width: min(420px, calc(100% - 40px));
        margin: auto;
        padding: 26px 26px 22px;
        border: 1px solid #5c4028;
        border-radius: 16px;
        background: #1b1815;
        color: #f1e9df;
        font-family: Arial, Helvetica, sans-serif;
        box-shadow: 0 24px 60px rgba(0, 0, 0, .55);
    }

    .dialogo::backdrop {
        background: rgba(0, 0, 0, .7);
    }

    .dialogo-titulo {
        margin: 0 0 10px;
        font-size: 20px;
        color: #f3e1cf;
    }

    .dialogo-mensaje {
        margin: 0;
        color: #cfc5bc;
        font-size: 15px;
        line-height: 1.55;
    }

    .dialogo-acciones {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 24px;
    }

    .dialogo-btn {
        width: auto;
        margin: 0;
        padding: 11px 18px;
        border-radius: 8px;
        border: 1px solid #5d4834;
        background: transparent;
        color: #ded3ca;
        font-size: 14px;
        font-weight: 400;
        cursor: pointer;
    }

    .dialogo-btn:hover {
        border-color: #d27a40;
        color: #f0a060;
        background: transparent;
    }

    .dialogo-btn.principal {
        background: #f09b58;
        border-color: #f09b58;
        color: #1c1510;
        font-weight: 700;
    }

    .dialogo-btn.principal:hover {
        background: #ffad6a;
        color: #1c1510;
    }

    .dialogo-btn:focus-visible {
        outline: 3px solid #e2a15c;
        outline-offset: 2px;
    }

    @media (max-width: 420px) {
        .dialogo-acciones {
            flex-direction: column-reverse;
        }

        .dialogo-btn {
            width: 100%;
        }
    }
</style>

<script>
(function () {
    const dialogo = document.getElementById('dialogo-sistema');

    if (!dialogo) {
        return;
    }

    const btnSi = dialogo.querySelector('[data-respuesta="si"]');
    const btnNo = dialogo.querySelector('[data-respuesta="no"]');

    function mostrarDialogo(opciones) {
        return new Promise(function (resolver) {
            dialogo.querySelector('#dialogo-titulo').textContent = opciones.titulo || 'Atención';
            dialogo.querySelector('#dialogo-mensaje').textContent = opciones.mensaje || '';
            btnSi.textContent = opciones.textoAceptar || 'Aceptar';
            btnNo.textContent = opciones.textoCancelar || 'Cancelar';
            btnNo.hidden = Boolean(opciones.soloAviso);

            const elementoAnterior = document.activeElement;

            function terminar(respuesta) {
                btnSi.removeEventListener('click', alAceptar);
                btnNo.removeEventListener('click', alCancelar);
                dialogo.removeEventListener('cancel', alCancelar);
                dialogo.close();

                if (elementoAnterior && typeof elementoAnterior.focus === 'function') {
                    elementoAnterior.focus();
                }

                resolver(respuesta);
            }

            function alAceptar() {
                terminar(true);
            }

            function alCancelar(evento) {
                evento.preventDefault();
                terminar(false);
            }

            btnSi.addEventListener('click', alAceptar);
            btnNo.addEventListener('click', alCancelar);
            dialogo.addEventListener('cancel', alCancelar);

            dialogo.showModal();

            // En las confirmaciones, el foco arranca en "Cancelar", por seguridad.
            (opciones.soloAviso ? btnSi : btnNo).focus();
        });
    }

    window.avisar = function (mensaje, titulo) {
        return mostrarDialogo({
            titulo: titulo || 'Atención',
            mensaje: mensaje,
            textoAceptar: 'Entendido',
            soloAviso: true,
        });
    };

    window.confirmar = function (opciones) {
        return mostrarDialogo(opciones);
    };

    // Los alert() que ya existan en el código se muestran con esta ventana.
    window.alert = function (mensaje) {
        window.avisar(String(mensaje));
    };

    // Formularios con data-confirmar: piden confirmación antes de enviarse.
    document.addEventListener('submit', function (evento) {
        const form = evento.target;

        // Si otro código ya frenó el envío (por ejemplo, una validación), no se pregunta nada.
        if (evento.defaultPrevented || !(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirmar')) {
            return;
        }

        evento.preventDefault();

        window.confirmar({
            titulo: form.dataset.confirmarTitulo || 'Confirmar',
            mensaje: form.dataset.confirmar,
            textoAceptar: form.dataset.confirmarAceptar || 'Aceptar',
            textoCancelar: form.dataset.confirmarCancelar || 'Cancelar',
        }).then(function (acepto) {
            if (acepto) {
                form.submit();
            }
        });
    });
})();
</script>
