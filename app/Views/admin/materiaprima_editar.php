<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Editar Materia Prima</h2>
    
    <form action="<?= base_url('materiaprima/actualizar/'.$materia['id_materia_prima']) ?>" method="post" class="space-y-4">
        <?= csrf_field() ?>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
            <input type="text" name="nombre_producto" value="<?= $materia['nombre_producto'] ?>" required class="w-full p-2 border rounded mt-1">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Stock Actual</label>
                <input type="number" step="0.01" name="stock_actual" value="<?= $materia['stock_actual'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Unidad de Medida</label>
                <select name="unidad_medida" class="w-full p-2 border rounded mt-1">
                    <?php 
                    $unidades = ['Kg' => 'Kilogramos (Kg)', 'Gramos' => 'Gramos (g)', 'Litros' => 'Litros (L)', 'Mililitros' => 'Mililitros (ml)', 'Piezas' => 'Piezas (Pza)'];
                    foreach($unidades as $valor => $label): ?>
                        <option value="<?= $valor ?>" <?= ($materia['unidad_medida'] == $valor) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Precio de Compra</label>
                <input type="number" step="0.01" name="precio_compra" value="<?= $materia['precio_compra'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Stock Mínimo (Alerta)</label>
                <input type="number" step="0.01" name="stock_minimo" value="<?= $materia['stock_minimo'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha de Última Entrada</label>
                <input type="date" name="fecha_ultima_entrada" value="<?= $materia['fecha_ultima_entrada'] ?>" required class="w-full p-2 border rounded mt-1">
            </div>
            <div class="flex items-center pt-6">
                <input type="checkbox" name="estado_materia" value="1" <?= ($materia['estado_materia'] == 1) ? 'checked' : '' ?> class="h-4 w-4 text-blue-600">
                <label class="ml-2 block text-sm text-gray-700">Producto Activo / Disponible (Dar de alta)</label>
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <a href="<?= base_url('materiaprima') ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Cambios</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>