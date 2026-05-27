<aside class="w-[380px] bg-white border-l border-blue-200 flex flex-col shadow-2xl shrink-0 h-full">
    <div class="bg-[#0A1F3D] text-white p-4 font-bold text-center shrink-0">🛒 Orden de Mesa <?= $mesa['numero_mesa'] ?></div>
    
    <div id="carrito-items" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50/50">
        <div class="text-center mt-10">
            <i class="fa-solid fa-cart-shopping text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-400 font-medium">Carrito vacio</p>
        </div>
    </div>

    <div class="p-5 border-t border-gray-200 bg-white shrink-0">
        <div class="flex justify-between font-black text-xl mb-4 text-[#0A1F3D]">
            <span>Total:</span> <span id="total-txt">$0.00</span>
        </div>
        
        <form id="form-orden" action="<?= base_url('pos/enviar_orden') ?>" method="POST">
            <input type="hidden" name="id_mesa" value="<?= $mesa['id_mesa'] ?>">
            <input type="hidden" name="datos_carrito" id="datos_carrito">
            <button type="submit" id="btn-enviar" disabled class="w-full bg-[#1565C0] text-white font-black py-4 rounded-xl disabled:bg-gray-300 transition-colors uppercase tracking-widest text-sm">
                Enviar a Cocina
            </button>
        </form>
    </div>
</aside>