// ==========================================
// MÓDULO DE CAJA - LÓGICA DE COBRO Y CORTE
// ==========================================

let porcentajeActual = 0;
let montoPropina = 0;
let granTotal = 0;
let metodoPago = 'efectivo';
let stringRecibido = "";
let totalRecibido = 0;

// Inicializador que lee los datos desde PHP
function inicializarCaja() {
    if (typeof CONFIG_CAJA !== 'undefined' && CONFIG_CAJA.consumoTotal > 0) {
        granTotal = CONFIG_CAJA.consumoTotal;
        calcularCambio();
    }
}

// ==========================================
// 1. LÓGICA DE COBRO Y CALCULADORA
// ==========================================
function setPropina(porcentaje) {
    document.getElementById('input_propina_custom').value = ''; 
    ejecutarCalculoPropina(porcentaje);
    
    let btnActivo = document.getElementById('btn_prop_' + porcentaje);
    if(btnActivo) {
        btnActivo.classList.remove('bg-[#F1F5F9]', 'text-gray-500');
        btnActivo.classList.add('bg-[#00B4D8]', 'text-white', 'shadow-md');
    }
}

function setPropinaCustom(valor) {
    let porcentaje = parseFloat(valor);
    if (isNaN(porcentaje) || porcentaje < 0) porcentaje = 0;
    ejecutarCalculoPropina(porcentaje);
}

function ejecutarCalculoPropina(porcentaje) {
    porcentajeActual = porcentaje;
    montoPropina = CONFIG_CAJA.consumoTotal * (porcentaje / 100);
    granTotal = CONFIG_CAJA.consumoTotal + montoPropina;

    document.getElementById('lbl_monto_propina').innerText = '+ $' + montoPropina.toFixed(2);
    document.getElementById('lbl_gran_total').innerText = '$' + granTotal.toFixed(2);

    let botones = document.querySelectorAll('.btn-prop');
    botones.forEach(btn => {
        btn.classList.remove('bg-[#00B4D8]', 'text-white', 'shadow-md');
        btn.classList.add('bg-[#F1F5F9]', 'text-gray-500');
    });

    if (metodoPago === 'tarjeta') {
        totalRecibido = granTotal;
        stringRecibido = "";
        document.getElementById('lbl_recibido').innerText = '$' + totalRecibido.toFixed(2);
    }
    calcularCambio();
}

function setMetodo(metodo) {
    metodoPago = metodo;
    if(metodo === 'tarjeta') {
        totalRecibido = granTotal;
        stringRecibido = "";
        document.getElementById('lbl_recibido').innerText = '$' + totalRecibido.toFixed(2);
    } else {
        stringRecibido = "";
        totalRecibido = 0;
        document.getElementById('lbl_recibido').innerText = '$0';
    }
    calcularCambio();
}

function tecla(val) {
    if(metodoPago === 'tarjeta') return; 

    if (val === 'back') {
        stringRecibido = stringRecibido.slice(0, -1);
    } else {
        if(stringRecibido === "" && val === "00") return;
        stringRecibido += val;
    }

    totalRecibido = parseInt(stringRecibido) || 0;
    document.getElementById('lbl_recibido').innerText = '$' + totalRecibido;
    calcularCambio();
}

