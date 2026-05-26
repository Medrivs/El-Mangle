<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Registrar Materia Prima</h2>
    
    <form action="<?= base_url('materiaprima/guardar') ?>" method="post" class="space-y-4">
        <?= csrf_field() ?>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
            <input type="text" name="nombre_producto" placeholder="Ej. Camarón Crudo 21/25" required class="w-full p-2 border rounded mt-1">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Stock Actual</label>
                <input type="number" step="0.01" name="stock_actual" required class="w-full p-2 border rounded mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Unidad de Medida</label>
                <select name="unidad_medida" class="w-full p-2 border rounded mt-1">
                    <option value="Kg">Kilogramos (Kg)</option>
                    <option value="Gramos">Gramos (g)</option>
                    <option value="Litros">Litros (L)</option>
                    <option value="Mililitros">Mililitros (ml)</option>
                    <option value="Piezas">Piezas (Pza)</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Precio de Compra (Total)</label>
                <input type="number" step="0.01" name="precio_compra" placeholder="0.00" required class="w-full p-2 border rounded mt-1">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Stock Mínimo (Alerta)</label>
                <input type="number" step="0.01" name="stock_minimo" placeholder="Ej. 5.00" required class="w-full p-2 border rounded mt-1">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Fecha de Última Entrada</label>
            <input type="date" name="fecha_ultima_entrada" required class="w-full p-2 border rounded mt-1">
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <a href="<?= base_url('materiaprima') ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Producto</button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>