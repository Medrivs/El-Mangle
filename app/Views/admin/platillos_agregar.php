<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<!-- paso 2 del rf 2 2 despliega el formulario -->
<div class="max-w-2xl mx-auto bg-white p-8 rounded-xl shadow border">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Registrar Nuevo Platillo</h2>
    
    <form action="<?= base_url('platillos/guardar') ?>" method="post" enctype="multipart/form-data" class="space-y-6">
        <?= csrf_field() ?>
        
        <!-- paso 3 del rf 2 2 captura la informacion y selecciona la imagen -->
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre del Platillo</label>
                <input type="text" name="nombre_platillo" placeholder="Ej Ceviche Peruano" class="w-full p-2 border rounded mt-1">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <select name="id_categoria" class="w-full p-2 border rounded mt-1">
                    <option value="">Seleccione</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id_categoria'] ?>"><?= $cat['nombre_categoria'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Subcategoría (Pestaña en POS)</label>
                <select name="subcategoria" class="w-full p-2 border rounded mt-1">
                    <option value="">Seleccione</option>
                    <?php foreach($subcategorias as $sub): ?>
                        <?php if(!empty($sub['subcategoria'])): ?>
                            <option value="<?= $sub['subcategoria'] ?>"><?= $sub['subcategoria'] ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Precio de Venta ($)</label>
                <input type="number" step="0.01" name="precio_venta" placeholder="0.00" class="w-full p-2 border rounded mt-1">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Subir Fotografía</label>
                <input type="file" name="imagen" accept="image/*" class="w-full p-2 border rounded mt-1 bg-gray-50">
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Descripción para el Cliente</label>
                <textarea name="descripcion" rows="3" placeholder="Ingredientes o detalles que hacen especial al platillo" class="w-full p-2 border rounded mt-1"></textarea>
            </div>
        </div>

        <!-- seccion de la receta para control de inventario dinamico -->
        <div class="border-t pt-4">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Receta (Control de Insumos)</h3>
            <p class="text-xs text-gray-500 mb-4">define cuanto inventario se consume al vender este platillo</p>
            
            <div id="contenedor-receta">
                <!-- renglon base clonable con boton de eliminar -->
                <div class="flex gap-4 mb-2 receta-item items-center">
                    <div class="w-7/12">
                        <select name="id_materia_prima[]" class="w-full p-2 border rounded">
                            <option value="">Seleccionar insumo</option>
                            <?php foreach($materias as $mat): ?>
                                <option value="<?= $mat['id_materia_prima'] ?>"><?= $mat['nombre_producto'] ?> (<?= $mat['unidad_medida'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-4/12">
                        <input type="number" step="0.001" name="cantidad_usada[]" placeholder="Cantidad usada" class="w-full p-2 border rounded">
                    </div>
                    <div class="w-1/12 text-center">
                        <button type="button" onclick="quitarInsumo(this)" class="text-red-500 hover:text-red-700 font-bold text-lg" title="Quitar insumo">X</button>
                    </div>
                </div>
            </div>
            
            <!-- boton interactivo para agregar mas ingredientes -->
            <button type="button" onclick="agregarInsumo()" class="text-sm text-blue-600 hover:underline mt-2">+ añadir otro ingrediente</button>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <!-- excepcion 1 del rf 2 2 si se selecciona cancelar -->
            <a href="<?= base_url('platillos') ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 text-gray-800">Cancelar</a>
            <!-- paso 4 del rf 2 2 selecciona la opcion guardar -->
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Platillo</button>
        </div>
    </form>
</div>

<script>
// funcion para clonar renglones de ingredientes
function agregarInsumo() {
    const contenedor = document.getElementById('contenedor-receta');
    const nuevoInsumo = contenedor.children[0].cloneNode(true);
    nuevoInsumo.querySelector('select').value = '';
    nuevoInsumo.querySelector('input').value = '';
    contenedor.appendChild(nuevoInsumo);
}

// funcion para eliminar un renglon de ingrediente
function quitarInsumo(boton) {
    const contenedor = document.getElementById('contenedor-receta');
    if (contenedor.children.length > 1) {
        boton.closest('.receta-item').remove();
    } else {
        // si es el ultimo renglon solo vaciamos los campos para no dejar sin interfaz
        const fila = boton.closest('.receta-item');
        fila.querySelector('select').value = '';
        fila.querySelector('input').value = '';
    }
}
</script>
<?= $this->endSection() ?>