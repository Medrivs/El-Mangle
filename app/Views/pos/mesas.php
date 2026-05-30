<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - Mangle Mesas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#F8FAFC] font-sans h-screen flex flex-col overflow-hidden">

    <header class="bg-[#0A1F3D] text-white p-5 flex justify-between items-center shadow-lg shrink-0 border-b border-blue-900">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-800/50 rounded-2xl flex items-center justify-center text-xl font-black shadow-inner border border-blue-700">
                <i class="fa-solid fa-user-tie text-[#00B4D8]"></i>
            </div>
            <div>
                <div class="font-black text-lg leading-tight tracking-tight uppercase"><?= session()->get('nombre_completo') ?? 'Mesero Activo' ?></div>
                <div class="text-xs text-gray-400">Username: <span class="text-[#00B4D8] font-bold">@<?= session()->get('username') ?? 'usuario' ?></span></div>
            </div>
        </div>

        <div class="flex gap-6 items-center">
            <div class="bg-[#0f2d56] px-5 py-2 rounded-2xl border border-blue-800/60 flex flex-col items-center">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mi Consumo</span>
                <span class="text-lg font-black text-green-400">$<?= number_format($consumo_total_mesero, 2) ?></span>
            </div>

            <div class="bg-[#0f2d56] px-5 py-2 rounded-2xl border border-blue-800/60 flex flex-col items-center">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Mesas Totales</span>
                <span class="text-lg font-black text-[#00B4D8]"><?= $total_mesas_atendidas ?></span>
            </div>

            <div class="bg-black/30 px-4 py-2 rounded-2xl border border-gray-800 font-mono text-xl font-black text-[#00B4D8]" id="reloj-digital">
                00:00:00
            </div>

            <a href="<?= base_url('logout') ?>" class="bg-red-500/10 hover:bg-red-600 text-red-500 hover:text-white border border-red-500/30 p-3 px-5 rounded-2xl font-black text-xs transition-colors uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-power-off"></i> Salir
            </a>
        </div>
    </header>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="bg-green-100 text-green-800 border-b border-green-200 p-3 font-bold text-center text-sm shrink-0 uppercase tracking-widest flex items-center justify-center gap-2">
            <i class="fa-solid fa-circle-check"></i> <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="bg-red-100 text-red-800 border-b border-red-200 p-3 font-bold text-center text-sm shrink-0 uppercase tracking-widest flex items-center justify-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <main class="p-8 flex-1 overflow-y-auto bg-[#F8FAFC]">
        <div class="max-w-7xl mx-auto">
            <h3 class="text-xs font-black text-gray-400 tracking-widest uppercase mb-6 pb-2">Estado General del Piso:</h3>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                <?php foreach($mesas as $m): 
                    // LÓGICA DE COLORES MINIMALISTAS
                    if ($m['estado_mesa'] == 'Libre' || empty($m['estado_mesa'])) {
                        $bordeTarjeta  = 'border-green-200 hover:border-green-400';
                        $fondoIcono    = 'bg-green-100 text-green-600';
                        $fondoPildora  = 'bg-green-50 text-green-600';
                        $puntoColor    = 'bg-green-500';
                        $icono         = 'fa-utensils'; // Tenedor y cuchillo
                        $textoEstado   = 'Disponible';
                        $url           = base_url('pos/mesa/'.$m['id_mesa']); 
                    } elseif ($m['estado_mesa'] == 'Ocupada') {
                        $bordeTarjeta  = 'border-red-200 hover:border-red-400';
                        $fondoIcono    = 'bg-red-100 text-red-600';
                        $fondoPildora  = 'bg-red-50 text-red-600';
                        $puntoColor    = 'bg-red-500';
                        $icono         = 'fa-receipt';
                        $textoEstado   = 'Ocupada';
                        $url           = base_url('pos/ver_comanda/'.$m['id_mesa']); 
                    } elseif ($m['estado_mesa'] == 'Por Pagar') {
                        $bordeTarjeta  = 'border-yellow-200 hover:border-yellow-400';
                        $fondoIcono    = 'bg-yellow-100 text-yellow-600';
                        $fondoPildora  = 'bg-yellow-50 text-yellow-700';
                        $puntoColor    = 'bg-yellow-500';
                        $icono         = 'fa-print';
                        $textoEstado   = 'Por Pagar';
                        $url           = base_url('pos/ver_comanda/'.$m['id_mesa']); 
                    }
                ?>
                    <a href="<?= $url ?>" 
                       class="bg-white border-2 <?= $bordeTarjeta ?> p-6 rounded-3xl shadow-sm hover:shadow-lg transition-all flex flex-col justify-between h-48 group">
                        
                        <div class="<?= $fondoIcono ?> w-12 h-12 rounded-2xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                            <i class="fa-solid <?= $icono ?>"></i>
                        </div>
                        
                        <div class="mt-4">
                            <h4 class="text-3xl font-black text-[#0A1F3D] tracking-tight mb-3">Mesa <?= $m['numero_mesa'] ?></h4>
                            
                            <div class="flex items-center">
                                <span class="<?= $fondoPildora ?> px-3 py-1.5 rounded-full text-xs font-bold flex items-center gap-2 uppercase tracking-wide">
                                    <span class="w-2 h-2 rounded-full <?= $puntoColor ?>"></span>
                                    <?= $textoEstado ?>
                                </span>
                            </div>
                        </div>
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