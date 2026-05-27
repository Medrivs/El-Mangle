<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - <?= $categoria['nombre_categoria'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#EFF6FF] flex h-screen w-full overflow-hidden font-sans">

    <main class="flex-1 min-w-0 flex flex-col h-full overflow-hidden">
        
        <header class="bg-[#0A1F3D] text-white p-4 flex gap-4 items-center shrink-0">
            <?php if ($pestaña_activa === null): ?>
                <a href="<?= base_url('pos/mesa/'.$mesa['id_mesa']) ?>" class="bg-blue-800 hover:bg-blue-700 p-2 px-4 rounded-xl font-bold text-sm transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Volver a Menu Principal
                </a>
            <?php else: ?>
                <a href="<?= base_url('pos/filtrar/'.$mesa['id_mesa'].'/'.$categoria['id_categoria']) ?>" class="bg-orange-600 hover:bg-orange-700 p-2 px-4 rounded-xl font-bold text-sm transition">
                    <i class="fa-solid fa-container-storage mr-1"></i> ← Ver todas las Subcategorias
                </a>
            <?php endif; ?>
            
            <h1 class="font-black text-lg tracking-wide uppercase">
                <?= $categoria['nombre_categoria'] ?> 
                <?= $pestaña_activa ? "› <span class='text-[#00B4D8]'>".$pestaña_activa."</span>" : "" ?>
            </h1>
        </header>

        <div class="flex-1 overflow-y-auto p-6">
            <?php if ($pestaña_activa === null): ?>
                <h3 class="text-xs font-bold text-[#1565C0] tracking-widest uppercase mb-6">Selecciona una Subcategoria:</h3>
                
                <div class="grid grid-cols-2 gap-6">
                    <?php foreach($pestañas as $pest): ?>
                        <a href="<?= base_url('pos/filtrar/'.$mesa['id_mesa'].'/'.$categoria['id_categoria'].'/'.urlencode($pest['subcategoria'])) ?>" 
                           class="bg-white p-8 rounded-3xl border-2 border-transparent shadow-sm hover:border-[#1565C0] hover:shadow-xl transition-all flex flex-col items-center justify-center text-center group">
                            <div class="w-16 h-16 bg-blue-50 text-[#1565C0] rounded-2xl flex items-center justify-center text-3xl mb-4 group-hover:bg-[#1565C0] group-hover:text-white transition-colors">
                                ⚓
                            </div>
                            <span class="font-black text-xl text-[#0A1F3D] uppercase tracking-tight"><?= $pest['subcategoria'] ?></span>
                            <span class="text-xs text-gray-400 mt-1">Explorar variedad</span>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <h3 class="text-xs font-bold text-[#1565C0] tracking-widest uppercase mb-6">Platillos en <?= $pestaña_activa ?>:</h3>
                
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach($platillos as $p): ?>
                        <button onclick="abrirModal(<?= $p['id_platillo'] ?>, '<?= addslashes($p['nombre_platillo']) ?>', <?= $p['precio_venta'] ?>, '<?= addslashes($p['subcategoria']) ?>')" 
                                class="bg-white p-5 rounded-2xl shadow-sm border-2 border-blue-50 hover:border-blue-500 text-center transition-all flex flex-col items-center justify-between h-44">
                            <div class="text-3xl text-blue-500"><i class="fa-solid fa-martini-glass-citrus"></i></div>
                            <div class="font-bold text-[#0A1F3D] text-sm leading-tight line-clamp-2 my-2"><?= $p['nombre_platillo'] ?></div>
                            <div class="text-[#1565C0] font-black">$<?= number_format($p['precio_venta'], 2) ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <aside class="w-[380px] bg-white border-l border-blue-200 flex flex-col shadow-2xl shrink-0 h-full">
        <div class="bg-[#0A1F3D] text-white p-4 font-bold text-center shrink-0">🛒 Orden de Mesa <?= $mesa['numero_mesa'] ?></div>
        
        <div id="carrito-items" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50/50">
            <div class="text-center mt-10">
                <i class="fa-solid fa-cart-shopping text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-400 font-medium">Carrito vacio</p>
            </div>
        </div>

        <div class="p-5 border-t border-gray-200 bg-white shrink-0">
            <div class="flex justify-between font-black text-xl mb-4 text-[#0A1F3D]">
                <span>Total:</span> <span id="total-txt">$0.00</span>
            </div>
            
            <form id="form-orden" action="<?= base_url('pos/enviar_orden') ?>" method="POST">
                <input type="hidden" name="id_mesa" value="<?= $mesa['id_mesa'] ?>">
                <input type="hidden" name="datos_carrito" id="datos_carrito">
                <button type="submit" id="btn-enviar" disabled class="w-full bg-[#1565C0] text-white font-black py-4 rounded-xl disabled:bg-gray-300 transition-colors uppercase tracking-widest text-sm">
                    Enviar a Cocina
                </button>
            </form>
        </div>
    </aside>

    <dialog id="modalPlatillo" class="bg-white p-6 rounded-3xl shadow-2xl w-full max-w-md backdrop:bg-black/60 backdrop:backdrop-blur-sm">
        <h3 id="modalNombre" class="font-black text-xl text-[#0A1F3D] mb-4 border-b border-gray-100 pb-3">Personalizar</h3>
        
        <div id="seccion-tamano">
            <label class="block font-black text-[#1565C0] mb-2 text-xs uppercase tracking-widest">1. Tamano:</label>
            <div class="flex gap-2 mb-5">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="tamano" value="Mediano" class="peer hidden" checked>
                    <div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-3 rounded-xl font-bold transition">Mediano</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="tamano" value="Grande" class="peer hidden">
                    <div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-3 rounded-xl font-bold transition">Grande</div>
                </label>
            </div>
        </div>

        <div id="seccion-proteina">
            <label class="block font-black text-[#1565C0] mb-2 text-xs uppercase tracking-widest">2. Seleccion de Mariscos:</label>
            <div class="grid grid-cols-2 gap-2 mb-6">
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Solo Camaron" class="peer hidden" checked><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Solo Camaron</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Solo Pulpo" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Solo Pulpo</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Solo Ostion" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Solo Ostion</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Camaron y Pulpo" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Camaron y Pulpo</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Ostion y Pulpo" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Ostion y Pulpo</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Campechano" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Campechano</div></label>
            </div>
        </div>

        <div class="flex gap-4 mb-8">
            <div class="w-1/4">
                <label class="font-black text-gray-700 text-xs uppercase mb-1 block">Cant.</label>
                <input type="number" id="modalCant" value="1" min="1" class="border-2 border-gray-200 bg-gray-50 w-full p-3 rounded-xl text-center font-bold text-lg focus:outline-none focus:border-[#1565C0]">
            </div>
            <div class="w-3/4">
                <label class="font-black text-gray-700 text-xs uppercase mb-1 block">Comentario</label>
                <input type="text" id="modalNota" class="border-2 border-orange-200 bg-orange-50 w-full p-3 rounded-xl font-medium text-orange-900 placeholder-orange-300 focus:outline-none focus:border-orange-400">
            </div>
        </div>

        <div class="flex gap-3">
            <button onclick="document.getElementById('modalPlatillo').close()" class="w-1/3 bg-gray-200 hover:bg-gray-300 text-gray-700 py-4 rounded-xl font-black transition">Cancelar</button>
            <button onclick="guardarAlCarrito()" class="w-2/3 bg-[#1565C0] hover:bg-blue-700 text-white py-4 rounded-xl font-black shadow-lg shadow-blue-500/30 transition flex justify-center items-center gap-2">
                <i class="fa-solid fa-plus"></i> Anadir a Orden
            </button>
        </div>
    </dialog>

    <script>
        // LA MAGIA: Creamos una llave única para la memoria de esta mesa en especifico
        const MESA_ID = <?= $mesa['id_mesa'] ?>;
        const STORAGE_KEY = 'carrito_mangle_mesa_' + MESA_ID;

        // Al iniciar, buscamos si ya había algo guardado en la memoria de esta mesa
        let carrito = JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
        let pActual = {};

        // Apenas cargue la página, dibujamos el carrito de inmediato
        window.onload = function() {
            dibujarCarrito();
        };

        function abrirModal(id, nombre, precio, subcategoria) {
            pActual = { id: id, nombre: nombre, precio: parseFloat(precio), subcategoria: subcategoria };
            
            document.getElementById('modalNombre').innerText = nombre;
            document.getElementById('modalCant').value = 1;
            
            let inputNota = document.getElementById('modalNota');
            inputNota.value = ''; 
            
            const subDulces = ['Pasteles', 'Flanes', 'Postres', 'Helados', 'Cafeteria'];
            const subBebidas = ['Mixologia', 'Mocktails', 'Clasica', 'Spritz', 'Cervezas', 'Aguas Frescas', 'Refrescos', 'Destilados', 'Vinos'];
            
            if (subDulces.includes(subcategoria)) {
                inputNota.placeholder = "Ej. Para llevar, extra chocolate...";
            } else if (subBebidas.includes(subcategoria)) {
                inputNota.placeholder = "Ej. Sin hielo, en vaso de plastico...";
            } else {
                inputNota.placeholder = "Ej. Sin cebolla, extra limon...";
            }

            document.querySelector('input[name="tamano"][value="Mediano"]').checked = true;
            document.querySelector('input[name="proteina"][value="Solo Camaron"]').checked = true;

            let divTamano = document.getElementById('seccion-tamano');
            let divProteina = document.getElementById('seccion-proteina');
            const requiereOpciones = ['Cocteles', 'Botanas', 'Cazuelas', 'Aguachiles'];

            if (requiereOpciones.includes(subcategoria)) {
                divTamano.classList.remove('hidden');
                divProteina.classList.remove('hidden');
            } else {
                divTamano.classList.add('hidden');
                divProteina.classList.add('hidden');
            }

            document.getElementById('modalPlatillo').showModal();
        }

        function guardarAlCarrito() {
            let cant = parseInt(document.getElementById('modalCant').value);
            let nota = document.getElementById('modalNota').value;

            let precioFinal = pActual.precio;
            let nombreFinal = pActual.nombre;

            const requiereOpciones = ['Cocteles', 'Botanas', 'Cazuelas', 'Aguachiles'];

            if (requiereOpciones.includes(pActual.subcategoria)) {
                let tamano = document.querySelector('input[name="tamano"]:checked').value;
                let proteina = document.querySelector('input[name="proteina"]:checked').value;
                
                if (tamano === 'Grande') {
                    if (pActual.subcategoria === 'Cocteles') precioFinal += 46;     
                    if (pActual.subcategoria === 'Botanas') precioFinal += 59;      
                    if (pActual.subcategoria === 'Aguachiles') precioFinal += 55;   
                }
                nombreFinal += ` (${tamano}) - ${proteina}`;
            }

            carrito.push({
                nombre: nombreFinal,
                precio: precioFinal, 
                cant: cant, 
                nota: nota
            });

            // GUARDAR ESTADO: Metemos el carrito actualizado a la memoria del navegador
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));

            document.getElementById('modalPlatillo').close();
            dibujarCarrito();
        }

        function quitar(index) {
            carrito.splice(index, 1);
            // GUARDAR ESTADO: Si borra algo, actualizamos la memoria también
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
            dibujarCarrito();
        }

        function dibujarCarrito() {
            let html = '';
            let total = 0;

            carrito.forEach((c, index) => {
                let subtotal = c.precio * c.cant;
                total += subtotal;
                
                html += `
                <div class="bg-white p-4 rounded-xl shadow-sm border border-blue-100 relative">
                    <div class="pr-6">
                        <div class="font-bold text-sm text-[#0A1F3D] leading-tight">${c.nombre}</div>
                        <div class="text-[#1565C0] font-black mt-1">$${subtotal.toFixed(2)}</div>
                    </div>
                    <div class="flex justify-between items-end mt-2">
                        <span class="text-xs font-semibold text-gray-500">$${c.precio.toFixed(2)} x ${c.cant} ${c.nota ? '<br><span class="text-orange-500 bg-orange-50 px-1 rounded inline-block mt-1">💬 '+c.nota+'</span>' : ''}</span>
                    </div>
                    <button type="button" onclick="quitar(${index})" class="absolute top-3 right-3 text-red-300 hover:text-red-600 bg-red-50 w-6 h-6 flex items-center justify-center rounded transition">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>`;
            });

            if(carrito.length === 0){
                html = `<div class="text-center mt-10"><i class="fa-solid fa-cart-shopping text-4xl text-gray-300 mb-3"></i><p class="text-gray-400 font-medium">Carrito vacio</p></div>`;
            }

            document.getElementById('carrito-items').innerHTML = html;
            document.getElementById('total-txt').innerText = '$' + total.toFixed(2);
            document.getElementById('datos_carrito').value = JSON.stringify(carrito);
            document.getElementById('btn-enviar').disabled = carrito.length === 0;
        }

        // LIMPIAR MEMORIA: Si presiona "Enviar a Cocina", destruimos la memoria para vaciar el carrito
        document.getElementById('form-orden').addEventListener('submit', function() {
            sessionStorage.removeItem(STORAGE_KEY);
        });
    </script>
</body>
</html>