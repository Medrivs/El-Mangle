<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Caja - El Mangle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#EFF6FF] min-h-screen font-sans flex flex-col">

    <!-- HEADER DE CAJA -->
    <header class="bg-[#0A1F3D] text-white p-4 flex justify-between items-center shadow-md shrink-0">
        <div class="flex items-center gap-4">
            <div class="bg-green-500 text-white w-10 h-10 rounded-full flex items-center justify-center text-xl shadow-lg">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide uppercase">Módulo de Caja</h1>
                <p class="text-xs text-blue-300 font-medium">Gestión de Cobros y Tickets</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="text-right hidden md:block">
                <p class="font-bold text-sm"><?= session()->get('nombre_usuario') ?: 'Cajero' ?></p>
                <p class="text-xs text-green-400">Turno Activo</p>
            </div>
            <a href="<?= base_url('login/logout') ?>" class="bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white w-10 h-10 rounded-xl flex items-center justify-center transition">
                <i class="fa-solid fa-power-off"></i>
            </a>
        </div>
    </header>

    <!-- PANEL DE MESAS -->
    <main class="flex-1 p-6 overflow-y-auto">
        <div class="max-w-7xl mx-auto">
            
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-[#1565C0] font-black text-lg uppercase tracking-widest">Estado de Mesas</h2>
                    <p class="text-gray-500 text-sm">Selecciona una mesa ocupada o por pagar para procesar el cobro.</p>
                </div>
                
                <!-- Indicadores Visuales -->
                <div class="flex gap-4 text-xs font-bold uppercase tracking-wider">
                    <div class="flex items-center gap-2 text-amber-600"><div class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></div> Por Pagar</div>
                    <div class="flex items-center gap-2 text-blue-600"><div class="w-3 h-3 rounded-full bg-blue-500"></div> Ocupadas</div>
                    <div class="flex items-center gap-2 text-gray-400"><div class="w-3 h-3 rounded-full bg-gray-300"></div> Libres</div>
                </div>
            </div>

            <!-- GRID DE MESAS -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <?php foreach($mesas as $mesa): 
                    // Configuración visual por defecto (Libre)
                    $bgColor = 'bg-white';
                    $borderColor = 'border-gray-200';
                    $textColor = 'text-gray-400';
                    $iconColor = 'text-gray-300';
                    $estadoTexto = 'Libre';
                    $enlace = '#'; // Si está libre, no hace nada
                    $animacion = '';
                    $icono = 'fa-chair';

                    if ($mesa['estado_mesa'] == 'Ocupada') {
                        $bgColor = 'bg-blue-50';
                        $borderColor = 'border-blue-300 hover:border-blue-500 shadow-md';
                        $textColor = 'text-[#1565C0]';
                        $iconColor = 'text-blue-500';
                        $estadoTexto = 'Ocupada (Consumiendo)';
                        $enlace = base_url('caja/cobrar/'.$mesa['id_mesa']);
                        $icono = 'fa-utensils';
                    } elseif ($mesa['estado_mesa'] == 'Por Pagar') {
                        $bgColor = 'bg-amber-50';
                        $borderColor = 'border-amber-400 hover:border-amber-600 shadow-lg';
                        $textColor = 'text-amber-700';
                        $iconColor = 'text-amber-500';
                        $estadoTexto = 'Pidió la Cuenta';
                        $enlace = base_url('caja/cobrar/'.$mesa['id_mesa']);
                        $animacion = 'animate-pulse';
                        $icono = 'fa-receipt';
                    }
                ?>
                    
                    <a href="<?= $enlace ?>" class="<?= $bgColor ?> border-2 <?= $borderColor ?> rounded-3xl p-6 flex flex-col items-center justify-center text-center transition-all relative overflow-hidden group <?= $mesa['estado_mesa'] == 'Libre' ? 'cursor-not-allowed opacity-70' : 'hover:-translate-y-1' ?>">
                        
                        <!-- Etiqueta superior si está por pagar -->
                        <?php if($mesa['estado_mesa'] == 'Por Pagar'): ?>
                            <div class="absolute top-0 left-0 w-full bg-amber-500 text-white text-[10px] font-black uppercase py-1 tracking-widest <?= $animacion ?>">
                                Atender en Caja
                            </div>
                        <?php endif; ?>

                        <div class="text-4xl <?= $iconColor ?> mb-3 transition-transform group-hover:scale-110 <?= $mesa['estado_mesa'] == 'Por Pagar' ? 'mt-4' : '' ?>">
                            <i class="fa-solid <?= $icono ?>"></i>
                        </div>
                        
                        <div class="font-black text-2xl <?= $textColor ?> mb-1">MESA <?= $mesa['numero_mesa'] ?></div>
                        
                        <div class="text-xs font-bold px-3 py-1 rounded-full <?= $mesa['estado_mesa'] == 'Por Pagar' ? 'bg-amber-200 text-amber-800' : ($mesa['estado_mesa'] == 'Ocupada' ? 'bg-blue-200 text-blue-800' : 'bg-gray-100 text-gray-500') ?>">
                            <?= $estadoTexto ?>
                        </div>
                    </a>

                <?php endforeach; ?>
            </div>

        </div>
    </main>

</body>
</html> 