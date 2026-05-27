<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - Seleccionar Categoría</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden">

    <header class="bg-[#0A192F] text-white p-4 flex justify-between items-center shadow-lg shrink-0">
        <div class="flex items-center gap-6">
            <a href="<?= base_url('pos') ?>" class="text-gray-400 hover:text-white transition">
                <i class="fa-solid fa-chevron-left"></i> Mesa <?= $mesa['numero_mesa'] ?>
            </a>
            <span class="text-xl font-bold border-l border-gray-600 pl-6">NUEVA ORDEN</span>
        </div>
        <div class="text-right">
            <div class="font-bold text-lg">Mesa <?= $mesa['numero_mesa'] ?></div>
            <div class="text-xs text-[#00B4D8]">ORDEN EN PREPARACIÓN</div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        
        <div class="flex-1 p-8 overflow-y-auto">
            <h2 class="text-[#185392] font-black tracking-widest uppercase mb-8 border-b-2 border-blue-100 pb-2">
                Selecciona una Categoría
            </h2>
            
            <div class="grid grid-cols-2 gap-8">
                <?php 
                // Definimos iconos manuales para cada categoría por su nombre
                $iconos = [
                    'Bebidas' => ['icon' => 'fa-glass-water', 'color' => 'text-blue-500'],
                    'Cocina Caliente' => ['icon' => 'fa-fire-burner', 'color' => 'text-orange-500'],
                    'Barra Fria' => ['icon' => 'fa-snowflake', 'color' => 'text-cyan-500'],
                    'Postres' => ['icon' => 'fa-ice-cream', 'color' => 'text-pink-500']
                ];
                
                foreach($categorias as $cat): 
                    $conf = $iconos[$cat['nombre_categoria']] ?? ['icon' => 'fa-utensils', 'color' => 'text-gray-500'];
                ?>
                    <a href="<?= base_url('pos/filtrar/'.$mesa['id_mesa'].'/'.$cat['id_categoria']) ?>" 
                       class="bg-white border-2 border-gray-100 p-10 rounded-3xl text-center shadow-sm hover:border-blue-400 hover:shadow-xl transition-all group">
                        
                        <div class="bg-gray-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition">
                            <i class="fa-solid <?= $conf['icon'] ?> text-5xl <?= $conf['color'] ?>"></i>
                        </div>
                        
                        <div class="text-2xl font-black text-[#0A192F] uppercase tracking-tighter">
                            <?= $cat['nombre_categoria'] ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="w-96 bg-white border-l border-gray-200 flex flex-col shadow-2xl shrink-0">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center gap-3 text-[#185392] mb-1">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <h2 class="text-lg font-black uppercase tracking-tighter">Lista Temporal de Orden</h2>
                </div>
                <p class="text-xs text-gray-400">Agrega platillos, luego envía a cocina</p>
            </div>
            
            <div class="flex-1 flex flex-col items-center justify-center p-10 text-center">
                <div class="bg-blue-50 p-6 rounded-full mb-4">
                    <i class="fa-solid fa-basket-shopping text-4xl text-blue-200"></i>
                </div>
                <h3 class="text-gray-800 font-bold mb-2">Carrito vacío</h3>
                <p class="text-sm text-gray-400 leading-tight">
                    Selecciona una categoría y toca un platillo para agregarlo aquí.
                </p>
            </div>

            <div class="p-6 bg-gray-50 border-t border-gray-200">
                <div class="flex justify-between items-end mb-6">
                    <span class="text-gray-400 font-bold text-sm">TOTAL ESTIMADO</span>
                    <span class="text-3xl font-black text-[#0A192F]">$0.00</span>
                </div>
                <button disabled class="w-full bg-gray-300 text-white font-black py-4 rounded-2xl cursor-not-allowed uppercase tracking-widest">
                    Enviar a Cocina
                </button>
            </div>
        </aside>

    </main>

</body>
</html>