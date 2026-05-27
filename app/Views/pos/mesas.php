<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>El Mangle - POS Simplificado</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <header class="bg-blue-900 text-white p-4 flex justify-between items-center shadow">
        <div>
            <h1 class="text-2xl font-bold">El Mangle - Punto de Venta</h1>
            <p class="text-sm text-blue-200">Mesero: <?= session()->get('nombre') ?></p>
        </div>
        
        <a href="<?= base_url('login/salir') ?>" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-bold transition">
            Cerrar Sesión
        </a>
    </header>

    <main class="p-6">
        <h2 class="text-xl font-bold mb-6 text-gray-800">Mapa de Mesas Disponibles</h2>

        <div class="grid grid-cols-4 gap-4">
            
            <?php foreach($mesas as $m): ?>
                <?php
                    // LÓGICA DE COLORES EN ESPAÑOL:
                    // Creamos una variable para guardar los colores dependiendo del estado de la mesa
                    $estiloColor = 'bg-white border-gray-300 text-gray-800'; // Por defecto

                    if ($m['estado_mesa'] == 'Libre') {
                        $estiloColor = 'bg-green-100 border-green-500 text-green-800'; // Verde
                    } elseif ($m['estado_mesa'] == 'Ocupada') {
                        $estiloColor = 'bg-red-100 border-red-500 text-red-800';     // Rojo
                    } elseif ($m['estado_mesa'] == 'Sucia') {
                        $estiloColor = 'bg-yellow-100 border-yellow-500 text-yellow-800'; // Amarillo
                    }
                ?>
                
                <a href="<?= base_url('pos/mesa/'.$m['id_mesa']) ?>" class="border-2 <?= $estiloColor ?> p-6 rounded-lg text-center shadow hover:opacity-80 transition block">
                    <div class="text-2xl font-bold">Mesa <?= $m['numero_mesa'] ?></div>
                    
                    <div class="text-sm font-bold uppercase mt-2 tracking-wider"><?= $m['estado_mesa'] ?></div>
                    
                    <div class="text-xs text-gray-500 mt-1">Capacidad: <?= $m['capacidad'] ?> personas</div>
                </a>
            <?php endforeach; ?>

        </div>
    </main>

</body>
</html>