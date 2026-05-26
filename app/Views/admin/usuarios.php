<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<h2 class="text-3xl font-bold mb-6">Gestión de Usuarios</h2>

<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-4">Nombre</th>
                <th class="p-4">Usuario</th>
                <th class="p-4">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach($usuarios as $u): ?>
            <tr>
                <td class="p-4"><?= $u['nombre_completo'] ?></td>
                <td class="p-4"><?= $u['username'] ?></td>
                <td class="p-4">
                    <a href="/usuarios/eliminar/<?= $u['id_usuario'] ?>" class="text-red-600">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>