<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="flex justify-between items-center mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Inventario: Materia Prima</h2>
    
    <a href="<?= base_url('materiaprima/agregar') ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
        + Registrar Producto
    </a>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-4 font-semibold text-gray-700">Producto</th>
                <th class="p-4 font-semibold text-gray-700">Stock Actual</th>
                <th class="p-4 font-semibold text-gray-700">Unidad</th>
                <th class="p-4 font-semibold text-gray-700">Precio Compra</th>
                <th class="p-4 font-semibold text-gray-700">Última Entrada</th>
                <th class="p-4 font-semibold text-gray-700">Acciones</th>
            </tr>
        </thead>
        
        <tbody class="divide-y">
            <?php if(empty($materias)): ?>
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">No hay productos registrados en el inventario.</td>
                </tr>
            <?php else: ?>
                <?php foreach($materias as $m): ?>
                <tr class="hover:bg-gray-50 transition <?= ($m['estado_materia'] == 0) ? 'bg-red-50 opacity-75' : '' ?>">
                    <td class="p-4">
                        <div class="font-medium text-gray-800"><?= $m['nombre_producto'] ?></div>
                        <?php if($m['estado_materia'] == 1): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-1">Activo</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mt-1">Dado de baja</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="p-4 <?= ($m['stock_actual'] <= $m['stock_minimo']) ? 'text-red-600 font-bold' : 'text-green-600 font-medium' ?>">
                        <?= $m['stock_actual'] ?>
                    </td>
                    
                    <td class="p-4 text-gray-600"><?= $m['unidad_medida'] ?></td>
                    <td class="p-4 text-gray-600">$<?= number_format($m['precio_compra'], 2) ?></td>
                    <td class="p-4 text-gray-600"><?= $m['fecha_ultima_entrada'] ?></td>
                    <td class="p-4 flex gap-4">
                        <a href="<?= base_url('materiaprima/editar/'.$m['id_materia_prima']) ?>" class="text-blue-600 hover:underline font-medium">Editar</a>
                        <?php if($m['estado_materia'] == 1): ?>
                            <a href="<?= base_url('materiaprima/eliminar/'.$m['id_materia_prima']) ?>" onclick="return confirm('¿Dar de baja este producto?')" class="text-red-600 hover:underline font-medium">Dar de baja</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>