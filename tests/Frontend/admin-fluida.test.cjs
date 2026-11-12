const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const {JSDOM,VirtualConsole}=require('jsdom');
const root=require('node:path').resolve(__dirname, '../..')+'/';
const source=fs.readFileSync(root+'public/js/admin-fluida.js','utf8');
const pause=()=>new Promise(r=>setTimeout(r,0));
const wait=async predicate=>{for(let i=0;i<100;i++){if(predicate())return;await pause();}throw Error('Timeout esperando DOM');};
function entorno(modulo='mesas',content='',options={}){
 const errors=[];const vc=new VirtualConsole();vc.on('jsdomError',e=>errors.push(e.message));
 const html=`<!doctype html><title>Inicial</title><header class="pb-admin-heading"><h1>Inicial</h1></header><main data-admin-fluida="${modulo}" tabindex="-1">${content}</main>`;
 const dom=new JSDOM(html,{url:`http://puntobar.test/admin/${modulo}`,runScripts:'outside-only',virtualConsole:vc});
 const w=dom.window;w.scrollTo=()=>{};w.confirm=()=>true;w.fetch=async()=>{throw Error('Sin mock')};
 w.HTMLDialogElement.prototype.showModal=function(){this.setAttribute('open','');};
 w.HTMLDialogElement.prototype.close=function(){this.removeAttribute('open');};
 const render=(inner,url=`http://puntobar.test/admin/${modulo}`,status=200,group=modulo)=>({ok:status<400,status,url,text:async()=>`<title>Actualizado</title><header class="pb-admin-heading"><h1>Nuevo título</h1></header><main data-admin-fluida="${group}" tabindex="-1">${inner}</main>`});
 const enviar=form=>form.dispatchEvent(new w.SubmitEvent('submit',{bubbles:true,cancelable:true}));
 if(options.load!==false)w.eval(source);
 return {dom,w,d:w.document,errors,render,enviar};
}
const form='<form method="post" action="/admin/mesas/1"><input name="_token" value="csrf"><input name="_method" value="PUT"><input name="numero" value="2" required><button type="submit">Guardar</button></form>';

test('Mesas: actualiza contenido, título y URL sin reemplazar documento ni cabecera; bloquea duplicados',async()=>{
 const e=entorno('mesas',form);const header=e.d.querySelector('header');let calls=0,body;
 e.w.fetch=async(url,op)=>{calls++;body=op.body;await pause();return e.render('<h1>Mesa 2</h1>','http://puntobar.test/admin/mesas/1');};
 e.enviar(e.d.querySelector('form'));e.enviar(e.d.querySelector('form'));
 assert.equal(e.d.querySelector('button').disabled,true);
 await wait(()=>e.d.querySelector('main h1'));
 assert.equal(calls,1);assert.equal(body.get('_token'),'csrf');assert.equal(body.get('_method'),'PUT');assert.equal(body.get('numero'),'2');
 assert.equal(e.d.querySelector('header'),header);assert.equal(e.d.title,'Actualizado');assert.equal(e.w.location.pathname,'/admin/mesas/1');assert.equal(header.textContent,'Nuevo título');e.dom.window.close();
});

test('Rechazo del servidor conserva valores y vuelve a inicializar requisitos de atención',async()=>{
 const returned='<p role="alert">Asigná un mozo</p><form><select id="estado"><option value="ocupada">Espera</option><option value="libre">Libre</option></select><select id="mozo_id"><option value="">Sin asignar</option></select><input id="cantidad_personas" value="0"></form>';
 const e=entorno('mesas',form);e.w.fetch=async()=>e.render(returned,'http://puntobar.test/admin/mesas/1/edit');
 e.enviar(e.d.querySelector('form'));await wait(()=>e.d.getElementById('mozo_id'));
 assert.equal(e.d.getElementById('mozo_id').required,true);assert.equal(e.d.getElementById('cantidad_personas').min,'1');
 e.d.getElementById('estado').value='libre';e.d.getElementById('estado').dispatchEvent(new e.w.Event('change'));
 assert.equal(e.d.getElementById('mozo_id').required,false);assert.equal(e.d.getElementById('cantidad_personas').min,'0');e.dom.window.close();
});

