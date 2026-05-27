// public/js/carrito.js

// Recuperamos el carrito de la memoria del navegador
// OJO: Usamos el ID de la mesa desde el input oculto o una variable global
// Pero para que sea universal, usaremos el sessionStorage directamente
const STORAGE_KEY = 'carrito_mesa_' + document.querySelector('input[name="id_mesa"]').value;
let carrito = JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];

function dibujarCarrito() {
    let container = document.getElementById('carrito-items');
    if (!container) return; // Si no hay carrito en esta pantalla, no hacemos nada

    let html = '';
    let total = 0;

    carrito.forEach((c, index) => {
        let subtotal = c.precio * c.cant;
        total += subtotal;
        
        html += `
        <div class="bg-white p-3 rounded shadow-sm text-sm border-b">
            <b>${c.nombre}</b><br>$${subtotal.toFixed(2)} 
            <small>${c.nota ? '💬 '+c.nota : ''}</small>
            <button type="button" onclick="quitar(${index})" class="text-red-500 text-xs block hover:underline">Quitar</button>
        </div>`;
    });

    container.innerHTML = html || '<p class="text-center text-gray-400 mt-10">Carrito vacio</p>';
    document.getElementById('total-txt').innerText = '$' + total.toFixed(2);
    
    let inputDatos = document.getElementById('datos_carrito');
    if (inputDatos) inputDatos.value = JSON.stringify(carrito);
    
    let btnEnviar = document.getElementById('btn-enviar');
    if (btnEnviar) btnEnviar.disabled = carrito.length === 0;
}

function quitar(index) {
    carrito.splice(index, 1);
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
    dibujarCarrito();
}

// Limpiar al enviar
document.getElementById('form-orden').addEventListener('submit', function() {
    sessionStorage.removeItem(STORAGE_KEY);
});