<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - El Mangle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Ocultar las flechas del input numérico para que se vea limpio */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="bg-[#F8FAFC] flex flex-col h-screen font-sans overflow-hidden">
    
    <!-- HEADER -->
    <header class="bg-[#0A1F3D] text-white flex justify-between items-center px-6 py-3 shrink-0 shadow-md z-10">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full border-2 border-[#00B4D8] flex items-center justify-center text-[#00B4D8] text-lg bg-[#0A1F3D]">
                <i class="fa-solid fa-anchor"></i>
            </div>
            <div class="leading-tight">
                <div class="text-[#00B4D8] text-[10px] font-black tracking-widest uppercase">El Mangle</div>
                <div class="text-white text-sm font-bold tracking-wide uppercase">Caja - Cajero</div>
            </div>
        </div>
        
        <div class="flex gap-4">
            <form action="<?= base_url('caja/corte_caja') ?>" method="POST">
                <button type="submit" class="bg-[#0f4c75] hover:bg-[#1565C0] border border-[#0f4c75] px-5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg">
                    <i class="fa-solid fa-wallet"></i> CORTE CAJA
                </button>
            </form>
            <a href="<?= base_url('login/logout') ?>" class="border border-gray-600 hover:bg-gray-800 px-5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> SALIR
            </a>
        </div>
    </header>

    <!-- ALERTAS -->
    <?php if(session()->getFlashdata('error')): ?>
        <div class="bg-red-500 text-white font-bold text-center py-1 text-sm shrink-0 animate-pulse">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    <?php if(session()->getFlashdata('success')): ?>
        <div class="bg-green-500 text-white font-bold text-center py-1 text-sm shrink-0">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-1 flex overflow-hidden">
        
        <!-- BARRA LATERAL IZQUIERDA -->
        <aside class="w-[300px] bg-[#F1F5F9] border-r border-gray-200 flex flex-col h-full shrink-0 shadow-inner">
            <div class="p-5 flex justify-between items-center shrink-0">
                <h2 class="text-[#0A1F3D] font-black text-base flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-[#00B4D8]"></i> Por Cobrar
                </h2>
                <span class="bg-[#D0F0FB] text-[#00B4D8] text-xs font-black px-2 py-1 rounded-full"><?= count($mesas) ?></span>
            </div>
            
            <div class="flex-1 overflow-y-auto px-4 pb-4 space-y-2">
                <?php if(empty($mesas)): ?>
                    <div class="text-center text-gray-400 mt-10 font-medium text-sm">No hay cuentas pendientes</div>
                <?php endif; ?>

                <?php foreach($mesas as $m): 
                    $esActiva = ($mesa_activa && $mesa_activa['id_mesa'] == $m['id_mesa']);
                    $bgClase = $esActiva ? 'bg-white border-[#00B4D8] shadow-md ring-2 ring-[#00B4D8]/20' : 'bg-white border-transparent shadow-sm hover:border-gray-300';
                ?>
                    <a href="<?= base_url('caja/index/'.$m['id_mesa']) ?>" class="block border-2 <?= $bgClase ?> rounded-xl p-4 transition-all">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-black text-[#0A1F3D] text-lg">Mesa <?= $m['numero_mesa'] ?></span>
                            <span class="font-black text-[#0A1F3D] text-lg">$<?= number_format($m['total'], 2) ?></span>
                        </div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">
                            <?= $m['mesero'] ?? 'MESA ABIERTA' ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- ÁREA CENTRAL DE COBRO (Derecha) -->
        <section class="flex-1 bg-white p-6 overflow-hidden flex flex-col">
            <?php if($mesa_activa): ?>
                
                <!-- HEADER DEL COBRO -->
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-100 shrink-0">
                    <div>
                        <h2 class="text-2xl font-black text-[#0A1F3D] mb-1">Cobro Mesa <?= $mesa_activa['numero_mesa'] ?></h2>
                        <p class="text-gray-500 font-medium text-xs">Atendido por <span class="uppercase font-bold"><?= $mesa_activa['mesero'] ?? 'ALAN MEDINA' ?></span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 font-black tracking-widest uppercase mb-1">Total a Pagar</p>
                        <p class="text-4xl font-black text-[#00B4D8]" id="lbl_gran_total">$<?= number_format($mesa_activa['total'], 2) ?></p>
                    </div>
                </div>

                <!-- GRID PRINCIPAL QUE NO HACE SCROLL -->
                <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-0">
                    
                    <!-- COLUMNA IZQUIERDA (Info: 7/12 del espacio) -->
                    <div class="lg:col-span-7 flex flex-col gap-4 overflow-y-auto pr-2 pb-2">
                        
                        <!-- DESGLOSE COMPACTO -->
                        <div class="border border-gray-100 rounded-2xl p-4 shadow-sm bg-white">
                            <div class="flex justify-between mb-2 text-sm text-gray-600 font-medium">
                                <span>Subtotal (<?= $mesa_activa['items'] ?> arts)</span>
                                <span>$<?= number_format($mesa_activa['total'] * 0.84, 2) ?></span>
                            </div>
                            <div class="flex justify-between mb-3 text-sm text-gray-600 font-medium pb-3 border-b border-gray-100">
                                <span>IVA (16%)</span>
                                <span>$<?= number_format($mesa_activa['total'] * 0.16, 2) ?></span>
                            </div>
                            <div class="flex justify-between font-black text-[#0A1F3D] text-lg">
                                <span>Consumo Total</span>
                                <span id="lbl_consumo">$<?= number_format($mesa_activa['total'], 2) ?></span>
                            </div>
                        </div>

                        <!-- PROPINA -->
                        <div class="border border-gray-100 rounded-2xl p-4 shadow-sm bg-white">
                            <h4 class="text-[10px] font-black text-gray-400 tracking-widest uppercase mb-3">Propina Opcional</h4>
                            <div class="flex gap-2 mb-3">
                                <button type="button" onclick="setPropina(0)" id="btn_prop_0" class="btn-prop flex-1 py-2 bg-[#00B4D8] text-white font-bold rounded-xl transition shadow-md text-sm">0%</button>
                                <button type="button" onclick="setPropina(10)" id="btn_prop_10" class="btn-prop flex-1 py-2 bg-[#F1F5F9] text-gray-500 hover:bg-gray-200 font-bold rounded-xl transition text-sm">10%</button>
                                <button type="button" onclick="setPropina(15)" id="btn_prop_15" class="btn-prop flex-1 py-2 bg-[#F1F5F9] text-gray-500 hover:bg-gray-200 font-bold rounded-xl transition text-sm">15%</button>
                                <button type="button" onclick="setPropina(20)" id="btn_prop_20" class="btn-prop flex-1 py-2 bg-[#F1F5F9] text-gray-500 hover:bg-gray-200 font-bold rounded-xl transition text-sm">20%</button>
                                <!-- INPUT PROPINA CUSTOM -->
                                <input type="number" step="any" id="input_propina_custom" placeholder="Otro %" class="flex-[1.2] py-2 px-1 text-center bg-[#F1F5F9] text-[#0A1F3D] placeholder-gray-400 focus:bg-white border-2 border-transparent focus:border-[#00B4D8] font-bold rounded-xl outline-none transition text-sm" oninput="setPropinaCustom(this.value)">
                            </div>
                            <div class="flex justify-between items-center bg-[#E1F5FE] text-[#00B4D8] p-2 px-3 rounded-xl font-bold text-sm">
                                <span>Monto Propina</span>
                                <span id="lbl_monto_propina">+ $0.00</span>
                            </div>
                        </div>

                        <!-- MÉTODOS DE PAGO (Botones grandes) -->
                        <div class="grid grid-cols-2 gap-3 h-24 shrink-0 mt-auto">
                            <label class="cursor-pointer h-full">
                                <input type="radio" name="metodo" value="efectivo" class="peer hidden" checked onchange="setMetodo('efectivo')">
                                <div class="h-full border border-gray-200 bg-white peer-checked:border-green-500 peer-checked:text-green-600 peer-checked:bg-green-50 peer-checked:shadow-sm text-gray-400 rounded-2xl flex flex-col items-center justify-center transition">
                                    <i class="fa-solid fa-money-bill-wave text-2xl mb-1"></i>
                                    <span class="font-black text-[11px] uppercase tracking-wide">Efectivo</span>
                                </div>
                            </label>
                            
                            <label class="cursor-pointer h-full">
                                <input type="radio" name="metodo" value="tarjeta" class="peer hidden" onchange="setMetodo('tarjeta')">
                                <div class="h-full border border-gray-200 bg-white peer-checked:border-blue-500 peer-checked:text-blue-600 peer-checked:bg-blue-50 peer-checked:shadow-sm text-gray-400 rounded-2xl flex flex-col items-center justify-center transition">
                                    <i class="fa-solid fa-credit-card text-2xl mb-1"></i>
                                    <span class="font-black text-[11px] uppercase tracking-wide">Tarjeta</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA (Calculadora: 5/12 del espacio) -->
                    <div class="lg:col-span-5 flex flex-col gap-3 h-full">
                        
                        <!-- CALCULADORA / TECLADO QUE SE ESTIRA -->
                        <div class="border border-gray-100 rounded-3xl p-4 shadow-sm bg-white flex-1 flex flex-col min-h-0">
                            <div class="flex justify-between items-center mb-3 shrink-0">
                                <span class="font-bold text-gray-500 text-xs">Recibido</span>
                                <span class="bg-green-100 text-green-700 font-black px-3 py-1 rounded-lg text-xl tracking-wider" id="lbl_recibido">$0</span>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2 flex-1">
                                <?php foreach([1,2,3,4,5,6,7,8,9,'00',0] as $num): ?>
                                    <button type="button" onclick="tecla('<?= $num ?>')" class="h-full bg-gray-50 hover:bg-gray-200 rounded-xl font-bold text-xl text-[#0A1F3D] transition shadow-sm"><?= $num ?></button>
                                <?php endforeach; ?>
                                <button type="button" onclick="tecla('back')" class="h-full bg-red-50 hover:bg-red-100 text-red-500 rounded-xl font-bold text-xl transition shadow-sm"><i class="fa-solid fa-delete-left"></i></button>
                            </div>
                            
                            <!-- DIV DINÁMICO DE CAMBIO / FALTANTE -->
                            <div id="div_calculo" class="mt-3 text-center text-[11px] shrink-0"></div>
                        </div>

                        <!-- BOTÓN DE LIQUIDAR -->
                        <form action="<?= base_url('caja/liquidar') ?>" method="POST" class="shrink-0">
                            <input type="hidden" name="id_mesa" value="<?= $mesa_activa['id_mesa'] ?>">
                            <input type="hidden" name="metodo_pago" id="input_metodo" value="efectivo">
                            <input type="hidden" name="monto_efectivo" id="input_efectivo" value="0">
                            <input type="hidden" name="monto_tarjeta" id="input_tarjeta" value="0">
                            
                            <button type="submit" id="btn_liquidar" class="w-full bg-[#EEF2F6] text-[#64748B] hover:bg-[#0A1F3D] hover:text-white font-black text-sm py-4 rounded-2xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-2">
                                Liquidar y Liberar Mesa <i class="fa-solid fa-check-double"></i>
                            </button>
                        </form>
                    </div>

                </div> <!-- Fin del Grid -->
                
            <?php else: ?>
                <!-- ESTADO VACÍO -->
                <div class="h-full flex flex-col items-center justify-center text-gray-300">
                    <i class="fa-solid fa-cash-register text-7xl mb-4 opacity-30"></i>
                    <h2 class="text-2xl font-bold text-gray-400 mb-1">Selecciona una cuenta</h2>
                    <p class="text-sm font-medium">Elige una mesa del panel izquierdo para cobrarla.</p>
                </div>
            <?php endif; ?>
            
        </section>
    </main>

    <!-- LOGICA DE CALCULADORA Y PROPINA -->
    <script>
        const consumoTotal = <?= $mesa_activa ? $mesa_activa['total'] : 0 ?>;
        let porcentajeActual = 0;
        let montoPropina = 0;
        let granTotal = consumoTotal;
        
        let metodoPago = 'efectivo';
        let stringRecibido = "";
        let totalRecibido = 0;

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
            montoPropina = consumoTotal * (porcentaje / 100);
            granTotal = consumoTotal + montoPropina;

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
                
                divCalculo.innerHTML = `<div class="bg-blue-50 text-blue-600 py-1 px-2 rounded font-bold border border-blue-100">Cobro total a Tarjeta (Terminal)</div>`;
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
        
        calcularCambio();
    </script>
</body>
</html>