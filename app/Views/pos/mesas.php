<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - Mangle Mesas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#EFF6FF] font-sans h-screen flex flex-col overflow-hidden">

    <header class="bg-[#0A1F3D] text-white p-5 flex justify-between items-center shadow-xl shrink-0 border-b border-blue-900">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-800 rounded-2xl flex items-center justify-center text-xl font-black shadow-inner border border-blue-700">
                <i class="fa-solid fa-user-tie text-[#00B4D8]"></i>
            </div>
            <div>
                <div class="font-black text-lg leading-tight tracking-tight uppercase"><?= session()->get('nombre_completo') ?? 'Mesero Activo' ?></div>
                <div class="text-xs text-gray-400">Username: <span class="text-[#00B4D8] font-bold">@<?= session()->get('username') ?? 'usuario' ?></span></div>
            </div>
        </div>

        <div class="flex gap-6 items-center">
            <div class="bg-[#0f2d56] px-5 py-2 rounded-2xl border border-blue-800/60 flex flex-col items-center">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mi Consumo Total</span>
                <span class="text-lg font-black text-green-400">$<?= number_format($consumo_total_mesero, 2) ?></span>
            </div>

            <div class="bg-[#0f2d56] px-5 py-2 rounded-2xl border border-blue-800/60 flex flex-col items-center">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mesas Totales</span>
                <span class="text-lg font-black text-[#00B4D8]"><?= $total_mesas_atendidas ?></span>
            </div>

            <div class="bg-black/20 px-4 py-2 rounded-2xl border border-gray-800 font-mono text-xl font-black text-[#00B4D8]" id="reloj-digital">
                00:00:00
            </div>

            <a href="<?= base_url('logout') ?>" class="bg-red-600 hover:bg-red-700 p-3 px-5 rounded-2xl font-black text-xs transition uppercase tracking-wider shadow-lg shadow-red-600/20 flex items-center gap-2">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </header>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="bg-green-500 text-white p-3 font-bold text-center text-sm shrink-0 uppercase tracking-widest flex items-center justify-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="bg-red-500 text-white p-3 font-bold text-center text-sm shrink-0 uppercase tracking-widest flex items-center justify-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <main class="p-8 flex-1 overflow-y-auto bg-[#F0F4F8]">
        <div class="max-w-6xl mx-auto">
            <h3 class="text-xs font-black text-[#1565C0] tracking-widest uppercase mb-6 border-b border-blue-200 pb-2">Estado General del Piso:</h3>
            
            <div class="grid grid-cols-4 gap-6">
                <?php foreach($mesas as $m): 
                    // CONTROL ESTRICTO DE ESTADOS Y COLORES
                    if ($m['estado_mesa'] == 'Libre' || empty($m['estado_mesa'])) {
                        $color = 'bg-gradient-to-br from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 shadow-green-500/20';
                        $url = base_url('pos/mesa/'.$m['id_mesa']); 
                        $icono = 'fa-circle-check';
                    } elseif ($m['estado_mesa'] == 'Ocupada') {
                        $color = 'bg-gradient-to-br from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 shadow-red-500/20';
                        $url = base_url('pos/ver_comanda/'.$m['id_mesa']); 
                        $icono = 'fa-receipt';
                    } elseif ($m['estado_mesa'] == 'Por Pagar') {
                        $color = 'bg-gradient-to-br from-yellow-400 to-amber-500 hover:from-yellow-500 hover:to-amber-600 shadow-yellow-400/20 text-gray-900';
                        $url = base_url('pos/ver_comanda/'.$m['id_mesa']); 
                        $icono = 'fa-print';
                    }
                ?>
                    <a href="<?= $url ?>" 
                       class="<?= $color ?> p-8 rounded-3xl shadow-xl flex flex-col items-center justify-center transition-all transform hover:-translate-y-1 relative group overflow-hidden">
                        
                        <div class="absolute inset-0 w-full h-full bg-white/10 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        
                        <i class="fa-solid <?= $icono ?> text-4xl mb-4 opacity-90"></i>
                        <span class="font-black text-2xl tracking-tighter uppercase">Mesa <?= $m['numero_mesa'] ?></span>
                        <span class="text-xs font-black mt-2 uppercase bg-black/10 px-3 py-1 rounded-full tracking-wide">
                            <?= $m['estado_mesa'] ?: 'Libre' ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        function iniciarReloj() {
            const elReloj = document.getElementById('reloj-digital');
            if(!elReloj) return;
            
            setInterval(() => {
                const ahora = new Date();
                elReloj.innerText = ahora.toLocaleTimeString('es-MX', { hour12: false });
            }, 1000);
        }
        iniciarReloj();
    </script>
</body>
</html>