for(const modulo of ['mesas','mozos','productos','notificaciones','descuento','comandas']){
 test(`${modulo}: filtros/paginación por GET, sin POST ni recarga`,async()=>{
 const e=entorno(modulo,`<a href="/admin/${modulo}?page=2">Siguiente</a>`);let method;
 e.w.fetch=async(url,op)=>{method=op.method;return e.render('<p id="resultado">Página 2</p>',url)};
 e.d.querySelector('a').click();await wait(()=>e.d.getElementById('resultado'));assert.equal(method,'GET');assert.equal(e.w.location.search,'?page=2');e.dom.window.close();
 });
}

test('Filtro GET serializa estado y mozo; historial Atrás vuelve a pedir GET',async()=>{
 const e=entorno('comandas','<form method="get" action="/admin/comandas"><input name="estado" value="cerrada"><input name="mozo" value="2"><button>Filtrar</button></form>');const requests=[];
 e.w.fetch=async(url,op)=>{requests.push([url,op.method]);return e.render('<p id="r">Filtrado</p>',url)};
 e.enviar(e.d.querySelector('form'));await wait(()=>e.d.getElementById('r'));
 assert.match(requests[0][0],/estado=cerrada&mozo=2/);
 e.w.history.replaceState({},'', '/admin/comandas');e.w.dispatchEvent(new e.w.PopStateEvent('popstate'));await wait(()=>requests.length===2);assert.equal(requests[1][1],'GET');e.dom.window.close();
});

for(const status of [401,403,419,500])test(`HTTP ${status}: conserva formulario, habilita controles y no reintenta`,async()=>{
 const e=entorno('mesas',form);let calls=0;e.w.fetch=async()=>{calls++;return e.render('',undefined,status)};
 e.enviar(e.d.querySelector('form'));await wait(()=>!e.d.querySelector('[aria-busy]'));
 assert.equal(calls,1);assert.equal(e.d.querySelector('input[name="numero"]').value,'2');assert.equal(e.d.querySelector('button').disabled,false);assert.ok(e.d.querySelector('[role="alert"]'));e.dom.window.close();
});

test('Fallo de red y respuesta de login: no repite la mutación ni pierde valores',async()=>{
 for(const mode of ['network','login']){
 const e=entorno('mesas',form);let calls=0;e.w.fetch=async()=>{calls++;if(mode==='network')throw Error('offline');return e.render('', 'http://puntobar.test/admin/login')};
 e.enviar(e.d.querySelector('form'));await wait(()=>!e.d.querySelector('[aria-busy]'));assert.equal(calls,1);assert.equal(e.d.querySelector('input[name="numero"]').value,'2');assert.ok(e.d.querySelector('[role="alert"]'));e.dom.window.close();}
});

test('Confirmación real del componente: cancelar no envía; aceptar pasa por fetch',async()=>{
 const e=entorno('mesas',form.replace('<form ','<form data-confirmar="¿Desactivar?" '),{load:false});
 const component=fs.readFileSync(root+'resources/views/components/dialogo.blade.php','utf8');
 const mark=component.slice(component.indexOf('<dialog'),component.indexOf('<style>'));e.d.body.insertAdjacentHTML('beforeend',mark);
 const script=component.match(/<script>([\s\S]*?)<\/script>/)[1];e.w.eval(script);e.w.eval(source);let calls=0;
 e.w.fetch=async()=>{calls++;return e.render('<p id="ok">Guardado</p>')};
 e.enviar(e.d.querySelector('form'));assert.equal(calls,0);e.d.querySelector('[data-respuesta="no"]').click();await pause();assert.equal(calls,0);
 e.enviar(e.d.querySelector('form'));e.d.querySelector('[data-respuesta="si"]').click();await wait(()=>e.d.getElementById('ok'));assert.equal(calls,1);e.dom.window.close();
});

