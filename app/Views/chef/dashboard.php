<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>KDS - Cocina El Mangle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <meta http-equiv="refresh" content="12">
</head>
<body class="bg-[#121212] font-sans h-screen flex flex-col overflow-hidden text-gray-100">

    <header class="bg-[#1c1c1c] p-4 flex justify-between items-center border-b border-gray-800 shrink-0 shadow-2xl">
        <div class="flex items-center gap-6">
            <div class="flex bg-black/40 p-1.5 rounded-2xl border border-gray-800 gap-1">
                <a href="<?= base_url('chef/dashboard?estacion=caliente') ?>" class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition <?= $estacion_activa === 'caliente' ? 'bg-orange-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">Cocina Caliente + Postres</a>
                <a href="<?= base_url('chef/dashboard?estacion=fria') ?>" class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition <?= $estacion_activa === 'fria' ? 'bg-cyan-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">Barra Fria</a>
                <a href="<?= base_url('chef/dashboard?estacion=bebidas') ?>" class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition <?= $estacion_activa === 'bebidas' ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:text-white' ?>">Bebidas</a>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="font-mono text-2xl font-black text-orange-500" id="reloj-kds">00:00:00</div>
            <a href="<?= base_url('logout') ?>" class="bg-gray-800 hover:bg-red-600 text-gray-300 p-3 rounded-xl transition border border-gray-700"><i class="fa-solid fa-power-off"></i></a>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        
        <div class="flex-1 flex p-4 gap-4 overflow-x-auto items-start">
            
            <div class="w-1/3 h-full flex flex-col bg-[#1e1e1e] rounded-3xl border border-gray-800 overflow-hidden shadow-inner">
                <div class="bg-blue-950/30 border-b border-blue-900/50 p-4 text-center shrink-0">
                    <h2 class="font-black text-blue-400 tracking-widest uppercase text-xs">Nuevas Ordenes</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-4">
                    <?php foreach($nuevas as $n): ?>
                        <div class="bg-[#262626] rounded-2xl border border-gray-800 overflow-hidden shadow-xl">
                            <div class="bg-[#2d2d2d] p-3 px-4 border-b border-gray-800 flex justify-between items-center text-xs">
                                <span class="font-black text-sm text-blue-400">MESA <?= $n['mesa'] ?></span>
                                <span class="text-gray-500 font-bold font-mono"><i class="fa-regular fa-clock mr-1"></i><?= date('H:i', strtotime($n['fecha'])) ?></span>
                            </div>
                            <div class="p-3 space-y-3">
                                <?php foreach($n['items'] as $item): ?>
                                    <div class="bg-black/20 p-3 rounded-xl border border-white/5 flex justify-between items-center">
                                        <div class="flex-1 pr-2">
                                            <div class="font-black text-sm text-white"><span class="text-orange-500 font-mono mr-1"><?= $item['cantidad'] ?>x</span><?= $item['nombre_platillo'] ?></div>
                                            <?php if($item['comentarios']): ?><div class="text-xs text-yellow-500 font-bold bg-yellow-950/20 border border-yellow-900/40 p-1.5 rounded-lg mt-2">⚠️ <?= $item['comentarios'] ?></div><?php endif; ?>
                                        </div>
                                        <a href="<?= base_url('chef/cambiar_estado/'.$item['id_detalle_comanda'].'/Preparando?estacion='.$estacion_activa) ?>" class="bg-blue-600 hover:bg-blue-500 px-3 py-2 rounded-xl text-white font-black text-xs transition uppercase tracking-wider shrink-0">Cocinar</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="w-1/3 h-full flex flex-col bg-[#1e1e1e] rounded-3xl border border-gray-800 overflow-hidden shadow-inner">
                <div class="bg-orange-950/30 border-b border-orange-900/50 p-4 text-center shrink-0">
                    <h2 class="font-black text-orange-400 tracking-widest uppercase text-xs">En Preparacion</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-4">
                    <?php foreach($preparando as $p): ?>
                        <div class="bg-[#262626] rounded-2xl border border-gray-800 overflow-hidden shadow-xl border-l-4 border-l-orange-500">
                            <div class="bg-[#2d2d2d] p-3 px-4 border-b border-gray-800 text-xs font-black text-orange-400">MESA <?= $p['mesa'] ?></div>
                            <div class="p-3 space-y-3">
                                <?php foreach($p['items'] as $item): ?>
                                    <div class="bg-orange-950/5 p-3 rounded-xl border border-orange-500/10 flex justify-between items-center">
                                        <div class="flex-1 pr-2">
                                            <div class="font-black text-sm text-white"><span class="text-orange-500 font-mono mr-1"><?= $item['cantidad'] ?>x</span><?= $item['nombre_platillo'] ?></div>
                                            <?php if($item['comentarios']): ?><div class="text-xs text-yellow-500 font-bold bg-yellow-950/20 border border-yellow-900/40 p-1.5 rounded-lg mt-2">⚠️ <?= $item['comentarios'] ?></div><?php endif; ?>
                                        </div>
                                        <a href="<?= base_url('chef/cambiar_estado/'.$item['id_detalle_comanda'].'/Listo?estacion='.$estacion_activa) ?>" class="bg-green-600 hover:bg-green-500 px-3 py-2 rounded-xl text-white font-black text-xs transition uppercase tracking-wider shrink-0">Terminar</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="w-1/3 h-full flex flex-col bg-[#1e1e1e] rounded-3xl border border-gray-800 overflow-hidden shadow-inner">
                <div class="bg-green-950/30 border-b border-green-900/50 p-4 text-center shrink-0">
                    <h2 class="font-black text-green-400 tracking-widest uppercase text-xs">Despachados (Pila)</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <?php foreach($listas as $item): ?>
                        <div class="bg-[#262626] p-4 rounded-xl border border-green-950 flex justify-between items-center shadow-lg border-l-4 border-l-green-500 opacity-60">
                            <div>
                                <div class="font-black text-[10px] text-gray-500 mb-1">MESA <?= $item['numero_mesa'] ?></div>
                                <div class="font-bold text-sm line-through text-gray-400 font-sans"><?= $item['cantidad'] ?>x <?= $item['nombre_platillo'] ?></div>
                            </div>
                            <div class="w-7 h-7 rounded-full bg-green-950 flex items-center justify-center text-green-400 text-xs"><i class="fa-solid fa-check"></i></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <aside class="w-80 bg-[#161616] flex flex-col shrink-0 border-l border-gray-800">
            <div class="p-4 bg-[#1c1c1c] border-b border-gray-800">
                <h3 class="font-black tracking-widest text-xs text-gray-400 uppercase"><i class="fa-solid fa-warehouse mr-2"></i> Control de Insumos</h3>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-3 bg-black/10">
                <?php foreach($inventario as $inv): 
                    $stock = $inv['stock_actual'];
                    $min = $inv['stock_minimo'];
                    
                    // Semáforo inteligente combinando cálculo automático y override manual del Chef
                    if ($inv['bloqueado_manual'] == 1 || $stock <= 0) {
                        $estadoColor = 'bg-red-500 shadow-red-500/50'; $textStyle = 'text-red-400 line-through';
                    } elseif ($inv['alerta_manual'] == 1 || $stock <= $min) {
                        $estadoColor = 'bg-amber-500 shadow-amber-500/50'; $textStyle = 'text-amber-400';
                    } else {
                        $estadoColor = 'bg-green-500 shadow-green-500/50'; $textStyle = 'text-gray-300';
                    }
                ?>
                    <div class="bg-[#222] p-3 rounded-2xl border border-gray-800 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-2.5 h-2.5 rounded-full shrink-0 <?= $estadoColor ?> shadow-lg"></div>
                                <span class="font-black text-xs uppercase tracking-tight truncate <?= $textStyle ?>"><?= $inv['nombre_producto'] ?></span>
                            </div>
                            <span class="font-mono text-xs font-black text-gray-500 shrink-0"><?= number_format($stock, 1) ?> <span class="text-[9px]"><?= $inv['unidad_medida'] ?></span></span>
                        </div>
                        
                        <div class="flex gap-2 text-[10px] font-black uppercase tracking-wider pt-1 border-t border-gray-800/40">
                            <a href="<?= base_url('chef/toggle_advertencia/'.$inv['id_materia_prima'].'?estacion='.$estacion_activa) ?>" class="flex-1 py-1.5 rounded-lg text-center transition border <?= $inv['alerta_manual'] ? 'bg-amber-600 border-amber-600 text-white' : 'border-gray-800 bg-black/20 text-gray-400 hover:text-amber-400' ?>">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i> Advertir
                            </a>
                            <a href="<?= base_url('chef/toggle_bloqueo/'.$inv['id_materia_prima'].'?estacion='.$estacion_activa) ?>" class="flex-1 py-1.5 rounded-lg text-center transition border <?= $inv['bloqueado_manual'] ? 'bg-red-600 border-red-600 text-white' : 'border-gray-800 bg-black/20 text-gray-400 hover:text-red-500' ?>">
                                <i class="fa-solid fa-ban mr-1"></i> Bloquear
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>

    </main>

    <script>
        setInterval(() => {
            document.getElementById('reloj-kds').innerText = new Date().toLocaleTimeString('es-MX', { hour12: false });
        }, 1000);
    </script>
</body>
</html>