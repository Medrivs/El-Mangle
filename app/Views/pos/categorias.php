<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - Seleccionar Categoria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#EFF6FF] font-sans h-screen flex overflow-hidden">

    <main class="flex-1 flex flex-col min-w-0">
        
        <header class="bg-[#0A1F3D] text-white p-4 flex justify-between items-center shadow-md shrink-0">
            <div class="flex items-center gap-4">
                <a href="<?= base_url('pos') ?>" class="bg-blue-800 hover:bg-blue-700 p-2 px-4 rounded-xl font-bold text-sm transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Regresar a Mesas
                </a>
                <span class="text-xl font-bold border-l border-gray-600 pl-6 tracking-tight">NUEVA ORDEN</span>
            </div>
            <div class="text-right">
                <div class="font-bold text-lg">Mesa <?= $mesa['numero_mesa'] ?></div>
                <div class="text-[10px] text-[#00B4D8] uppercase tracking-widest font-bold">Selecciona categoria</div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            <h2 class="text-[#185392] font-black tracking-widest uppercase mb-8 border-b-2 border-blue-100 pb-2">
                Categorias Disponibles
            </h2>
            
            <div class="grid grid-cols-2 gap-8 max-w-4xl">
                <?php 
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
                       class="bg-white border-2 border-gray-100 p-10 rounded-3xl text-center shadow-sm hover:border-[#1565C0] hover:shadow-xl transition-all group">
                        
                        <div class="bg-gray-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:bg-blue-50 transition">
                            <i class="fa-solid <?= $conf['icon'] ?> text-5xl <?= $conf['color'] ?>"></i>
                        </div>
                        
                        <div class="text-2xl font-black text-[#0A192F] uppercase tracking-tighter">
                            <?= $cat['nombre_categoria'] ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

<?= $this->include('pos/partials/carrito') ?>

    <script src="<?= base_url('js/carrito.js') ?>"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Verificamos si existe el carrito en el DOM antes de dibujar
            if (document.getElementById('carrito-items')) {
                dibujarCarrito();
            }
        });
    </script>

</body>
</html>