test('Notificaciones: mantiene selección al marcar leída y reconfigura eliminación tras actualizar',async()=>{
 const fixture=()=>'<input id="pn-todas" type="checkbox"><span id="pn-conteo"></span><form id="pn-eliminar" action="/admin/notificaciones" method="POST"><input id="pn-alcance" name="alcance" value="todas"><button id="pn-borrar" data-total="3" type="submit">Eliminar</button></form><input class="pn-elegir" type="checkbox" form="pn-eliminar" name="ids[]" value="5"><input class="pn-elegir" type="checkbox" form="pn-eliminar" name="ids[]" value="6"><form id="leida" action="/admin/notificaciones/5/leida" method="POST"><button type="submit">Leída</button></form>';
 const e=entorno('notificaciones',fixture());e.w.eval(fs.readFileSync(root+'public/js/notificaciones.js','utf8'));let sent;
 e.w.fetch=async(url,op)=>{sent=op.body;return e.render(fixture())};
 const first=e.d.querySelector('.pn-elegir');first.checked=true;first.dispatchEvent(new e.w.Event('change'));
 assert.equal(e.d.getElementById('pn-alcance').value,'seleccionadas');
 e.enviar(e.d.getElementById('leida'));await wait(()=>!e.d.querySelector('[aria-busy]'));
 assert.equal(e.d.querySelector('.pn-elegir').checked,true);assert.equal(e.d.getElementById('pn-alcance').value,'seleccionadas');
 e.enviar(e.d.getElementById('pn-eliminar'));await wait(()=>!e.d.querySelector('[aria-busy]'));
 assert.deepEqual([...sent.getAll('ids[]')],['5']);assert.equal(sent.get('alcance'),'seleccionadas');e.dom.window.close();
});

const productoFixture=()=>`<button data-nuevo>Nuevo</button><button data-editar='{"id":4,"url":"/admin/productos/4","nombre":"Agua","categoria":"bebida","tipo":"unidad","stock":8,"precio":500,"activo":true,"masiva":true}'>Editar</button><dialog id="dlg-producto"><form id="form-producto" method="POST"><h2 id="dlg-producto-titulo"></h2><p id="dlg-producto-sub"></p><input name="_token" value="csrf"><input name="_method"><input name="_editando"><input name="nombre"><select name="categoria"><option value="bebida">Bebida</option></select><input type="radio" name="tipo" value="unidad" checked><input type="radio" name="tipo" value="preparacion"><div id="fp-stock-campo"><input name="stock"></div><input name="precio"><input type="checkbox" name="activo"><input type="checkbox" name="permite_actualizacion_masiva"><p id="fp-tipo-ayuda"></p><button type="button" data-cerrar>Cancelar</button><button id="fp-guardar" type="submit">Guardar</button></form></dialog><div id="pp-config" data-url-store="/admin/productos" data-reabrir=""></div>`;

test('Productos: crea, vuelve a abrir editar y guarda nuevamente tras reemplazar el contenido',async()=>{
 const e=entorno('productos',productoFixture());e.w.eval(fs.readFileSync(root+'public/js/productos.js','utf8'));let calls=0,bodies=[];
 e.w.fetch=async(url,op)=>{calls++;bodies.push(op.body);return e.render(productoFixture())};
 e.d.querySelector('[data-nuevo]').click();assert.equal(e.d.getElementById('dlg-producto').open,true);
 e.d.querySelector('[name="nombre"]').value='Jugo';e.enviar(e.d.getElementById('form-producto'));await wait(()=>!e.d.querySelector('[aria-busy]'));
 assert.equal(calls,1);assert.equal(bodies[0].get('nombre'),'Jugo');
 e.d.querySelector('[data-editar]').click();assert.equal(e.d.querySelector('[name="nombre"]').value,'Agua');
 e.enviar(e.d.getElementById('form-producto'));await wait(()=>!e.d.querySelector('[aria-busy]'));
 assert.equal(calls,2);assert.equal(bodies[1].get('_method'),'PUT');assert.equal(e.d.getElementById('fp-guardar').disabled,false);assert.equal(e.errors.length,0);e.dom.window.close();
});

