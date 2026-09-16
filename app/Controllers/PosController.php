<?php
class PosController {
    private ProductModel  $products;
    private SaleModel     $sales;
    private LocationModel $locations;
    private CustomerModel $customers;

    public function __construct() {
        $this->products  = new ProductModel();
        $this->sales     = new SaleModel();
        $this->locations = new LocationModel();
        $this->customers = new CustomerModel();
    }

    public function index(): void {
        $locations = $this->locations->all();
        $categories = (new CategoryModel())->all();
        view('pos/index', compact('locations', 'categories'));
    }

    public function processSale(): void {
        verifyCsrf();
        $wantsJson = (
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || (($_POST['ajax'] ?? '') === '1')
        );

        $cartJson  = $_POST['cart_json'] ?? '[]';
        $cart      = json_decode($cartJson, true);

        if (empty($cart)) {
            if ($wantsJson) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Cart is empty.']);
                exit;
            }
            flash('error', 'Cart is empty.');
            redirect('/pos');
        }

        // Build items and validate stock
        $items    = [];
        $subtotal = 0;
        foreach ($cart as $entry) {
            $product = $this->products->findById((int)$entry['product_id']);
            if (!$product) continue;
            if ($product['quantity'] < (int)$entry['qty']) {
                $msg = "Insufficient stock for: {$product['name']} Size {$product['size']}";
                if ($wantsJson) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'error' => $msg]);
                    exit;
                }
                flash('error', $msg);
                redirect('/pos');
            }
            $lineTotal = $product['selling_price'] * (int)$entry['qty'];
            $subtotal += $lineTotal;
            $items[] = [
                'product_id'  => $product['id'],
                'quantity'    => (int)$entry['qty'],
                'unit_price'  => $product['selling_price'],
                'cost_price'  => $product['cost_price'],
                'line_total'  => $lineTotal,
            ];
        }

        $discount  = (float)($_POST['discount'] ?? 0);
        $total     = max(0, $subtotal - $discount);
        $payMethod = $_POST['payment_method'] ?? 'cash';
        $tendered  = (float)($_POST['amount_tendered'] ?? $total);
        $change    = $payMethod === 'cash' ? max(0, $tendered - $total) : 0;

        // Optional: create or find customer
        $customerId = null;
        $custName   = trim($_POST['customer_name'] ?? '');
        $custPhone  = trim($_POST['customer_phone'] ?? '');
        if ($custName || $custPhone) {
            $existing = $custPhone ? $this->customers->findByPhone($custPhone) : null;
            if ($existing) {
                $customerId = $existing['id'];
            } else {
                $customerId = $this->customers->create([
                    'name'      => $custName,
                    'phone'     => $custPhone,
                    'shoe_size' => trim($_POST['customer_size'] ?? ''),
                ]);
            }
        }

        $saleId = $this->sales->create([
            'sale_ref'        => $this->sales->generateRef(),
            'staff_id'        => $_SESSION['user_id'],
            'location_id'     => (int)($_POST['location_id'] ?? 1),
            'customer_id'     => $customerId,
            'subtotal'        => $subtotal,
            'discount'        => $discount,
            'total'           => $total,
            'payment_method'  => $payMethod,
            'amount_tendered' => $tendered,
            'change_due'      => $change,
            'momo_ref'        => trim($_POST['momo_ref'] ?? '') ?: null,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ], $items);

        if ($wantsJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'          => true,
                'sale_id'     => $saleId,
                'receipt_url' => BASE_PATH . '/pos/receipt/' . $saleId . '?embed=1',
            ]);
            exit;
        }

        redirect('/pos/receipt/' . $saleId);
    }

    public function receipt(string $id): void {
        $sale  = $this->sales->findById((int)$id);
        if (!$sale) redirect('/pos');
        $items = $this->sales->getItems((int)$id);
        $embed = isset($_GET['embed']);
        view('pos/receipt', compact('sale', 'items', 'embed'));
    }

    // AJAX: paginated POS product list (q, page, per_page, category_id, gender)
    public function searchProduct(): void {
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($_GET['per_page'] ?? 10)));
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (string) $_GET['category_id'] : null;
        $gender = isset($_GET['gender']) && $_GET['gender'] !== '' ? (string) $_GET['gender'] : null;

        $bundle = $this->products->forPosBrowse(
            $q !== '' ? $q : null,
            $page,
            $perPage,
            $categoryId,
            $gender
        );
        echo json_encode([
            'items'      => $bundle['items'],
            'total'      => $bundle['total'],
            'page'       => $bundle['page'],
            'perPage'    => $bundle['perPage'],
            'totalPages' => $bundle['totalPages'],
        ]);
        exit;
    }

    // AJAX: find by SKU or barcode (may return multiple sizes sharing one SKU)
    public function byBarcode(string $bc): void {
        header('Content-Type: application/json');
        $products = $this->products->findAllByCode($bc);
        if (!$products) {
            echo json_encode(['found' => false]);
            exit;
        }
        $map = static function (array $p): array {
            return [
                'id'            => (int)$p['id'],
                'name'          => $p['name'],
                'design'        => $p['design'],
                'size'          => $p['size'],
                'sku'           => $p['sku'] ?? null,
                'selling_price' => (float)$p['selling_price'],
                'cost_price'    => (float)$p['cost_price'],
                'quantity'      => (int)$p['quantity'],
                'category_name' => $p['category_name'],
                'gender'        => $p['gender'],
                'barcode'       => $p['barcode'],
            ];
        };
        $variants = array_map($map, $products);
        echo json_encode([
            'found'    => true,
            'count'    => count($variants),
            'product'  => $variants[0],
            'variants' => $variants,
        ]);
        exit;
    }
}