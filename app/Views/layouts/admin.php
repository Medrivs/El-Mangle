<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El Mangle - Sistema de Gestión</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <?php 
    $uri = service('uri')->getSegment(1); 
    ?>

    <aside class="w-64 bg-[#111827] text-white flex flex-col flex-shrink-0">
        <div class="p-6">
            <h1 class="text-2xl font-bold tracking-wider">El Mangle</h1>
        </div>
        
        <nav class="flex-1 px-4 space-y-2 mt-4 overflow-y-auto">
            <a href="<?= base_url('usuarios') ?>" 
               class="block px-4 py-2 rounded-lg transition-colors <?= ($uri == 'usuarios' || $uri == '') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
               Usuarios
            </a>

            <a href="<?= base_url('materiaprima') ?>" 
               class="block px-4 py-2 rounded-lg transition-colors <?= ($uri == 'materiaprima') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
               Materia Prima
            </a>

            <a href="<?= base_url('platillos') ?>" 
               class="block px-4 py-2 rounded-lg transition-colors <?= ($uri == 'platillos') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
               Platillos
            </a>

            <a href="<?= base_url('mesas') ?>" 
               class="block px-4 py-2 rounded-lg transition-colors <?= ($uri == 'mesas') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?>">
               Mesas
            </a>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <a href="#" class="block px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors">
                Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <?= $this->renderSection('content') ?>
    </main>

</body>
</html>