test('Productos: si el servidor reenvía errores, reabre el diálogo con los datos recibidos',async()=>{
 const e=entorno('productos',productoFixture());e.w.eval(fs.readFileSync(root+'public/js/productos.js','utf8'));
 e.w.fetch=async()=>e.render(productoFixture().replace('data-reabrir=""','data-reabrir="1"').replace('<input name="nombre">','<input name="nombre" value="Duplicado"><p class="pp-error">Ya existe</p>'));
 e.d.querySelector('[data-nuevo]').click();e.enviar(e.d.getElementById('form-producto'));await wait(()=>!e.d.querySelector('[aria-busy]'));
 assert.equal(e.d.getElementById('dlg-producto').open,true);assert.equal(e.d.querySelector('[name="nombre"]').value,'Duplicado');assert.equal(e.d.querySelector('.pp-error').textContent,'Ya existe');e.dom.window.close();
});

const masivaFixture=()=>`<button data-abrir-masiva>Precios</button><div id="pp-config" data-url-vista-previa="/admin/productos/actualizacion-masiva/vista-previa"></div><dialog id="dlg-masiva"><form id="form-masiva" action="/admin/productos/actualizacion-masiva" method="POST"><input name="_token" value="csrf"><input type="radio" name="direccion" value="1" checked><input type="radio" name="direccion" value="-1"><input type="radio" name="tipo" value="porcentaje" checked><input type="radio" name="tipo" value="monto"><input name="valor"><input type="radio" name="alcance" value="todos" checked><input type="radio" name="alcance" value="seleccionados"><input type="radio" name="categoria" value="bebida" checked><span id="m-valor-label"></span><div id="m-valor-wrap"></div><div id="m-categorias"></div><p id="m-lista-ayuda"></p><div id="m-lista"><label class="pp-check" data-id="4" data-categoria="bebida" data-nombre="Agua" data-precio="500" data-bloqueado="0"><input type="checkbox" value="4"></label></div><p id="m-aviso"></p><p id="m-mensaje"></p><span id="m-contador"></span><table><tbody id="m-filas"></tbody></table><button id="m-aplicar" type="button" disabled>Aplicar</button><button type="button" data-cerrar-masiva>Cancelar</button></form></dialog><dialog id="dlg-confirmar"><h2 id="dlg-confirmar-titulo"></h2><p id="dlg-confirmar-detalle"></p><button id="c-volver">Volver</button><button id="c-confirmar">Confirmar</button></dialog>`;

test('Precios masivos: vista previa, confirmación y guardado por fetch, luego se puede reabrir',async()=>{
 const e=entorno('productos',masivaFixture());e.w.eval(fs.readFileSync(root+'public/js/productos-masiva.js','utf8'));let preview=0,save=0;
 e.w.fetch=async(url,op)=>{if(url.endsWith('vista-previa')){preview++;return {ok:true,status:200,json:async()=>({filas:[{nombre:'Agua',precio_actual:'500',precio_nuevo:'550',valido:true}],cantidad:1,hay_invalidos:false})}}save++;assert.equal(op.body.get('valor'),'10');return e.render(masivaFixture())};
 e.d.querySelector('[data-abrir-masiva]').click();const valor=e.d.querySelector('[name="valor"]');valor.value='10';valor.dispatchEvent(new e.w.Event('input'));
 await new Promise(r=>setTimeout(r,300));assert.equal(preview,1);assert.equal(e.d.getElementById('m-aplicar').disabled,false);
 e.d.getElementById('m-aplicar').click();assert.equal(save,0);assert.equal(e.d.getElementById('dlg-confirmar').open,true);
 e.d.getElementById('c-confirmar').click();await wait(()=>!e.d.querySelector('[aria-busy]'));assert.equal(save,1);
 e.d.querySelector('[data-abrir-masiva]').click();assert.equal(e.d.getElementById('dlg-masiva').open,true);assert.equal(e.errors.length,0);e.dom.window.close();
});

