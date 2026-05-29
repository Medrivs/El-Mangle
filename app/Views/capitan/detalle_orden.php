<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Edición de Orden - El Mangle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#F8FAFC] flex flex-col h-screen font-sans">

    <!-- HEADER -->
    <header class="bg-[#0A1F3D] text-white px-6 py-4 flex justify-between items-center shadow-md">
        <div class="flex items-center gap-4">
            <a href="<?= base_url('capitan') ?>" class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2">
                    Mesa <?= $mesa['numero_mesa'] ?> 
                    <span class="bg-red-500 text-xs px-2 py-1 rounded-md uppercase tracking-wide">Modo <?= ucfirst($modo) ?></span>
                </h1>
                <p class="text-xs text-gray-300 uppercase tracking-widest mt-1">Atendido por: <?= $mesa['mesero'] ?></p>
            </div>
        </div>
    </header>

    <!-- ALERTAS -->
    <?php if(session()->getFlashdata('success')): ?>
        <div class="bg-green-500 text-white font-bold text-center py-2 shadow-sm"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <main class="flex-1 overflow-y-auto p-6 max-w-3xl mx-auto w-full">
        
        <?php if(empty($detalles)): ?>
            <div class="text-center text-gray-400 mt-20">
                <i class="fa-solid fa-receipt text-6xl mb-4 opacity-30"></i>
                <h2 class="text-xl font-bold">La orden está vacía</h2>
                <p class="text-sm mt-1">No hay platillos en esta mesa.</p>
            </div>
        <?php else: ?>
            
            <?php if($modo == 'dividir'): ?>
                <form action="<?= base_url('capitan/ejecutar_division') ?>" method="POST">
                <input type="hidden" name="id_mesa" value="<?= $mesa['id_mesa'] ?>">
            <?php endif; ?>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-6">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 uppercase tracking-widest font-black">
                            <?php if($modo == 'dividir'): ?>
                                <th class="p-4 text-center w-10"><i class="fa-solid fa-check-double"></i></th>
                            <?php endif; ?>
                            <th class="p-4">Cant.</th>
                            <th class="p-4">Platillo</th>
                            <th class="p-4">Precio</th>
                            <?php if($modo == 'cancelar'): ?>
                                <th class="p-4 text-center">Acción</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="text-[#0A1F3D]">
                        <?php foreach($detalles as $d): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50 transition cursor-pointer">
                                
                                <?php if($modo == 'dividir'): ?>
                                    <td class="p-4 text-center">
                                        <input type="checkbox" name="items[]" value="<?= $d['id_detalle_comanda'] ?>" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer">
                                    </td>
                                <?php endif; ?>

                                <td class="p-4 font-black text-lg"><?= $d['cantidad'] ?>x</td>
                                <td class="p-4">
                                    <div class="font-bold"><?= $d['platillo'] ?></div>
                                    <?php if(!empty($d['comentarios'])): ?>
                                        <div class="text-xs text-gray-400 mt-1"><i class="fa-regular fa-comment"></i> <?= $d['comentarios'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-medium">$<?= number_format($d['precio_unitario'], 2) ?></td>
                                
                                <?php if($modo == 'cancelar'): ?>
                                    <td class="p-4 text-center">
                                        <button type="button" onclick="abrirCancelacion(<?= $d['id_detalle_comanda'] ?>, '<?= $d['platillo'] ?>', <?= $d['cantidad'] ?>)" 
                                                class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white px-4 py-2 rounded-xl text-sm font-bold transition shadow-sm">
                                            <i class="fa-solid fa-trash-can mr-1"></i> Borrar
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if($modo == 'dividir'): ?>
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-center justify-between shadow-inner mt-4">
                    <div>
                        <h4 class="text-blue-800 font-bold text-lg"><i class="fa-solid fa-code-branch mr-2"></i>Separar en Nueva Cuenta</h4>
                        <p class="text-sm text-blue-600 mt-1">Los platillos seleccionados se moverán a la mesa virtual:</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-2xl font-black text-blue-900">Mesa <?= $mesa['numero_mesa'] ?> -</span>
                        <input type="text" name="sufijo" required maxlength="2" placeholder="Ej: B" class="w-16 text-center border-2 border-blue-200 rounded-xl p-3 font-black text-blue-900 uppercase text-xl outline-none focus:border-blue-500">
                        
                        <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-6 rounded-xl transition shadow-md flex items-center gap-2 uppercase tracking-wide">
                            Generar Cuenta <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                </form>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <!-- MODAL DE CANCELACIÓN (Solo se usa si está en modo cancelar) -->
    <?php if($modo == 'cancelar'): ?>
    <dialog id="modalCancelar" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-0 backdrop:bg-[#0A1F3D]/80">
        <div class="bg-red-600 text-white p-5 flex justify-between items-center rounded-t-2xl">
            <h3 class="font-bold flex items-center gap-3 text-lg"><i class="fa-solid fa-triangle-exclamation"></i> Cancelar Platillo</h3>
            <button onclick="document.getElementById('modalCancelar').close()" class="text-white/70 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <form action="<?= base_url('capitan/cancelar_item') ?>" method="POST" class="p-6">
            <input type="hidden" name="id_mesa" value="<?= $mesa['id_mesa'] ?>">
            <input type="hidden" name="id_detalle" id="input_cancelar_id">
            
            <p class="text-sm text-gray-500 mb-4">¿Cuántas unidades de <strong id="lbl_nombre_platillo" class="text-[#0A1F3D]"></strong> deseas eliminar de la cuenta?</p>
            
            <div class="flex gap-4 mb-4">
                <div class="flex-1">
                    <label class="text-[10px] font-black text-gray-400 tracking-widest uppercase mb-2 block">Cantidad a borrar</label>
                    <input type="number" id="input_cantidad" name="cantidad" min="1" max="1" value="1" class="w-full border-2 border-gray-200 rounded-xl p-3 font-black text-[#0A1F3D] text-center outline-none focus:border-red-500 transition" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="text-[10px] font-black text-gray-400 tracking-widest uppercase mb-2 block">Motivo obligatorio</label>
                <textarea name="motivo" rows="2" placeholder="Ej. El cliente ya no lo quiere..." class="w-full border-2 border-gray-200 rounded-xl p-3 font-medium text-[#0A1F3D] text-sm outline-none focus:border-red-500 transition resize-none" required></textarea>
            </div>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-4 rounded-xl transition shadow-lg uppercase tracking-widest text-sm">
                Confirmar Cancelación
            </button>
        </form>
    </dialog>

    <script>
        function abrirCancelacion(id_detalle, platillo, max_cantidad) {
            document.getElementById('input_cancelar_id').value = id_detalle;
            document.getElementById('lbl_nombre_platillo').innerText = platillo;
            
            let inputCantidad = document.getElementById('input_cantidad');
            inputCantidad.max = max_cantidad;
            inputCantidad.value = max_cantidad; // Por defecto sugiere borrar todo
            
            document.getElementById('modalCancelar').showModal();
        }
    </script>
    <?php endif; ?>

</body>
</html>