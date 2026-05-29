<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Capitán - El Mangle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-[#0A1F3D] flex flex-col h-screen font-sans overflow-hidden">

    <header class="text-white flex justify-between items-center px-6 py-4 shrink-0">
        <div>
            <h1 class="text-xl font-bold">Mapa General de Mesas <span class="font-normal text-gray-300">(Zona Completa)</span></h1>
            <p class="text-sm text-gray-400">Selecciona una mesa para acciones administrativas</p>
        </div>
        <div class="bg-[#15325A] px-4 py-2 rounded-full text-sm font-medium flex items-center gap-2 border border-[#1E4275]">
            <i class="fa-regular fa-clock text-[#00B4D8]"></i> <?= date('h:i a') ?>
        </div>
    </header>

    <?php if(session()->getFlashdata('success')): ?>
        <div class="bg-green-500 text-white font-bold text-center py-2 shrink-0">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>

    <main class="flex-1 flex overflow-hidden bg-[#F8FAFC] rounded-tl-3xl">
        <section class="flex-1 p-6 overflow-y-auto">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach($mesas as $m): 
                    $bgColor = 'bg-white'; $borderColor = 'border-green-200'; $statusColor = 'text-green-500 bg-green-50';
                    $iconColor = 'bg-green-100 text-green-600'; $lblEstado = 'Disponible';
                    
                    if ($m['estado_mesa'] == 'Ocupada') {
                        $bgColor = 'bg-[#FFF5F5]'; $borderColor = 'border-red-300'; $statusColor = 'text-red-500 bg-red-50';
                        $iconColor = 'bg-red-100 text-red-500'; $lblEstado = 'Ocupada';
                    } elseif ($m['estado_mesa'] == 'Por Pagar') {
                        $bgColor = 'bg-[#FFFAF0]'; $borderColor = 'border-yellow-300'; $statusColor = 'text-yellow-600 bg-yellow-50';
                        $iconColor = 'bg-yellow-100 text-yellow-600'; $lblEstado = 'Cuenta Impresa';
                    }
                ?>
                <div onclick="seleccionarMesa(<?= $m['id_mesa'] ?>, '<?= $m['numero_mesa'] ?>', '<?= $lblEstado ?>', <?= $m['items'] ?>, <?= $m['total'] ?>, '<?= $m['mesero'] ?? 'No asignado' ?>')" 
                     class="<?= $bgColor ?> border-2 <?= $borderColor ?> rounded-3xl p-5 cursor-pointer hover:shadow-lg transition flex flex-col justify-between h-[220px] relative">
                    
                    <?php if($m['items'] > 0): ?>
                        <div class="absolute top-4 right-4 text-xs font-bold <?= $statusColor ?> px-2 py-1 rounded-lg">
                            <?= $m['items'] ?> items
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col items-start gap-3 mt-2">
                        <div class="w-12 h-12 <?= $iconColor ?> rounded-2xl flex items-center justify-center text-xl">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <h3 class="text-3xl font-black text-[#0A1F3D]">Mesa <?= $m['numero_mesa'] ?></h3>
                    </div>

                    <div class="mt-auto flex flex-col items-center">
                        <div class="flex items-center gap-2 <?= $statusColor ?> text-xs font-bold px-3 py-1 rounded-full mb-3">
                            <div class="w-2 h-2 rounded-full bg-current"></div> <?= $lblEstado ?>
                        </div>
                        <?php if($m['total'] > 0): ?>
                            <div class="text-lg font-black text-[#0A1F3D]">$<?= number_format($m['total'], 2) ?> MXN</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="w-[380px] bg-white border-l border-gray-200 flex flex-col h-full shrink-0 shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.05)]">
            <div class="bg-[#15325A] p-6 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-2xl shadow-lg">
                        <i class="fa-solid fa-shrimp"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-lg leading-tight">El Mangle</h2>
                        <p class="text-xs text-gray-300"><?= mb_strtoupper(session()->get('nombre') ?? 'Capitán') ?></p>
                    </div>
                </div>
                <a href="<?= base_url('login/logout') ?>" class="w-10 h-10 rounded-full bg-[#1E4275] hover:bg-red-500 flex items-center justify-center transition">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>

            <div id="panel_acciones" class="flex-1 p-6 flex flex-col gap-3 overflow-y-auto opacity-50 pointer-events-none transition-all">
                <div class="flex justify-between items-center mb-2">
                    <span id="lbl_status_panel" class="bg-gray-100 text-gray-500 px-3 py-1 rounded-md text-xs font-bold">Selecciona Mesa</span>
                    <span id="lbl_items_panel" class="text-gray-400 text-sm font-medium">0 artículos</span>
                </div>

                <div class="text-center mb-4">
                    <h2 id="lbl_titulo_mesa" class="text-2xl font-black text-[#0A1F3D]">Mesa --</h2>
                    <p id="lbl_mesero_panel" class="text-xs text-gray-400 font-bold uppercase tracking-wide">Esperando selección...</p>
                </div>

                <button id="btn_dividir" onclick="abrirModalDividir()" class="w-full bg-white border border-gray-200 hover:border-blue-400 p-4 rounded-2xl flex flex-col items-start gap-1 transition shadow-sm group">
                    <div class="flex items-center gap-3 text-[#0A1F3D] font-bold"><div class="w-8 h-8 bg-blue-50 text-blue-500 rounded-lg flex items-center justify-center group-hover:bg-blue-500 group-hover:text-white transition"><i class="fa-solid fa-split-envelope"></i></div>Dividir Cuenta</div>
                    <span class="text-xs text-gray-400 ml-11">Separar artículos en cuentas nuevas</span>
                </button>

                <button id="btn_transferir" onclick="abrirModalTransferir()" class="w-full bg-white border border-gray-200 hover:border-purple-400 p-4 rounded-2xl flex flex-col items-start gap-1 transition shadow-sm group">
                    <div class="flex items-center gap-3 text-[#0A1F3D] font-bold"><div class="w-8 h-8 bg-purple-50 text-purple-500 rounded-lg flex items-center justify-center group-hover:bg-purple-500 group-hover:text-white transition"><i class="fa-solid fa-arrow-right-arrow-left"></i></div>Transferir Mesa</div>
                    <span class="text-xs text-gray-400 ml-11">Mover orden a mesa disponible</span>
                </button>

                <button id="btn_cancelar" onclick="abrirModalCancelar()" class="w-full bg-white border border-gray-200 hover:border-red-400 p-4 rounded-2xl flex flex-col items-start gap-1 transition shadow-sm group">
                    <div class="flex items-center gap-3 text-red-500 font-bold"><div class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition"><i class="fa-solid fa-ban"></i></div>Cancelar Platillo</div>
                    <span class="text-xs text-gray-400 ml-11">Remover con motivo obligatorio</span>
                </button>

                <button id="btn_reabrir" onclick="reabrirCuenta()" class="w-full bg-white border border-gray-200 hover:border-orange-400 p-4 rounded-2xl flex flex-col items-start gap-1 transition shadow-sm group">
                    <div class="flex items-center gap-3 text-orange-600 font-bold"><div class="w-8 h-8 bg-orange-50 text-orange-500 rounded-lg flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition"><i class="fa-solid fa-lock-open"></i></div>Reabrir Cuenta</div>
                    <span class="text-xs text-gray-400 ml-11">Permitir al mesero agregar más platillos</span>
                </button>

                <div class="mt-auto shrink-0 pt-4 border-t border-gray-100">
                    <button type="button" id="btn_ver_orden" onclick="ejecutarAccionPrincipal()" class="w-full bg-[#E2E8F0] text-[#475569] font-black py-4 rounded-2xl transition hover:bg-[#0A1F3D] hover:text-white shadow-sm uppercase tracking-widest text-sm flex justify-center items-center gap-2">
                        Ver Detalle de Orden
                    </button>
                </div>
            </div>
            <div class="p-3 text-center text-xs text-gray-400 border-t border-gray-100 bg-white shrink-0">
                <i class="fa-solid fa-water"></i> El Mangle POS v2.1 (Capitán)
            </div>
        </aside>
    </main>

    <!-- INCLUIR EL MODAL EXTRACÍDO -->
    <?= $this->include('capitan/modal_transferir') ?>

    <!-- COMUNICACIÓN PHP -> JS -->
    <script>
        const CONFIG_CAPITAN = {
            baseUrl: '<?= base_url() ?>'
        };
    </script>

    <!-- INCLUIR EL JAVASCRIPT EXTERNO -->
    <script src="<?= base_url('js/capitan.js') ?>"></script>
</body>
</html>