// public/js/capitan.js

let mesaSeleccionada = null;
let urlAccionPrincipal = "";

function seleccionarMesa(id, numero, estado, items, total, mesero) {
    mesaSeleccionada = id;
    
    // 1. Quitar estado de "Deshabilitado" al panel lateral
    let panel = document.getElementById('panel_acciones');
    if (panel) {
        panel.classList.remove('opacity-50', 'pointer-events-none');
    }

    // 2. Llenar la información básica textualmente
    if (document.getElementById('lbl_titulo_mesa')) {
        document.getElementById('lbl_titulo_mesa').innerText = 'Mesa ' + numero;
    }
    if (document.getElementById('lbl_mesero_panel')) {
        document.getElementById('lbl_mesero_panel').innerText = 'Atendido por: ' + mesero;
    }
    if (document.getElementById('lbl_items_panel')) {
        document.getElementById('lbl_items_panel').innerText = items + ' artículos';
    }

    // 3. Pintar la etiqueta de estado dinámicamente
    let lblStatus = document.getElementById('lbl_status_panel');
    if (lblStatus) {
        lblStatus.innerText = estado;
        lblStatus.className = 'px-3 py-1 rounded-md text-xs font-bold ' + 
            (estado === 'Disponible' ? 'bg-green-100 text-green-600' : 
            (estado === 'Ocupada' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'));
    }

    // 4. Control de visibilidad de botones de acción
    let btnDividir = document.getElementById('btn_dividir');
    let btnTransferir = document.getElementById('btn_transferir');
    let btnCancelar = document.getElementById('btn_cancelar');
    let btnReabrir = document.getElementById('btn_reabrir');
    let btnVerOrden = document.getElementById('btn_ver_orden');

    if (btnVerOrden) {
        if (estado === 'Disponible') {
            if (btnDividir) btnDividir.style.display = 'none';
            if (btnTransferir) btnTransferir.style.display = 'none';
            if (btnCancelar) btnCancelar.style.display = 'none';
            if (btnReabrir) btnReabrir.style.display = 'none';
            
            btnVerOrden.innerText = 'Abrir Mesa y Tomar Orden';
            btnVerOrden.className = "w-full bg-[#00B4D8] text-white font-black py-4 rounded-2xl transition hover:bg-[#0096B4] shadow-md uppercase tracking-widest text-sm flex justify-center items-center gap-2";
            
            // Asignamos la ruta para abrir comanda nueva
            urlAccionPrincipal = CONFIG_CAPITAN.baseUrl + "pos/mesa/" + id;
        } else {
            if (btnDividir) btnDividir.style.display = 'flex';
            if (btnTransferir) btnTransferir.style.display = 'flex';
            if (btnCancelar) btnCancelar.style.display = 'flex';
            // Solo se puede reabrir si ya está impresa la cuenta
            if (btnReabrir) btnReabrir.style.display = (estado === 'Cuenta Impresa') ? 'flex' : 'none'; 
            
            btnVerOrden.innerText = 'Ver Detalle de Orden';
            btnVerOrden.className = "w-full bg-[#E2E8F0] text-[#475569] font-black py-4 rounded-2xl transition hover:bg-[#0A1F3D] hover:text-white shadow-sm uppercase tracking-widest text-sm flex justify-center items-center gap-2";
            
            // Asignamos la ruta para ver el ticket actual
            urlAccionPrincipal = CONFIG_CAPITAN.baseUrl + "pos/ver_comanda/" + id;
        }
    }
}

// Dispara la redirección al hacer clic en el botón principal
function ejecutarAccionPrincipal() {
    if (urlAccionPrincipal !== "") {
        window.location.href = urlAccionPrincipal;
    }
}

// Controladores para ventanas modales y vistas secundarias
function abrirModalTransferir() { 
    if(!mesaSeleccionada) return;
    if (document.getElementById('input_transferir_origen')) {
        document.getElementById('input_transferir_origen').value = mesaSeleccionada;
    }
    if (document.getElementById('modalTransferir')) {
        document.getElementById('modalTransferir').showModal(); 
    }
}

function reabrirCuenta() {
    if(!mesaSeleccionada) return;
    if(confirm('¿Estás seguro de reabrir esta cuenta?')) {
        window.location.href = CONFIG_CAPITAN.baseUrl + "capitan/reabrir/" + mesaSeleccionada;
    }
}

function abrirModalDividir() { 
    if(!mesaSeleccionada) return;
    window.location.href = CONFIG_CAPITAN.baseUrl + "capitan/detalle_orden/" + mesaSeleccionada + "/dividir"; 
}

function abrirModalCancelar() { 
    if(!mesaSeleccionada) return;
    window.location.href = CONFIG_CAPITAN.baseUrl + "capitan/detalle_orden/" + mesaSeleccionada + "/cancelar"; 
}