<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comanda - Mesa <?= $mesa['numero_mesa'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-[#F0F4F8] h-screen flex justify-center items-center font-sans p-4 overflow-hidden">

    <div class="bg-white w-full max-w-xl rounded-3xl shadow-2xl overflow-hidden flex flex-col h-[90vh] border border-gray-100">
        
        <?php 
            $esPorPagar = ($mesa['estado_mesa'] == 'Por Pagar');
            $headerColor = $esPorPagar ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-gray-900' : 'bg-[#0A1F3D] text-white';
        ?>
        <header class="<?= $headerColor ?> p-6 flex justify-between items-center shrink-0 shadow-md">
            <a href="<?= base_url('pos') ?>" class="bg-black/10 hover:bg-black/20 p-2 px-4 rounded-xl font-bold transition flex items-center gap-2 text-sm">
                <i class="fa-solid fa-chevron-left"></i> Ver Mesas
            </a>
            <div class="text-right">
                <h1 class="text-2xl font-black tracking-tighter uppercase">Mesa <?= $mesa['numero_mesa'] ?></h1>
                <span class="text-[10px] font-black uppercase tracking-widest bg-black/10 px-3 py-1 rounded-full inline-block mt-1">
                    <?= $mesa['estado_mesa'] ?>
                </span>
            </div>
        </header>

        <?php if ($esPorPagar): ?>
            <div class="bg-amber-50 border-b border-amber-200 p-3 px-6 text-xs text-amber-800 font-bold flex items-center gap-2 shrink-0">
                <i class="fa-solid fa-lock text-amber-600"></i> Cuenta impresa por unica vez. Mesa bloqueada para el mesero hasta confirmacion de pago en caja.
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-red-500 text-white p-3 font-bold text-center text-xs uppercase tracking-wider shrink-0">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="flex-1 overflow-y-auto p-6 bg-gray-50/50 space-y-3">
            <h3 class="text-[10px] font-black text-gray-400 tracking-widest uppercase mb-4">Detalle de Consumo de la Comanda:</h3>
            
            <?php if(empty($detalles)): ?>
                <div class="text-center text-gray-400 py-12">
                    <i class="fa-solid fa-basket-shopping text-4xl text-gray-200 mb-2"></i>
                    <p class="text-sm font-medium">No hay platillos en la orden.</p>
                </div>
            <?php else: ?>
                <?php foreach($detalles as $d): ?>
                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex justify-between items-center">
                        <div>
                            <div class="font-bold text-[#0A1F3D] text-sm"><?= $d['nombre_platillo'] ?></div>
                            <div class="text-xs text-gray-400 mt-1">
                                Cantidad: <span class="font-black text-gray-700"><?= $d['cantidad'] ?></span>
                                <?php if($d['comentarios']): ?>
                                    <span class="text-orange-600 bg-orange-50 px-2 py-0.5 rounded ml-2 font-medium">💬 <?= $d['comentarios'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="font-black text-[#1565C0] text-base">
                            $<?= number_format($d['cantidad'] * $d['precio_unitario'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="p-6 bg-white border-t border-gray-100 shrink-0 shadow-2xl">
            <div class="flex justify-between items-end mb-6">
                <span class="text-gray-400 font-black text-xs uppercase tracking-widest">Subtotal Acumulado</span>
                <span class="text-4xl font-black text-[#0A1F3D] tracking-tight">$<?= number_format($total, 2) ?></span>
            </div>

            <div class="flex gap-4">
                <?php if ($esPorPagar): ?>
                    <button disabled class="flex-1 bg-gray-100 text-gray-400 font-black py-4 rounded-xl flex items-center justify-center gap-2 cursor-not-allowed uppercase tracking-widest text-xs border border-gray-200">
                        <i class="fa-solid fa-ban"></i> Pedidos Cerrados
                    </button>
                <?php else: ?>
                    <a href="<?= base_url('pos/mesa/'.$mesa['id_mesa']) ?>" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-black py-4 rounded-xl flex items-center justify-center gap-2 transition uppercase tracking-widest text-xs shadow-lg shadow-green-500/20">
                        <i class="fa-solid fa-plus-circle"></i> Agregar Alimentos
                    </a>
                <?php endif; ?>

                <?php if ($esPorPagar): ?>
                    <button disabled class="flex-1 bg-gray-200 text-gray-400 font-black py-4 rounded-xl flex items-center justify-center gap-2 cursor-not-allowed uppercase tracking-widest text-xs border border-gray-300">
                        <i class="fa-solid fa-circle-check text-gray-400"></i> Cuenta ya Impresa
                    </button>
                <?php else: ?>
                    <a href="<?= base_url('pos/imprimir_cuenta/'.$mesa['id_mesa']) ?>" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-xl flex items-center justify-center gap-2 transition uppercase tracking-widest text-xs shadow-lg shadow-blue-500/20">
    <i class="fa-solid fa-print"></i> Imprimir Ticket
</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>