test('Estado de mesa: conserva nodo seleccionado, botón de comanda, referencia del reloj y permite segundo cambio',async()=>{
 const {dom,w,d}=entorno('mesas','');
 const card=(estado='libre')=>`<article class="mesa ${estado}" data-mesa-id="1" data-mesa-estado="${estado}" data-inicio-espera=""><button data-abrir-comanda="3">Ver</button><div data-tiempo-pedido="1" data-desde="123"></div><form class="estado-form" method="POST" action="/mesas/1/estado"><input name="_token" value="csrf"><input name="_method" value="PUT"><select name="estado"><option value="libre">Libre</option><option value="ocupada">Espera</option></select><button type="submit">Actualizar</button></form></article>`;
 d.body.innerHTML=card()+'<strong id="cantidad-mesas-libres">1</strong><strong id="cantidad-mesas-alerta">0</strong>';
 const original=d.querySelector('.mesa');original.classList.add('seleccionada');const reloj=d.querySelector('[data-tiempo-pedido]');let opened=0;d.querySelector('[data-abrir-comanda]').addEventListener('click',()=>opened++);
 w.eval(fs.readFileSync(root+'public/js/mesas-admin-fluida.js','utf8'));let calls=0;
 w.fetch=async()=>{calls++;return {ok:true,json:async()=>({mensaje:'Estado actualizado',html:card('ocupada')+'<strong id="cantidad-mesas-libres">0</strong><strong id="cantidad-mesas-alerta">0</strong>'})}};
 d.querySelector('form').requestSubmit();await wait(()=>!d.querySelector('[aria-busy]'));
 assert.equal(d.querySelector('.mesa'),original);assert.equal(d.querySelector('[data-tiempo-pedido]'),reloj);assert.equal(original.classList.contains('seleccionada'),true);assert.equal(d.getElementById('cantidad-mesas-libres').textContent,'0');
 d.querySelector('[data-abrir-comanda]').click();assert.equal(opened,1);d.querySelector('form').requestSubmit();await wait(()=>!d.querySelector('[aria-busy]'));assert.equal(calls,2);dom.window.close();
});

test('Estado de mesa: error de validación muestra aviso y conserva estado real sin reintentar',async()=>{
 const {dom,w,d}=entorno();d.body.innerHTML='<article class="mesa libre"><form class="estado-form" action="/mesas/1/estado" method="POST"><input name="estado" value="ocupada"><button type="submit">Actualizar</button></form></article>';
 w.eval(fs.readFileSync(root+'public/js/mesas-admin-fluida.js','utf8'));let calls=0;w.fetch=async()=>{calls++;return {ok:false,status:422,json:async()=>({errors:{estado:['Primero asigná mozo y personas.']}})}};
 d.querySelector('form').requestSubmit();await wait(()=>!d.querySelector('[aria-busy]'));assert.equal(calls,1);assert.match(d.querySelector('[role="alert"]').textContent,/asigná mozo/);assert.equal(d.querySelector('.mesa').classList.contains('libre'),true);assert.equal(d.querySelector('button').disabled,false);dom.window.close();
});


test('PuntoBar3: Alerta de atención también exige mozo y personas; Libre los libera', async()=>{
 const e=entorno('mesas', `<form><select id="estado" data-estados-con-mozo='["ocupada","pendiente_de_cierre"]'><option value="pendiente_de_cierre" selected>Alerta</option><option value="libre">Libre</option></select><select id="mozo_id"><option value="">Sin asignar</option></select><input id="cantidad_personas" type="number" value="0"></form>`);
 assert.equal(e.d.getElementById('mozo_id').required,true);
 assert.equal(e.d.getElementById('cantidad_personas').min,'1');
 assert.equal(e.d.querySelector('form').checkValidity(),false);
 e.d.getElementById('estado').value='libre';e.d.getElementById('estado').dispatchEvent(new e.w.Event('change'));
 assert.equal(e.d.getElementById('mozo_id').required,false);
 assert.equal(e.d.getElementById('cantidad_personas').min,'0');e.dom.window.close();
});
