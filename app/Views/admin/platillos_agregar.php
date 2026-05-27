<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Registrar Nuevo Platillo</h2>
    
    <form action="<?= base_url('platillos/guardar') ?>" method="post" enctype="multipart/form-data" class="space-y-4">
        <?= csrf_field() ?>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre del Platillo</label>
                <input type="text" name="nombre_platillo" placeholder="Ej. Ceviche Peruano" required class="w-full p-2 border rounded mt-1">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <select name="id_categoria" required class="w-full p-2 border rounded mt-1">
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"><?= $cat['nombre_categoria'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Precio de Venta ($)</label>
                <input type="number" step="0.01" name="precio_venta" placeholder="0.00" required class="w-full p-2 border rounded mt-1">
            </div>
            
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Subir Fotografía</label>
                <input type="file" name="imagen" accept="image/*" class="w-full p-2 border rounded mt-1 bg-gray-50">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Descripción para el Cliente</label>
                <textarea name="descripcion" rows="3" placeholder="Ingredientes o detalles que hacen especial al platillo..." class="w-full p-2 border rounded mt-1"></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <a href="<?= base_url('platillos') ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Platillo</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>