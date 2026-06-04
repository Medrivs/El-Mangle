let mesaSeleccionada = null;
let urlAccionPrincipal = "";

function seleccionarMesa(id, numero, estado, items, total, mesero) {
    mesaSeleccionada = id;
    
    let panel = document.getElementById('panel_acciones');
    if (panel) {
        panel.classList.remove('opacity-50', 'pointer-events-none');
    }

    if (document.getElementById('lbl_titulo_mesa')) {
        document.getElementById('lbl_titulo_mesa').innerText = 'Mesa ' + numero;
    }
    if (document.getElementById('lbl_mesero_panel')) {
        document.getElementById('lbl_mesero_panel').innerText = 'Atendido por: ' + mesero;
    }
    if (document.getElementById('lbl_items_panel')) {
        document.getElementById('lbl_items_panel').innerText = items + ' artículos';
    }

    let lblStatus = document.getElementById('lbl_status_panel');
    if (lblStatus) {
        lblStatus.innerText = estado;
        lblStatus.className = 'px-3 py-1 rounded-md text-xs font-bold ' + 
            (estado === 'Disponible' ? 'bg-green-100 text-green-600' : 
            (estado === 'Ocupada' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600'));
    }

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
            
            urlAccionPrincipal = CONFIG_CAPITAN.baseUrl + "pos/mesa/" + id;
        } else {
            if (btnDividir) btnDividir.style.display = 'flex';
            if (btnTransferir) btnTransferir.style.display = 'flex';
            if (btnCancelar) btnCancelar.style.display = 'flex';
            if (btnReabrir) btnReabrir.style.display = (estado === 'Cuenta Impresa') ? 'flex' : 'none'; 
            
            btnVerOrden.innerText = 'Ver Detalle de Orden';
            btnVerOrden.className = "w-full bg-[#E2E8F0] text-[#475569] font-black py-4 rounded-2xl transition hover:bg-[#0A1F3D] hover:text-white shadow-sm uppercase tracking-widest text-sm flex justify-center items-center gap-2";
            
            urlAccionPrincipal = CONFIG_CAPITAN.baseUrl + "pos/ver_comanda/" + id;
        }
    }
}

function ejecutarAccionPrincipal() {
    if (urlAccionPrincipal !== "") {
        window.location.href = urlAccionPrincipal;
    }
}

// funcion para abrir el modal global de zonas
function abrirModalZonas() {
    let modal = document.getElementById('modalZonas');
    if (modal) modal.showModal();
}

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
    if(confirm('¿estas seguro de reabrir esta cuenta?')) {
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