// ==========================================
// MÓDULO CAPITÁN - LÓGICA DE INTERFAZ
// ==========================================

let mesaSeleccionada = null;

function seleccionarMesa(id, numero, estado, items, total, mesero) {
    mesaSeleccionada = id;
    
    // 1. Quitar estado de "Deshabilitado" al panel
    let panel = document.getElementById('panel_acciones');
    panel.classList.remove('opacity-50', 'pointer-events-none');

    // 2. Llenar la información básica
    document.getElementById('lbl_titulo_mesa').innerText = 'Mesa ' + numero;
    document.getElementById('lbl_mesero_panel').innerText = 'Atendido por: ' + mesero;
    document.getElementById('lbl_items_panel').innerText = items + ' artículos';
    document.getElementById('input_mesa_admin').value = id;

    // 3. Pintar etiqueta de estado
    let lblStatus = document.getElementById('lbl_status_panel');
    lblStatus.innerText = estado;
    lblStatus.className = 'px-3 py-1 rounded-md text-xs font-bold ' + 
        (estado === 'Disponible' ? 'bg-green-100 text-green-600' : 
        (estado === 'Ocupada' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'));

    // 4. Lógica de habilitar/deshabilitar botones según el estado
    let btnDividir = document.getElementById('btn_dividir');
    let btnTransferir = document.getElementById('btn_transferir');
    let btnCancelar = document.getElementById('btn_cancelar');
    let btnReabrir = document.getElementById('btn_reabrir');
    let btnVerOrden = document.getElementById('btn_ver_orden');

    if (estado === 'Disponible') {
        // Si está disponible, no hay nada que dividir ni cancelar
        btnDividir.style.display = 'none';
        btnTransferir.style.display = 'none';
        btnCancelar.style.display = 'none';
        btnReabrir.style.display = 'none';
        
        btnVerOrden.innerText = 'Abrir Mesa y Tomar Orden';
        btnVerOrden.className = "w-full bg-[#00B4D8] text-white font-bold py-4 rounded-xl transition hover:bg-[#0096B4] shadow-md";
    } else {
        // Si está ocupada o por pagar, mostramos herramientas administrativas
        btnDividir.style.display = 'flex';
        btnTransferir.style.display = 'flex';
        btnCancelar.style.display = 'flex';
        
        btnReabrir.style.display = (estado === 'Cuenta Impresa') ? 'flex' : 'none';
        
        btnVerOrden.innerText = 'Ver Detalle de Orden';
        btnVerOrden.className = "w-full bg-[#E2E8F0] text-[#475569] font-bold py-4 rounded-xl transition hover:bg-[#0A1F3D] hover:text-white shadow-sm";
    }
}

// LÓGICA DE MODALES
function abrirModalTransferir() { 
    if(!mesaSeleccionada) return;
    document.getElementById('input_transferir_origen').value = mesaSeleccionada;
    document.getElementById('modalTransferir').showModal(); 
}

function reabrirCuenta() {
    if(!mesaSeleccionada) return;
    if(confirm('¿Estás seguro de reabrir esta cuenta?')) {
        window.location.href = CONFIG_CAPITAN.baseUrl + "capitan/reabrir/" + mesaSeleccionada;
    }
}

function abrirModalCancelar() { 
    if(!mesaSeleccionada) return;
    // Viajamos a la pantalla de detalle en modo "cancelar"
    window.location.href = CONFIG_CAPITAN.baseUrl + "capitan/detalle_orden/" + mesaSeleccionada + "/cancelar"; 
}

function abrirModalDividir() { 
    if(!mesaSeleccionada) return;
    // Viajamos a la pantalla de detalle en modo "dividir"
    window.location.href = CONFIG_CAPITAN.baseUrl + "capitan/detalle_orden/" + mesaSeleccionada + "/dividir"; 
}