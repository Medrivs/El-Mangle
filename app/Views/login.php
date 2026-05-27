<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El Mangle - Login POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen w-screen flex flex-col font-sans overflow-hidden bg-[#0A192F]">

    <div class="flex-1 flex flex-col items-center justify-center p-6 bg-[#0A192F] text-white">
        
        <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center mb-4 shadow-lg overflow-hidden">
            <img src="<?= base_url('img/logo.png') ?>" alt="El Mangle Logo" class="w-full h-full object-contain p-2">
        </div>
        
        <h1 class="text-3xl font-bold tracking-widest uppercase">El Mangle</h1>
        <h2 class="text-[#00B4D8] text-sm font-semibold tracking-widest uppercase mb-10">Gastronomía Marina</h2>

        <form id="loginForm" action="<?= base_url('login/ingresar') ?>" method="post" class="flex flex-col items-center w-full max-w-sm">
            <?= csrf_field() ?>
            
            <p class="text-gray-300 text-sm tracking-widest mb-4">INGRESE PIN</p>
            
            <input type="hidden" name="pin" id="pinInput" value="">

            <div class="flex space-x-6 bg-white rounded-xl px-12 py-4 mb-6 shadow-lg">
                <div class="w-3 h-3 rounded-full border-2 border-[#0A192F] pin-dot transition-colors"></div>
                <div class="w-3 h-3 rounded-full border-2 border-[#0A192F] pin-dot transition-colors"></div>
                <div class="w-3 h-3 rounded-full border-2 border-[#0A192F] pin-dot transition-colors"></div>
                <div class="w-3 h-3 rounded-full border-2 border-[#0A192F] pin-dot transition-colors"></div>
            </div>

            <?php if(session()->getFlashdata('error')): ?>
                <div class="bg-red-500/20 text-red-200 px-4 py-2 rounded mb-4 text-sm text-center w-full">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="flex-1 bg-[#F0F4F8] flex items-center justify-center p-6">
        <div class="grid grid-cols-3 gap-6 max-w-sm w-full">
            <?php for($i=1; $i<=9; $i++): ?>
                <button type="button" onclick="agregarNumero(<?= $i ?>)" class="bg-white text-3xl font-bold text-[#0A192F] h-20 rounded-2xl shadow hover:bg-gray-100 active:bg-gray-200 transition-colors flex items-center justify-center">
                    <?= $i ?>
                </button>
            <?php endfor; ?>
            
            <button type="button" onclick="borrarNumero()" class="bg-white text-[#0A192F] h-20 rounded-2xl shadow hover:bg-gray-100 active:bg-gray-200 transition-colors flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z" /></svg>
            </button>
            
            <button type="button" onclick="agregarNumero(0)" class="bg-white text-3xl font-bold text-[#0A192F] h-20 rounded-2xl shadow hover:bg-gray-100 active:bg-gray-200 transition-colors flex items-center justify-center">
                0
            </button>
            
            <button type="button" onclick="document.getElementById('loginForm').submit();" class="bg-[#008080] text-white h-20 rounded-2xl shadow hover:bg-[#006666] active:bg-[#004d4d] transition-colors flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
            </button>
        </div>
    </div>

    <script>
        let pinActual = "";
        const maxDigitos = 4;
        const inputOculto = document.getElementById('pinInput');
        const puntosVisuales = document.querySelectorAll('.pin-dot');

        function agregarNumero(num) {
            if (pinActual.length < maxDigitos) {
                pinActual += num;
                actualizarVisual();
            }
        }

        function borrarNumero() {
            if (pinActual.length > 0) {
                pinActual = pinActual.slice(0, -1);
                actualizarVisual();
            }
        }

        function actualizarVisual() {
            inputOculto.value = pinActual;
            
            puntosVisuales.forEach((punto, index) => {
                if (index < pinActual.length) {
                    punto.classList.add('bg-[#0A192F]'); 
                } else {
                    punto.classList.remove('bg-[#0A192F]'); 
                }
            });

            // Si llega a 4 dígitos, enviamos el formulario automáticamente
            if (pinActual.length === maxDigitos) {
                setTimeout(() => {
                    document.getElementById('loginForm').submit();
                }, 200);
            }
        }
    </script>
</body>
</html>