<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>El Mangle - Admin</title>
</head>
<body class="bg-gray-100 flex min-h-screen">
    <aside class="w-64 bg-slate-900 text-white p-6">
        <h1 class="text-2xl font-bold mb-10">El Mangle</h1>
        <nav class="space-y-4">
            <a href="/usuarios" class="block py-2 px-4 bg-blue-600 rounded">Usuarios</a>
        </nav>
    </aside>
    <main class="flex-1 p-8">
        <?= $this->renderSection('content') ?>
    </main>
</body>
</html>