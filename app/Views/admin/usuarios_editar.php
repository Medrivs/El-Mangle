<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
    <h2 class="text-2xl font-bold mb-6">Editar Usuario</h2>
    
    <form action="<?= base_url('usuarios/actualizar/'.$usuario['id_usuario']) ?>" method="post" class="space-y-4">
        <?= csrf_field() ?>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre Completo</label>
            <input type="text" name="nombre_completo" value="<?= $usuario['nombre_completo'] ?>" required class="w-full p-2 border rounded mt-1">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" value="<?= $usuario['username'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Rol</label>
                <select name="id_rol" class="w-full p-2 border rounded mt-1">
                    <?php 
                    // Lista de roles idéntica a la de tu base de datos
                    $roles = [1=>'Administrador', 2=>'Capitán', 3=>'Mesero', 4=>'Chef', 5=>'Caja', 6=>'Cocina'];
                    foreach($roles as $id => $nombre): ?>
                        <option value="<?= $id ?>" <?= ($usuario['id_rol'] == $id) ? 'selected' : '' ?>><?= $nombre ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nueva Contraseña (vacío para no cambiar)</label>
                <input type="password" name="password" class="w-full p-2 border rounded mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input type="tel" name="telefono" value="<?= $usuario['telefono'] ?>" class="w-full p-2 border rounded mt-1">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha de Ingreso</label>
                <input type="date" name="fecha_ingreso" value="<?= $usuario['fecha_ingreso'] ?>" class="w-full p-2 border rounded mt-1">
            </div>
            <div class="flex items-center pt-6">
                <input type="checkbox" name="estado_usuario" value="1" <?= ($usuario['estado_usuario'] == 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600">
                <label class="ml-2 block text-sm text-gray-700">Usuario Activo</label>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <a href="<?= base_url('usuarios') ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Cambios</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>