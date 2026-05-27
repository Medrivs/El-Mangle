<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Personalizar Platillo</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">Personaliza: <?= $platillo['nombre_platillo'] ?></h1>
        
        <p class="text-gray-600 mb-6"><?= $platillo['descripcion'] ?></p>

        <div class="grid grid-cols-2 gap-4">
            <button class="bg-blue-600 text-white p-4 rounded hover:bg-blue-700">Grande</button>
            <button class="bg-gray-200 p-4 rounded hover:bg-gray-300">Mediano</button>
        </div>

        <a href="<?= base_url('pos/mesa/'.$mesa['id_mesa']) ?>" class="block mt-6 text-center text-blue-600 underline">Cancelar</a>
    </div>
</body>
</html>