// public/js/caja.js

let porcentajeActual = 0;
let montoPropina = 0;
let granTotal = 0;
let metodoPago = 'efectivo';
let stringRecibido = "";
let totalRecibido = 0;

function inicializarCaja() {
    if (typeof CONFIG_CAJA !== 'undefined' && CONFIG_CAJA.consumoTotal > 0) {
        granTotal = CONFIG_CAJA.consumoTotal;
        calcularCambio();
    }
}

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
        if (val === '.' && stringRecibido.includes('.')) return;
        
        if (val === '.' && stringRecibido === "") {
            stringRecibido = "0.";
        } else {
            if (stringRecibido === "0" && val !== ".") {
                stringRecibido = val;
            } else {
                if (stringRecibido.includes('.')) {
                    let partes = stringRecibido.split('.');
                    if (partes[1].length >= 2) return; 
                }
                stringRecibido += val;
            }
        }
    }

    totalRecibido = parseFloat(stringRecibido) || 0;
    document.getElementById('lbl_recibido').innerText = stringRecibido === "" ? '$0' : '$' + stringRecibido;
    calcularCambio();
}

function calcularCambio() {
    let divCalculo = document.getElementById('div_calculo');
    if(!divCalculo) return;
    
    let btnLiquidar = document.getElementById('btn_liquidar_seguro'); // Usamos el ID de seguridad
    let inputMetodo = document.getElementById('input_metodo');
    let inputEfe = document.getElementById('input_efectivo');
    let inputTar = document.getElementById('input_tarjeta');

    let diferencia = totalRecibido - granTotal;

    if (metodoPago === 'tarjeta') {
        inputMetodo.value = 'tarjeta';
        inputEfe.value = 0;
        inputTar.value = granTotal;
        divCalculo.innerHTML = `<div class="bg-blue-50 text-blue-600 py-1 px-2 rounded font-bold border border-blue-100">Cobro total a Tarjeta (Terminal)</div>`;
        if(btnLiquidar) {
            btnLiquidar.innerHTML = `Liquidar en Tarjeta <i class="fa-solid fa-credit-card"></i>`;
            btnLiquidar.className = "w-full bg-blue-600 hover:bg-blue-800 text-white font-black text-lg py-5 rounded-2xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-3";
        }
    } else if (totalRecibido === 0) {
        inputMetodo.value = 'efectivo';
        inputEfe.value = granTotal;
        inputTar.value = 0;
        divCalculo.innerHTML = '';
        if(btnLiquidar) {
            btnLiquidar.innerHTML = `Ingresa Monto a Cobrar <i class="fa-solid fa-keyboard"></i>`;
            btnLiquidar.className = "w-full bg-gray-200 text-gray-400 font-black text-lg py-5 rounded-2xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-3 cursor-not-allowed";
        }
    } else if (diferencia < 0) {
        let cobroTarjeta = Math.abs(diferencia);
        inputMetodo.value = 'mixto';
        inputEfe.value = totalRecibido;
        inputTar.value = cobroTarjeta;
        divCalculo.innerHTML = `<div class="bg-orange-50 text-orange-600 py-1 px-2 rounded font-bold border border-orange-100">Pasar Terminal por: $${cobroTarjeta.toFixed(2)}</div>`;
        if(btnLiquidar) {
            btnLiquidar.innerHTML = `Liquidar Mixto <i class="fa-solid fa-cash-register"></i>`;
            btnLiquidar.className = "w-full bg-orange-500 hover:bg-orange-600 text-white font-black text-lg py-5 rounded-2xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-3";
        }
    } else {
        inputMetodo.value = 'efectivo';
        inputEfe.value = granTotal;
        inputTar.value = 0;
        divCalculo.innerHTML = `<div class="bg-green-50 text-green-600 py-1 px-2 rounded font-bold border border-green-100">Cambio a Entregar: $${diferencia.toFixed(2)}</div>`;
        if(btnLiquidar) {
            btnLiquidar.innerHTML = `Liquidar en Efectivo <i class="fa-solid fa-money-bill-wave"></i>`;
            btnLiquidar.className = "w-full bg-green-500 hover:bg-green-600 text-white font-black text-lg py-5 rounded-2xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-3";
        }
    }
}

// Lógica de Auditoría
function abrirModalCorte() {
    document.getElementById('modalCorteCaja').showModal();
    calcularAuditoria();
}

function calcularAuditoria() {
    let fondo = parseFloat(document.getElementById('fondo_inicial').value) || 0;
    let fisico = parseFloat(document.getElementById('efectivo_fisico').value) || 0;
    
    let esperado = fondo + CONFIG_CAJA.ventasEfectivoDia;
    let diferencia = fisico - esperado;
    let lblDif = document.getElementById('lbl_diferencia');

    lblDif.innerText = diferencia.toFixed(2);

    if (diferencia < 0) {
        lblDif.classList.remove('text-[#00A97F]', 'text-[#00B4D8]', 'text-gray-400');
        lblDif.classList.add('text-red-500');
    } else {
        lblDif.innerText = (diferencia > 0 ? '+' : '') + diferencia.toFixed(2);
        lblDif.classList.remove('text-red-500', 'text-gray-400');
        lblDif.classList.add('text-[#00A97F]'); 
    }

    document.getElementById('input_fondo').value = fondo;
    document.getElementById('input_fisico').value = fisico;
    document.getElementById('input_dif').value = diferencia;
}

// --- BLINDAJE DE COBRO PARA LA CAJA NEGRA ---
document.addEventListener('DOMContentLoaded', function() {
    inicializarCaja();

    const btnLiquidarSeguro = document.getElementById('btn_liquidar_seguro');

    if (btnLiquidarSeguro) {
        btnLiquidarSeguro.addEventListener('click', function() {
            let form = document.getElementById('form_cobro_seguro');
            
            let radio = document.querySelector('input[name="metodo"]:checked');
            let metodo = radio ? radio.value : 'efectivo';
            document.getElementById('input_metodo').value = metodo;
            
            let txtConsumo = document.getElementById('lbl_consumo').innerText.replace(/[^0-9.]/g, '');
            let txtPropina = document.getElementById('lbl_monto_propina').innerText.replace(/[^0-9.]/g, '');
            let txtRecibido = document.getElementById('lbl_recibido').innerText.replace(/[^0-9.]/g, '');
            
            let totalPagar = parseFloat(txtConsumo) + parseFloat(txtPropina);
            let recibido = parseFloat(txtRecibido);
            
            if (metodo === 'tarjeta') {
                document.getElementById('input_tarjeta').value = totalPagar;
                document.getElementById('input_efectivo').value = 0;
            } else {
                if (recibido < totalPagar) {
                    alert("⚠️ El efectivo recibido ($" + recibido + ") no alcanza para cubrir la cuenta ($" + totalPagar + ").");
                    return; 
                }
                document.getElementById('input_efectivo').value = recibido;
                document.getElementById('input_tarjeta').value = 0;
            }
            
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando Pago...';
            this.classList.add('opacity-75', 'cursor-not-allowed');
            form.submit();
        });
    }
});