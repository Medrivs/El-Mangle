<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>POS - <?= $categoria['nombre_categoria'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#EFF6FF] flex h-screen w-full overflow-hidden font-sans">

    <main class="flex-1 min-w-0 flex flex-col h-full overflow-hidden">
        
        <header class="bg-[#0A1F3D] text-white p-4 flex gap-4 items-center shrink-0">
            <?php if ($pestaña_activa === null): ?>
                <a href="<?= base_url('pos/mesa/'.$mesa['id_mesa']) ?>" class="bg-blue-800 hover:bg-blue-700 p-2 px-4 rounded-xl font-bold text-sm transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Volver a Menu Principal
                </a>
            <?php else: ?>
                <a href="<?= base_url('pos/filtrar/'.$mesa['id_mesa'].'/'.$categoria['id_categoria']) ?>" class="bg-orange-600 hover:bg-orange-700 p-2 px-4 rounded-xl font-bold text-sm transition">
                    <i class="fa-solid fa-container-storage mr-1"></i> ← Ver todas las Subcategorias
                </a>
            <?php endif; ?>
            
            <h1 class="font-black text-lg tracking-wide uppercase">
                <?= $categoria['nombre_categoria'] ?> 
                <?= $pestaña_activa ? "› <span class='text-[#00B4D8]'>".$pestaña_activa."</span>" : "" ?>
            </h1>
        </header>

        <div class="flex-1 overflow-y-auto p-6">
            <?php if ($pestaña_activa === null): ?>
                <h3 class="text-xs font-bold text-[#1565C0] tracking-widest uppercase mb-6">Selecciona una Subcategoria:</h3>
                
                <div class="grid grid-cols-2 gap-6">
                    <?php foreach($pestañas as $pest): ?>
                        <a href="<?= base_url('pos/filtrar/'.$mesa['id_mesa'].'/'.$categoria['id_categoria'].'/'.urlencode($pest['subcategoria'])) ?>" 
                           class="bg-white p-8 rounded-3xl border-2 border-transparent shadow-sm hover:border-[#1565C0] hover:shadow-xl transition-all flex flex-col items-center justify-center text-center group">
                            <div class="w-16 h-16 bg-blue-50 text-[#1565C0] rounded-2xl flex items-center justify-center text-3xl mb-4 group-hover:bg-[#1565C0] group-hover:text-white transition-colors">
                                ⚓
                            </div>
                            <span class="font-black text-xl text-[#0A1F3D] uppercase tracking-tight"><?= $pest['subcategoria'] ?></span>
                            <span class="text-xs text-gray-400 mt-1">Explorar variedad</span>
                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <h3 class="text-xs font-bold text-[#1565C0] tracking-widest uppercase mb-6">Platillos en <?= $pestaña_activa ?>:</h3>
                
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach($platillos as $p): 
                        // Reglas de renderizado SoftRestaurant
                        $onclickAction = "abrirModal(".$p['id_platillo'].", '".addslashes($p['nombre_platillo'])."', ".$p['precio_venta'].", '".addslashes($p['subcategoria'])."')";
                        $cardClasses = "bg-white p-5 rounded-2xl shadow-sm border-2 border-blue-50 hover:border-blue-500 text-center transition-all flex flex-col items-center justify-between h-44 relative overflow-hidden";
                        $badgeOverlay = "";

                        if (isset($p['ingredientes_bloqueados']) && $p['ingredientes_bloqueados']) {
                            $onclickAction = "alert('¡Agotado! Este platillo contiene insumos bloqueados por la cocina temporalmente.')";
                            $cardClasses = "bg-gray-200/60 p-5 rounded-2xl shadow-sm border-2 border-gray-300 text-center flex flex-col items-center justify-between h-44 relative overflow-hidden opacity-50 cursor-not-allowed text-gray-400";
                            $badgeOverlay = "<div class='absolute top-2 left-2 bg-red-600 text-white text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider shadow-md z-10'>Agotado</div>";
                        } elseif (isset($p['ingredientes_alerta']) && $p['ingredientes_alerta']) {
                            $badgeOverlay = "<div class='absolute top-2 left-2 bg-amber-500 text-white text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-wider shadow-md z-10 animate-pulse'>⚠️ Poco Stock</div>";
                        }
                    ?>
                        <button onclick="<?= $onclickAction ?>" class="<?= $cardClasses ?>">
                            <?= $badgeOverlay ?>
                            <div class="text-3xl text-blue-500"><i class="fa-solid fa-martini-glass-citrus"></i></div>
                            <div class="font-bold text-[#0A1F3D] text-sm leading-tight line-clamp-2 my-2"><?= $p['nombre_platillo'] ?></div>
                            <div class="text-[#1565C0] font-black">$<?= number_format($p['precio_venta'], 2) ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </main>

    <dialog id="modalPlatillo" class="bg-white p-6 rounded-3xl shadow-2xl w-full max-w-md backdrop:bg-black/60 backdrop:backdrop-blur-sm">
        <h3 id="modalNombre" class="font-black text-xl text-[#0A1F3D] mb-4 border-b border-gray-100 pb-3">Personalizar</h3>
        
        <div id="seccion-tamano">
            <label class="block font-black text-[#1565C0] mb-2 text-xs uppercase tracking-widest">1. Tamano:</label>
            <div class="flex gap-2 mb-5">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="tamano" value="Mediano" class="peer hidden" checked>
                    <div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-3 rounded-xl font-bold transition">Mediano</div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="tamano" value="Grande" class="peer hidden">
                    <div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-3 rounded-xl font-bold transition">Grande</div>
                </label>
            </div>
        </div>

        <div id="seccion-proteina">
            <label class="block font-black text-[#1565C0] mb-2 text-xs uppercase tracking-widest">2. Seleccion de Mariscos:</label>
            <div class="grid grid-cols-2 gap-2 mb-6">
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Solo Camaron" class="peer hidden" checked><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Solo Camaron</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Solo Pulpo" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Solo Pulpo</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Solo Ostion" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Solo Ostion</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Camaron y Pulpo" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Camaron y Pulpo</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Ostion y Pulpo" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Ostion y Pulpo</div></label>
                <label class="cursor-pointer"><input type="radio" name="proteina" value="Campechano" class="peer hidden"><div class="peer-checked:bg-[#1565C0] peer-checked:text-white peer-checked:border-[#1565C0] bg-gray-50 border-2 border-gray-200 text-gray-600 text-center py-2 rounded-xl font-bold text-sm transition">Campechano</div></label>
            </div>
        </div>

        <div class="flex gap-4 mb-8">
            <div class="w-1/4">
                <label class="font-black text-gray-700 text-xs uppercase mb-1 block">Cant.</label>
                <input type="number" id="modalCant" value="1" min="1" class="border-2 border-gray-200 bg-gray-50 w-full p-3 rounded-xl text-center font-bold text-lg focus:outline-none focus:border-[#1565C0]">
            </div>
            <div class="w-3/4">
                <label class="font-black text-gray-700 text-xs uppercase mb-1 block">Comentario</label>
                <input type="text" id="modalNota" class="border-2 border-orange-200 bg-orange-50 w-full p-3 rounded-xl font-medium text-orange-900 placeholder-orange-300 focus:outline-none focus:border-orange-400">
            </div>
        </div>

        <div class="flex gap-3">
            <button onclick="document.getElementById('modalPlatillo').close()" class="w-1/3 bg-gray-200 hover:bg-gray-300 text-gray-700 py-4 rounded-xl font-black transition">Cancelar</button>
            <button onclick="guardarAlCarrito()" class="w-2/3 bg-[#1565C0] hover:bg-blue-700 text-white py-4 rounded-xl font-black shadow-lg shadow-blue-500/30 transition flex justify-center items-center gap-2">
                <i class="fa-solid fa-plus"></i> Añadir a Orden
            </button>
        </div>
    </dialog>

    <?= $this->include('pos/partials/carrito') ?> 

</body>
</html>