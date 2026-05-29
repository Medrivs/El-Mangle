<dialog id="modalCorteCaja" class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-0 backdrop:bg-[#0A1F3D]/80">
    <div class="bg-[#0A1F3D] text-white p-5 flex justify-between items-center rounded-t-2xl">
        <h3 class="font-bold flex items-center gap-3 text-lg"><i class="fa-solid fa-wallet text-[#00B4D8]"></i> Corte de Caja</h3>
        <button onclick="document.getElementById('modalCorteCaja').close()" class="text-gray-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
    </div>
    
    <div class="p-6">
        <div class="flex gap-4 mb-6">
            <div class="flex-1 border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                <h4 class="text-[10px] font-black text-[#8BA3BA] tracking-widest uppercase mb-3">Ventas Totales</h4>
                <div class="flex justify-between text-sm text-gray-500 mb-1">
                    <span>Efectivo</span> 
                    <span class="font-medium">$<?= isset($corte['efectivo']) ? number_format($corte['efectivo'], 2) : '0.00' ?></span>
                </div>
                <div class="flex justify-between text-sm text-gray-500">
                    <span>Tarjeta</span> 
                    <span class="font-bold text-[#0A1F3D]">$<?= isset($corte['tarjeta']) ? number_format($corte['tarjeta'], 2) : '0.00' ?></span>
                </div>
            </div>
            <div class="flex-1 border border-gray-200 rounded-xl p-4 bg-white shadow-sm flex flex-col justify-between gap-3">
                <div>
                    <h4 class="text-[10px] font-black text-[#8BA3BA] tracking-widest uppercase mb-1">Total Propinas</h4>
                    <div class="text-sm font-bold text-[#0A1F3D]">$<?= isset($corte['propinas']) ? number_format($corte['propinas'], 2) : '0.00' ?></div>
                </div>
                <div class="border-t border-gray-100 pt-2">
                    <h4 class="text-[10px] font-black text-[#8BA3BA] tracking-widest uppercase mb-1">Descuentos / Cancel.</h4>
                    <div class="text-sm font-bold text-red-500">-$<?= isset($corte['descuentos']) ? number_format($corte['descuentos'], 2) : '0.00' ?></div>
                </div>
            </div>
        </div>

        <div class="text-center mb-6">
            <h4 class="text-[11px] font-black text-[#8BA3BA] tracking-widest uppercase mb-1">Venta Neta Final</h4>
            <div class="text-[40px] font-black text-[#0A1F3D] leading-none">$<?= isset($corte['venta_neta']) ? number_format($corte['venta_neta'], 2) : '0.00' ?></div>
        </div>

        <div class="bg-[#F8FAFC] border border-gray-200 rounded-xl p-5 mb-6 shadow-inner">
            <h4 class="text-[10px] font-black text-[#64748B] tracking-widest uppercase mb-4 flex items-center gap-2">
                <i class="fa-solid fa-calculator"></i> Auditoría de Caja
            </h4>
            
            <div class="flex justify-between items-center mb-3">
                <label class="text-sm font-bold text-[#334155]">Fondo Inicial ($)</label>
                <input type="number" id="fondo_inicial" value="0.00" class="w-32 border border-gray-300 rounded-lg py-1 px-3 text-right text-sm text-gray-600 font-medium focus:outline-none focus:border-[#0A1F3D] focus:ring-1 focus:ring-[#0A1F3D] transition" oninput="calcularAuditoria()">
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <label class="text-sm font-bold text-[#334155]">Efectivo Físico ($)</label>
                <input type="number" id="efectivo_fisico" value="0.00" class="w-32 border border-gray-300 rounded-lg py-1 px-3 text-right text-sm text-gray-600 font-medium focus:outline-none focus:border-[#0A1F3D] focus:ring-1 focus:ring-[#0A1F3D] transition" oninput="calcularAuditoria()">
            </div>
            
            <div class="flex justify-between items-center border-t border-gray-200 pt-4">
                <label class="text-base font-black text-[#0A1F3D]">Diferencia:</label>
                <span id="lbl_diferencia" class="text-2xl font-black text-[#00A97F]">0.00</span>
            </div>
        </div>

        <form action="<?= base_url('caja/corte_caja') ?>" method="POST">
            <input type="hidden" name="fondo_inicial" id="input_fondo" value="0">
            <input type="hidden" name="efectivo_fisico" id="input_fisico" value="0">
            <input type="hidden" name="diferencia" id="input_dif" value="0">
            <button type="submit" class="w-full bg-[#0A1F3D] hover:bg-[#1565C0] text-white font-bold py-4 rounded-xl transition shadow-lg uppercase tracking-widest flex justify-center items-center gap-3 text-sm">
                <i class="fa-solid fa-lock text-[#00B4D8]"></i> Confirmar Cierre
            </button>
        </form>
    </div>
</dialog>