<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - El Mangle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        
        /* barra de scroll personalizada para que se vea mas limpia */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-[#F1F5F9] flex flex-col h-screen font-sans overflow-hidden text-[#1E293B]">
    
    <header class="bg-[#0A1F3D] text-white flex justify-between items-center px-6 py-4 shrink-0 shadow-md z-20">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-[#00B4D8]/20 flex items-center justify-center text-[#00B4D8] text-lg">
                <i class="fa-solid fa-anchor"></i>
            </div>
            <div class="leading-tight">
                <div class="text-[#00B4D8] text-[10px] font-black tracking-widest uppercase">El Mangle</div>
                <div class="text-white text-base font-bold tracking-wide uppercase">Caja Registradora</div>
            </div>
        </div>
        
        <div class="flex gap-4">
            <button type="button" onclick="abrirModalCorte()" class="bg-[#00B4D8] hover:bg-[#0096B4] text-white px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-wallet"></i> Corte de Caja
            </button>
            <a href="<?= base_url('login/logout') ?>" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-5 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-sm border border-gray-700">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </header>

    <?php if(session()->getFlashdata('error')): ?>
        <div class="bg-red-600 text-white font-bold text-center py-2 text-sm shadow-md shrink-0 animate-pulse"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>
    <?php if(session()->getFlashdata('success')): ?>
        <div class="bg-emerald-500 text-white font-bold text-center py-2 text-sm shadow-md shrink-0"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <main class="flex-1 flex overflow-hidden">
        
        <aside class="w-[320px] bg-white border-r border-gray-200 flex flex-col h-full shrink-0 z-10 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0 bg-white">
                <h2 class="text-[#0A1F3D] font-black text-lg flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-gray-400"></i> Por Cobrar
                </h2>
                <span class="bg-gray-100 text-gray-600 text-xs font-black px-3 py-1 rounded-full"><?= count($mesas) ?></span>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#F8FAFC]">
                <?php if(empty($mesas)): ?>
                    <div class="text-center text-gray-400 mt-10 font-medium text-sm flex flex-col items-center">
                        <i class="fa-solid fa-check-circle text-4xl mb-3 opacity-20"></i>
                        No hay cuentas pendientes
                    </div>
                <?php endif; ?>
                
                <?php foreach($mesas as $m): 
                    $esActiva = ($mesa_activa && $mesa_activa['id_mesa'] == $m['id_mesa']);
                    $borderClase = $esActiva ? 'border-[#00B4D8] ring-4 ring-[#00B4D8]/10' : 'border-gray-200 hover:border-gray-300';
                ?>
                    <a href="<?= base_url('caja/index/'.$m['id_mesa']) ?>" class="block bg-white border-2 <?= $borderClase ?> rounded-xl p-4 transition-all shadow-sm">
                        <div class="flex justify-between items-center mb-1">
                            <span class="font-black text-[#0A1F3D] text-lg">Mesa <?= $m['numero_mesa'] ?></span>
                            <span class="font-black text-emerald-600 text-lg">$<?= number_format($m['total'], 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <div class="text-[11px] text-gray-500 font-bold uppercase tracking-wide bg-gray-100 px-2 py-1 rounded-md"><i class="fa-solid fa-user-tie mr-1"></i> <?= $m['mesero'] ?? 'MESA ABIERTA' ?></div>
                            <div class="text-[11px] text-gray-400 font-bold"><?= $m['items'] ?> arts</div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="flex-1 bg-[#F8FAFC] p-6 overflow-hidden flex flex-col">
            <?php if($mesa_activa): ?>
                
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex justify-between items-center mb-6 shrink-0">
                    <div>
                        <h2 class="text-3xl font-black text-[#0A1F3D] mb-1 tracking-tight">Cobro Mesa <?= $mesa_activa['numero_mesa'] ?></h2>
                        <p class="text-gray-500 font-medium text-sm">Atendido por <span class="uppercase font-bold text-gray-700"><?= $mesa_activa['mesero'] ?? 'MESA ABIERTA' ?></span></p>
                    </div>
                    <div class="text-right bg-gray-50 p-4 rounded-xl border border-gray-100 min-w-[200px]">
                        <p class="text-[11px] text-gray-500 font-black tracking-widest uppercase mb-1">Total de Consumo</p>
                        <p class="text-4xl font-black text-[#0A1F3D] tracking-tight" id="lbl_gran_total">$<?= number_format($mesa_activa['total'], 2) ?></p>
                    </div>
                </div>

                <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-0">
                    
                    <div class="lg:col-span-7 flex flex-col gap-6 overflow-y-auto pb-2 pr-2">
                        
                        <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-sm font-black text-gray-800 uppercase tracking-wide mb-4 flex items-center gap-2"><i class="fa-solid fa-receipt text-gray-400"></i> Desglose</h3>
                            <div class="flex justify-between mb-3 text-sm text-gray-600 font-medium pb-3 border-b border-gray-50"><span>Subtotal (<?= $mesa_activa['items'] ?> artículos)</span><span>$<?= number_format($mesa_activa['total'] * 0.84, 2) ?></span></div>
                            <div class="flex justify-between mb-4 text-sm text-gray-600 font-medium pb-4 border-b border-gray-50"><span>IVA (16%)</span><span>$<?= number_format($mesa_activa['total'] * 0.16, 2) ?></span></div>
                            <div class="flex justify-between font-black text-[#0A1F3D] text-xl"><span>Subtotal a Pagar</span><span id="lbl_consumo">$<?= number_format($mesa_activa['total'], 2) ?></span></div>
                        </div>

                        <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
                            <h3 class="text-sm font-black text-gray-800 uppercase tracking-wide mb-4 flex items-center gap-2"><i class="fa-solid fa-hand-holding-dollar text-gray-400"></i> Propina Sugerida</h3>
                            <div class="flex gap-2 mb-4">
                                <button type="button" onclick="setPropina(0)" id="btn_prop_0" class="btn-prop flex-1 py-3 bg-[#00B4D8] text-white font-bold rounded-xl transition shadow-sm text-sm border-2 border-[#00B4D8]">0%</button>
                                <button type="button" onclick="setPropina(10)" id="btn_prop_10" class="btn-prop flex-1 py-3 bg-white text-gray-600 font-bold rounded-xl transition text-sm border-2 border-gray-200 hover:border-gray-300">10%</button>
                                <button type="button" onclick="setPropina(15)" id="btn_prop_15" class="btn-prop flex-1 py-3 bg-white text-gray-600 font-bold rounded-xl transition text-sm border-2 border-gray-200 hover:border-gray-300">15%</button>
                                <button type="button" onclick="setPropina(20)" id="btn_prop_20" class="btn-prop flex-1 py-3 bg-white text-gray-600 font-bold rounded-xl transition text-sm border-2 border-gray-200 hover:border-gray-300">20%</button>
                                <input type="number" step="any" id="input_propina_custom" placeholder="Otro %" class="flex-[1.2] py-3 px-2 text-center bg-gray-50 border-2 border-gray-200 font-bold rounded-xl outline-none transition text-sm focus:border-[#00B4D8] focus:bg-white" oninput="setPropinaCustom(this.value)">
                            </div>
                            <div class="flex justify-between items-center bg-gray-50 text-gray-700 p-3 px-4 rounded-xl font-bold text-sm border border-gray-200"><span>Monto de Propina</span><span id="lbl_monto_propina" class="text-emerald-600 font-black">+ $0.00</span></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 h-28 shrink-0 mt-auto">
                            <label class="cursor-pointer h-full">
                                <input type="radio" name="metodo" value="efectivo" class="peer hidden" checked onchange="setMetodo('efectivo')">
                                <div class="h-full border-2 border-gray-200 bg-white peer-checked:border-emerald-500 peer-checked:text-emerald-600 peer-checked:bg-emerald-50 rounded-2xl flex flex-col items-center justify-center transition text-gray-400 shadow-sm">
                                    <i class="fa-solid fa-money-bill-wave text-3xl mb-2"></i><span class="font-black text-xs uppercase tracking-widest">Efectivo</span>
                                </div>
                            </label>
                            <label class="cursor-pointer h-full">
                                <input type="radio" name="metodo" value="tarjeta" class="peer hidden" onchange="setMetodo('tarjeta')">
                                <div class="h-full border-2 border-gray-200 bg-white peer-checked:border-blue-500 peer-checked:text-blue-600 peer-checked:bg-blue-50 rounded-2xl flex flex-col items-center justify-center transition text-gray-400 shadow-sm">
                                    <i class="fa-solid fa-credit-card text-3xl mb-2"></i><span class="font-black text-xs uppercase tracking-widest">Tarjeta</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="lg:col-span-5 flex flex-col gap-4 h-full">
                        <div class="border border-gray-200 rounded-2xl p-5 shadow-sm bg-white flex-1 flex flex-col min-h-0">
                            
                            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100 shrink-0">
                                <span class="font-bold text-gray-500 text-sm uppercase tracking-wide">Recibido</span>
                                <span class="bg-gray-100 text-[#0A1F3D] font-black px-4 py-2 rounded-xl text-3xl" id="lbl_recibido">$0</span>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-3 flex-1">
                                <?php foreach([1,2,3,4,5,6,7,8,9,'.',0] as $num): ?>
                                    <button type="button" onclick="tecla('<?= $num ?>')" class="h-full bg-gray-50 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 rounded-xl font-black text-2xl text-gray-700 transition active:scale-95"><?= $num ?></button>
                                <?php endforeach; ?>
                                <button type="button" onclick="tecla('back')" class="h-full bg-red-50 border border-red-100 hover:bg-red-100 text-red-500 rounded-xl font-bold text-xl transition active:scale-95"><i class="fa-solid fa-delete-left"></i></button>
                            </div>
                            
                            <div id="div_calculo" class="mt-4 pt-4 border-t border-gray-100 text-center text-sm font-medium shrink-0 h-12 flex items-center justify-center text-gray-500">
                                </div>
                        </div>

                        <form action="<?= base_url('caja/liquidar') ?>" method="POST" class="shrink-0">
                            <input type="hidden" name="id_mesa" value="<?= $mesa_activa['id_mesa'] ?>">
                            <input type="hidden" name="metodo_pago" id="input_metodo" value="efectivo">
                            <input type="hidden" name="monto_efectivo" id="input_efectivo" value="0">
                            <input type="hidden" name="monto_tarjeta" id="input_tarjeta" value="0">
                            
                            <button type="submit" id="btn_liquidar" class="w-full bg-gray-200 text-gray-400 font-black text-lg py-5 rounded-2xl transition shadow-sm uppercase tracking-widest flex justify-center items-center gap-3 cursor-not-allowed" disabled>
                                Cobrar Cuenta <i class="fa-solid fa-lock"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="h-full flex flex-col items-center justify-center bg-white rounded-3xl border border-dashed border-gray-300 m-4">
                    <div class="bg-gray-50 w-32 h-32 rounded-full flex items-center justify-center mb-6">
                        <i class="fa-solid fa-cash-register text-5xl text-gray-300"></i>
                    </div>
                    <h2 class="text-2xl font-black text-[#0A1F3D] mb-2">Caja Libre</h2>
                    <p class="text-gray-500 font-medium">Selecciona una mesa del panel izquierdo para procesar el cobro.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?= $this->include('caja/modal_corte') ?>

    <script>
        // configuracion inicial inyectada desde PHP para el JS
        const CONFIG_CAJA = {
            consumoTotal: <?= $mesa_activa ? $mesa_activa['total'] : 0 ?>,
            ventasEfectivoDia: <?= isset($corte['efectivo']) ? $corte['efectivo'] : 0 ?>
        };
    </script>
    
    <script src="<?= base_url('js/caja.js?v=3') ?>"></script>
</body>
</html>