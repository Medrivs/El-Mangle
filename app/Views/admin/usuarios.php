<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="flex justify-between items-center mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Gestión de Usuarios</h2>
    
    <a href="<?= base_url('usuarios/agregar') ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        + Agregar Usuario
    </a>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-4 font-semibold text-gray-700">Nombre</th>
                <th class="p-4 font-semibold text-gray-700">Usuario</th>
                <th class="p-4 font-semibold text-gray-700">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach($usuarios as $u): ?>
            <tr class="hover:bg-gray-50 transition">
                <td class="p-4 text-gray-600"><?= $u['nombre_completo'] ?></td>
                <td class="p-4 text-gray-600"><?= $u['username'] ?></td>
                <td class="p-4 flex gap-4">
                    <a href="<?= base_url('usuarios/editar/'.$u['id_usuario']) ?>" class="text-blue-600 hover:underline font-medium">
                        Editar
                    </a>
                    
                    <a href="<?= base_url('usuarios/eliminar/'.$u['id_usuario']) ?>" 
                       onclick="return confirm('¿Estás seguro de eliminar a este usuario?')" 
                       class="text-red-600 hover:underline font-medium">
                        Eliminar
                    </a>
                </td>
                <td class="p-4">
    <?php if($u['estado_usuario'] == 1): ?>
        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Activo</span>
    <?php else: ?>
        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Dado de Baja</span>
    <?php endif; ?>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>