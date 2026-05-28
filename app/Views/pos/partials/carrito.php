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

<script>
    // 1. LLAVE ÚNICA PARA TODO EL SISTEMA (Insertada directo con PHP)
    const STORAGE_KEY = 'carrito_mangle_mesa_<?= $mesa['id_mesa'] ?>';
    
    let carrito = JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
    let pActual = {};

    // 2. Dibujar automáticamente al cargar cualquier pantalla
    document.addEventListener("DOMContentLoaded", function() {
        dibujarCarrito();
    });

    function dibujarCarrito() {
        let container = document.getElementById('carrito-items');
        if (!container) return;

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

        container.innerHTML = html;
        document.getElementById('total-txt').innerText = '$' + total.toFixed(2);
        
        let datosCarrito = document.getElementById('datos_carrito');
        if(datosCarrito) datosCarrito.value = JSON.stringify(carrito);
        
        let btnEnviar = document.getElementById('btn-enviar');
        if(btnEnviar) btnEnviar.disabled = carrito.length === 0;
    }

    function quitar(index) {
        carrito.splice(index, 1);
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
        dibujarCarrito();
    }

    // Funciones del Modal (Protegidas para no chocar en Categorías)
    function abrirModal(id, nombre, precio, subcategoria) {
        pActual = { id: id, nombre: nombre, precio: parseFloat(precio), subcategoria: subcategoria };
        
        let modalNombre = document.getElementById('modalNombre');
        if(!modalNombre) return; // Si estamos en categorías, aquí se detiene y no marca error
        
        modalNombre.innerText = nombre;
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

        let medianoRad = document.querySelector('input[name="tamano"][value="Mediano"]');
        if(medianoRad) medianoRad.checked = true;

        let camaronRad = document.querySelector('input[name="proteina"][value="Solo Camaron"]');
        if(camaronRad) camaronRad.checked = true;

        let divTamano = document.getElementById('seccion-tamano');
        let divProteina = document.getElementById('seccion-proteina');
        const requiereOpciones = ['Cocteles', 'Botanas', 'Cazuelas', 'Aguachiles'];

        if (requiereOpciones.includes(subcategoria)) {
            if(divTamano) divTamano.classList.remove('hidden');
            if(divProteina) divProteina.classList.remove('hidden');
        } else {
            if(divTamano) divTamano.classList.add('hidden');
            if(divProteina) divProteina.classList.add('hidden');
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
            id: pActual.id, // VITAL: Manda el id para la BD
            nombre: nombreFinal,
            precio: precioFinal, 
            cant: cant, 
            nota: nota
        });

        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
        document.getElementById('modalPlatillo').close();
        dibujarCarrito();
    }

    // Vaciar memoria si se manda la comanda
    let formOrden = document.getElementById('form-orden');
    if(formOrden){
        formOrden.addEventListener('submit', function() {
            sessionStorage.removeItem(STORAGE_KEY);
        });
    }
</script>