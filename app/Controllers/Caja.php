<?php
    namespace App\Controllers;

    use App\Models\MesaModel;
    use App\Models\ComandaModel;

    class Caja extends BaseController
    {
        protected $db, $mesaModel, $comandaModel;

        // Se inyectan las dependencias aquí para facilitar los Mocks en Unit Testing
        public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
        {
            parent::initController($request, $response, $logger);
            $this->db = \Config\Database::connect();
            $this->mesaModel = new MesaModel();
            $this->comandaModel = new ComandaModel();
        }

        // =======================================================
        // 1. PANTALLA PRINCIPAL DE CAJA
        // =======================================================
        public function index($id_mesa_sel = null)
        {
            if (!session()->get('isLoggedIn')) return redirect()->to('/');

            $data = ['mesas' => [], 'mesa_activa' => null, 'corte' => $this->obtenerVentasDelDia()];

            $mesasRaw = $this->db->table('Mesa m')->select('m.*, u.nombre_completo as mesero')
                ->join('Usuario u', 'u.id_usuario = m.id_usuario_mesero', 'left')
                ->where(['m.activa' => 1, 'm.estado_mesa' => 'Por Pagar'])->get()->getResultArray();

            foreach ($mesasRaw as $m) {
                $m = array_merge($m, $this->obtenerTotalesMesa($m['id_mesa']));
                $data['mesas'][] = $m;
                if ($id_mesa_sel == $m['id_mesa']) $data['mesa_activa'] = $m;
            }

            return view('caja/index', $data);
        }

        // =======================================================
        // 2. ACCIÓN PARA COBRAR (LIQUIDAR)
        // =======================================================
        public function liquidar()
        {
            if (!session()->get('isLoggedIn')) return redirect()->to('/');

            $post = $this->request->getPost();
            $comanda = $this->comandaModel->where('id_mesa', $post['id_mesa'])->orderBy('id_comanda', 'DESC')->first();
            
            if (!$comanda) return redirect()->back()->with('error', 'Comanda no encontrada.');

            $consumo = $this->obtenerTotalesMesa($post['id_mesa'])['total'];
            $propina_total = ((float)$post['monto_efectivo'] + (float)$post['monto_tarjeta']) - $consumo;

            $this->db->transStart();

            if ((float)$post['monto_efectivo'] > 0) {
                $propina = ($post['metodo_pago'] === 'efectivo') ? $propina_total : 0;
                $this->registrarPago($comanda['id_comanda'], (float)$post['monto_efectivo'], $propina, 1);
            }

            if ((float)$post['monto_tarjeta'] > 0) {
                $propina = in_array($post['metodo_pago'], ['tarjeta', 'mixto']) ? $propina_total : 0;
                $this->registrarPago($comanda['id_comanda'], (float)$post['monto_tarjeta'], $propina, 2);
            }

            $this->mesaModel->update($post['id_mesa'], ['estado_mesa' => 'Libre']);
            $this->db->transComplete();

            return $this->db->transStatus() 
                ? redirect()->to('/caja')->with('success', 'Cuenta pagada exitosamente.')
                : redirect()->back()->with('error', 'Error al procesar el pago.');
        }

        // =======================================================
        // 3. ACCIÓN PARA EL CORTE DE CAJA
        // =======================================================
        public function corte_caja()
        {
            if (!session()->get('isLoggedIn')) return redirect()->to('/');
            
            if ($this->mesaModel->whereIn('estado_mesa', ['Ocupada', 'Por Pagar'])->countAllResults() > 0) {
                return redirect()->to('/caja')->with('error', '⚠️ Todavía hay mesas consumiendo o por pagar.');
            }

            return redirect()->to('/caja')->with('success', '✅ Corte de Caja realizado correctamente.');
        }

        // =======================================================
        // MÉTODOS PRIVADOS (COMPACTOS PARA TESTING)
        // =======================================================

        private function obtenerTotalesMesa($id_mesa): array
        {
            $comanda = $this->comandaModel->where('id_mesa', $id_mesa)->orderBy('id_comanda', 'DESC')->first();
            if (!$comanda) return ['total' => 0, 'items' => 0, 'id_comanda' => null];

            $totales = $this->db->query("SELECT SUM(cantidad * precio_unitario) as total, SUM(cantidad) as items FROM Detalle_Comanda WHERE id_comanda = ?", [$comanda['id_comanda']])->getRowArray();
            
            return ['total' => $totales['total'] ?? 0, 'items' => $totales['items'] ?? 0, 'id_comanda' => $comanda['id_comanda']];
        }

        private function obtenerVentasDelDia(): array
        {
            $pagos = $this->db->table('Cuenta_Pago cp')
                ->select('mp.nombre as metodo, SUM(cp.total) as suma_total, SUM(cp.propina) as suma_propinas')
                ->join('Metodo_Pago mp', 'mp.id_metodo = cp.id_metodo', 'left')
                ->where('DATE(cp.fecha_hora_pago)', date('Y-m-d'))
                ->groupBy('mp.id_metodo, mp.nombre')->get()->getResultArray();

            $corte = ['efectivo' => 0, 'tarjeta' => 0, 'propinas' => 0, 'descuentos' => 0, 'venta_neta' => 0];
            
            foreach ($pagos as $p) {
                $metodo = strtolower($p['metodo']);
                if (isset($corte[$metodo])) $corte[$metodo] = $p['suma_total'];
                $corte['propinas'] += $p['suma_propinas'];
            }
            
            $corte['venta_neta'] = $corte['efectivo'] + $corte['tarjeta'];
            return $corte;
        }

        private function registrarPago($id_comanda, $total, $propina, $id_metodo)
        {
            $this->db->table('Cuenta_Pago')->insert([
                'id_comanda' => $id_comanda, 'id_usuario' => session()->get('id_usuario'),
                'subtotal' => $total / 1.16, 'iva' => $total - ($total / 1.16),
                'propina' => $propina, 'total' => $total, 'id_metodo' => $id_metodo,
                'fecha_hora_pago' => date('Y-m-d H:i:s')
            ]);
        }
    }