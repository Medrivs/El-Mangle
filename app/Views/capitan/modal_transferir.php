<!-- app/Views/capitan/modal_transferir.php -->
<dialog id="modalTransferir" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-0 backdrop:bg-[#0A1F3D]/80">
    <div class="bg-purple-600 text-white p-5 flex justify-between items-center rounded-t-2xl">
        <h3 class="font-bold flex items-center gap-3 text-lg"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transferir Mesa</h3>
        <button onclick="document.getElementById('modalTransferir').close()" class="text-white/70 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <form action="<?= base_url('capitan/transferir') ?>" method="POST" class="p-6">
        <input type="hidden" id="input_transferir_origen" name="id_mesa_origen">
        
        <p class="text-sm text-gray-500 mb-4">Selecciona una mesa disponible a la cual deseas mover la orden actual:</p>
        
        <div class="mb-6">
            <label class="text-xs font-black text-gray-400 tracking-widest uppercase mb-2 block">Mesa Destino</label>
            <select name="id_mesa_destino" class="w-full border-2 border-gray-200 rounded-xl p-3 font-bold text-[#0A1F3D] outline-none focus:border-purple-500 transition cursor-pointer" required>
                <option value="" disabled selected>-- Elige una mesa libre --</option>
                <?php if(isset($mesas_libres)): ?>
                    <?php foreach($mesas_libres as $libre): ?>
                        <option value="<?= $libre['id_mesa'] ?>">Mesa <?= $libre['numero_mesa'] ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl transition shadow-lg uppercase tracking-widest text-sm">
            Confirmar Traslado
        </button>
    </form>
</dialog>