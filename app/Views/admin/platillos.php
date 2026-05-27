<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="flex justify-between items-center mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Menú de Platillos</h2>
    
    <a href="<?= base_url('platillos/agregar') ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
        + Registrar Platillo
    </a>
</div>

<div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200">
    <table class="w-full text-left text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="p-4 font-semibold text-gray-700">Platillo</th>
                <th class="p-4 font-semibold text-gray-700 w-1/3">Descripción</th>
                <th class="p-4 font-semibold text-gray-700">Categoría</th>
                <th class="p-4 font-semibold text-gray-700">Precio</th>
                <th class="p-4 font-semibold text-gray-700">Acciones</th>
            </tr>
        </thead>
        
        <tbody class="divide-y">
            <?php if(empty($platillos)): ?>
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">No hay platillos registrados en el menú.</td>
                </tr>
            <?php else: ?>
                <?php foreach($platillos as $p): ?>
                <tr class="hover:bg-gray-50 transition <?= ($p['disponible'] == 0) ? 'bg-red-50 opacity-75' : '' ?>">
                    
                    <td class="p-4 flex items-center gap-4">
                        
                        <div class="w-16 h-16 flex-shrink-0 bg-gray-100 rounded-lg border overflow-hidden flex items-center justify-center">
                            <?php if(!empty($p['imagen_url'])): ?>
                               <img src="<?= base_url($p['imagen_url']) ?>" alt="<?= $p['nombre_platillo'] ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-gray-400 text-[10px] text-center uppercase tracking-wider font-semibold">Sin<br>Foto</span>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <div class="font-bold text-gray-800 text-base"><?= $p['nombre_platillo'] ?></div>
                            <?php if($p['disponible'] == 1): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mt-1">Disponible</span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mt-1">Agotado / Baja</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <td class="p-4 text-gray-500 italic"><?= $p['descripcion'] ?></td>
                    <td class="p-4 text-gray-600 font-medium"><?= $p['nombre_categoria'] ?></td>
                    <td class="p-4 text-gray-800 font-bold">$<?= number_format($p['precio_venta'], 2) ?></td>
                    
                    <td class="p-4">
                        <div class="flex flex-col gap-2 items-start">
                            <a href="<?= base_url('platillos/editar/'.$p['id_platillo']) ?>" class="text-blue-600 hover:underline font-medium">Editar</a>
                            <?php if($p['disponible'] == 1): ?>
                                <a href="<?= base_url('platillos/eliminar/'.$p['id_platillo']) ?>" onclick="return confirm('¿Marcar este platillo como no disponible?')" class="text-red-600 hover:underline font-medium">Dar de baja</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>