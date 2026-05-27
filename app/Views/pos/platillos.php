<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - Platillos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#F0F4F8] font-sans h-screen flex flex-col overflow-hidden">

    <header class="bg-[#0A192F] text-white p-4 flex justify-between items-center shadow-md shrink-0">
        <div class="flex items-center gap-2 text-sm font-bold tracking-wide">
            <a href="<?= base_url('pos/mesa/'.$mesa['id_mesa']) ?>" class="bg-[#112240] hover:bg-[#1A365D] w-8 h-8 rounded-full flex items-center justify-center transition mr-2">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </a>
            <a href="<?= base_url('pos/mesa/'.$mesa['id_mesa']) ?>" class="hover:text-[#00B4D8] transition">Mesa <?= $mesa['numero_mesa'] ?></a>
            <span class="text-gray-500">›</span>
            <span class="text-gray-300"><?= $categoria['nombre_categoria'] ?></span>
            <span class="text-gray-500">›</span>
            <span class="text-white"><?= $pestaña_activa ?></span>
        </div>
        <div class="flex items-center gap-4 text-right">
            <div class="bg-[#112240] p-2 rounded-full">
                <i class="fa-solid fa-utensils text-[#00B4D8]"></i>
            </div>
            <div>
                <div class="font-bold text-lg leading-none">Mesa <?= $mesa['numero_mesa'] ?></div>
                <div class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Orden en preparación</div>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        
        <div class="flex-1 flex flex-col overflow-hidden bg-[#F4F7FA]">
            
            <div class="p-6 pb-2">
                <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-hide">
                    <?php foreach($pestañas as $pest): ?>
                        <?php 
                            // Si esta es la pestaña que estamos viendo, la pintamos de azul sólido
                            $esActiva = ($pest['subcategoria'] == $pestaña_activa);
                            $estiloBoton = $esActiva 
                                ? 'bg-[#185392] text-white font-bold shadow-md' 
                                : 'bg-white text-gray-600 font-medium hover:bg-gray-50 border border-gray-200';
                        ?>
                        <a href="<?= base_url('pos/filtrar/'.$mesa['id_mesa'].'/'.$categoria['id_categoria'].'/'.urlencode($pest['subcategoria'])) ?>" 
                           class="px-6 py-2.5 rounded-full whitespace-nowrap transition-all <?= $estiloBoton ?>">
                            <?= $pest['subcategoria'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <h3 class="text-xs font-bold text-[#185392] tracking-widest uppercase mt-6 mb-2">Selecciona el Platillo</h3>
            </div>
            
            <div class="flex-1 overflow-y-auto px-6 pb-6">
                <div class="grid grid-cols-2 gap-6">
                    <?php foreach($platillos as $p): ?>
                        <button class="bg-white border border-gray-200 p-6 rounded-2xl shadow-sm hover:border-[#185392] hover:shadow-md transition-all flex flex-col items-center text-center h-48 relative">
                            
                            <div class="w-16 h-16 mb-4 flex items-center justify-center">
                                <i class="fa-solid fa-martini-glass-citrus text-4xl text-[#185392]"></i>
                            </div>
                            
                            <div class="font-black text-[#0A192F] text-lg leading-tight w-full truncate">
                                <?= $p['nombre_platillo'] ?>
                            </div>
                            
                            <div class="text-sm text-gray-500 mt-1 font-medium">
                                $<?= number_format($p['precio_venta'], 2) ?> MXN
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <aside class="w-96 bg-white border-l border-gray-200 flex flex-col shadow-2xl shrink-0 z-10">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center gap-3 text-[#185392] mb-1">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <h2 class="text-sm font-black uppercase tracking-widest">Lista Temporal de Orden</h2>
                </div>
                <p class="text-xs text-gray-400">Agrega platillos, luego envía a cocina</p>
            </div>
            
            <div class="flex-1 flex flex-col items-center justify-center p-10 text-center bg-gray-50/50">
                <div class="bg-blue-50 w-20 h-20 flex items-center justify-center rounded-2xl mb-4">
                    <i class="fa-solid fa-cart-arrow-down text-3xl text-blue-200"></i>
                </div>
                <h3 class="text-[#0A192F] font-black text-lg mb-2">Carrito vacío</h3>
                <p class="text-sm text-gray-400 leading-relaxed">
                    Selecciona platillos en el panel izquierdo y toca el botón para agregarlos aquí.
                </p>
            </div>
        </aside>

    </main>

</body>
</html>