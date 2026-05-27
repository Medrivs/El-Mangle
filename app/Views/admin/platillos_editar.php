<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Editar Platillo</h2>
    
    <form action="<?= base_url('platillos/actualizar/'.$platillo['id_platillo']) ?>" method="post" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>
        
        <input type="hidden" name="imagen_actual" value="<?= $platillo['imagen_url'] ?>">
        
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre del Platillo</label>
                <input type="text" name="nombre_platillo" value="<?= $platillo['nombre_platillo'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <select name="id_categoria" required class="w-full p-2 border rounded mt-1">
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>" <?= ($platillo['id_categoria'] == $cat['id_categoria']) ? 'selected' : '' ?>>
                            <?= $cat['nombre_categoria'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Precio de Venta ($)</label>
                <input type="number" step="0.01" name="precio_venta" value="<?= $platillo['precio_venta'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>
            
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Cambiar Fotografía</label>
                <input type="file" name="imagen" accept="image/*" class="w-full p-2 border rounded mt-1 bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">Si no deseas cambiar la foto actual, deja este campo vacío.</p>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Descripción para el Cliente</label>
                <textarea name="descripcion" rows="3" class="w-full p-2 border rounded mt-1"><?= $platillo['descripcion'] ?></textarea>
            </div>
            
            <div class="col-span-2 flex items-center pt-2">
                <input type="checkbox" name="disponible" value="1" <?= ($platillo['disponible'] == 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600">
                <label class="ml-2 block text-sm text-gray-700">Platillo Disponible en el Menú (Dar de alta)</label>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <a href="<?= base_url('platillos') ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Cambios</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>