function calcularCambio() {
    if (!document.getElementById('div_calculo')) return;

    let divCalculo = document.getElementById('div_calculo');
    let btnLiquidar = document.getElementById('btn_liquidar');
    let inputMetodo = document.getElementById('input_metodo');
    let inputEfe = document.getElementById('input_efectivo');
    let inputTar = document.getElementById('input_tarjeta');

    let diferencia = totalRecibido - granTotal;

    if (metodoPago === 'tarjeta') {
        inputMetodo.value = 'tarjeta';
        inputEfe.value = 0;
        inputTar.value = granTotal;
        divCalculo.innerHTML = `<div class="bg-blue-50 text-blue-600 py-1 px-2 rounded font-bold border border-blue-100">Cobro total a Tarjeta</div>`;
        btnLiquidar.innerHTML = `Liquidar en Tarjeta <i class="fa-solid fa-credit-card"></i>`;
        btnLiquidar.className = "w-full bg-blue-600 hover:bg-blue-800 text-white font-black text-sm py-4 rounded-xl transition shadow-md uppercase tracking-widest flex justify-center items-center gap-2";
    } else if (totalRecibido === 0) {
        inputMetodo.value = 'efectivo';
        inputEfe.value = granTotal;
        inputTar.value = 0;
        divCalculo.innerHTML = '';
        btnLiquidar.innerHTML = `Liquidar y Liberar Mesa <i class="fa-solid fa-check-double"></i>`;
        btnLiquidar.className = "w-full bg-[#EEF2F6] text-[#64748B] hover:bg-[#0A1F3D] hover:text-white font-black text-sm py-4 rounded-xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-2";
    } else if (diferencia < 0) {
        let cobroTarjeta = Math.abs(diferencia);
        inputMetodo.value = 'mixto';
        inputEfe.value = totalRecibido;
        inputTar.value = cobroTarjeta;
        divCalculo.innerHTML = `<div class="bg-orange-50 text-orange-600 py-1 px-2 rounded font-bold border border-orange-100">Pasar Terminal por: $${cobroTarjeta.toFixed(2)}</div>`;
        btnLiquidar.innerHTML = `Liquidar ($${totalRecibido} Efe + $${cobroTarjeta.toFixed(2)} Tarj) <i class="fa-solid fa-cash-register"></i>`;
        btnLiquidar.className = "w-full bg-orange-500 hover:bg-orange-600 text-white font-black text-sm py-4 rounded-xl transition shadow-md uppercase tracking-widest flex justify-center items-center gap-2";
    } else {
        inputMetodo.value = 'efectivo';
        inputEfe.value = granTotal;
        inputTar.value = 0;
        divCalculo.innerHTML = `<div class="bg-green-50 text-green-600 py-1 px-2 rounded font-bold border border-green-100">Cambio a Entregar: $${diferencia.toFixed(2)}</div>`;
        btnLiquidar.innerHTML = `Liquidar (Cambio: $${diferencia.toFixed(2)}) <i class="fa-solid fa-money-bill-wave"></i>`;
        btnLiquidar.className = "w-full bg-green-500 hover:bg-green-600 text-white font-black text-sm py-4 rounded-xl transition shadow-md uppercase tracking-widest flex justify-center items-center gap-2";
    }
}

// ==========================================
// 2. LÓGICA DE AUDITORÍA Y CORTE DE CAJA
// ==========================================
function abrirModalCorte() {
    document.getElementById('modalCorteCaja').showModal();
    calcularAuditoria();
}

function calcularAuditoria() {
    // Leer valores de los inputs
    let fondo = parseFloat(document.getElementById('fondo_inicial').value) || 0;
    let fisico = parseFloat(document.getElementById('efectivo_fisico').value) || 0;
    
    // El efectivo total esperado es el Fondo Inicial + Las ventas en efectivo del turno
    let ventasEfectivo = CONFIG_CAJA.ventasEfectivo || 0;
    let esperado = fondo + ventasEfectivo;
    
    let diferencia = fisico - esperado;
    let lblDif = document.getElementById('lbl_diferencia');

    lblDif.innerText = diferencia.toFixed(2);

    // Formato de colores y signo
    if (diferencia < 0) {
        lblDif.classList.remove('text-[#00B4D8]', 'text-gray-400');
        lblDif.classList.add('text-red-500');
    } else {
        lblDif.innerText = (diferencia > 0 ? '+' : '') + diferencia.toFixed(2);
        lblDif.classList.remove('text-red-500', 'text-gray-400');
        lblDif.classList.add('text-[#00A97F]'); // Verde tipo WhatsApp de tu diseño
    }

    // Actualizar hidden inputs para mandar al backend
    document.getElementById('input_fondo').value = fondo;
    document.getElementById('input_fisico').value = fisico;
    document.getElementById('input_dif').value = diferencia;
}

// Auto-Iniciar cuando carga el DOM
document.addEventListener("DOMContentLoaded", inicializarCaja);