<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comanda - Mesa <?= $mesa['numero_mesa'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden">

    <header class="bg-blue-900 text-white p-4 flex justify-between items-center shadow shrink-0">
        <div class="flex items-center gap-4">
            <a href="<?= base_url('pos') ?>" class="bg-blue-700 hover:bg-blue-600 px-4 py-2 rounded font-bold transition">
                ← Volver
            </a>
            <div>
                <h1 class="text-2xl font-bold">Mesa <?= $mesa['numero_mesa'] ?></h1>
                <p class="text-sm text-blue-200">Atiende: <?= session()->get('nombre') ?></p>
            </div>
        </div>
    </header>

    <main class="flex-1 flex overflow-hidden">
        
        <div class="flex-1 p-6 overflow-y-auto">
            <h2 class="text-lg font-bold text-gray-700 mb-4">Menú Disponible</h2>
            
            <div class="grid grid-cols-3 gap-4">
                <?php foreach($platillos as $p): ?>
                    <button class="bg-white border-2 border-gray-200 p-4 rounded-lg text-left shadow-sm hover:border-blue-500 hover:shadow transition">
                        <div class="font-bold text-gray-800 text-lg leading-tight mb-2"><?= $p['nombre_platillo'] ?></div>
                        <div class="text-blue-600 font-black text-xl">$<?= number_format($p['precio_venta'], 2) ?></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <aside class="w-80 bg-white border-l border-gray-300 flex flex-col shadow-xl z-10 shrink-0">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 text-center uppercase tracking-widest">Comanda</h2>
            </div>
            
            <div class="flex-1 p-4 overflow-y-auto bg-gray-50">
                <p class="text-center text-gray-400 mt-10 text-sm italic">
                    Aún no hay platillos en esta orden.<br>Toca un platillo del menú para agregarlo.
                </p>
                
                </div>

            <div class="p-4 bg-white border-t border-gray-200">
                <div class="flex justify-between text-xl font-black text-gray-800 mb-4">
                    <span>Total:</span>
                    <span>$0.00</span>
                </div>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded text-lg transition shadow-lg">
                    Enviar a Cocina
                </button>
            </div>
        </aside>

    </main>

</body>
</html>