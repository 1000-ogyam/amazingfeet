<?php
// ════════════════════════════════════════════════════════════
//  DashboardController
// ════════════════════════════════════════════════════════════
class DashboardController {
    public function index(): void {
        $sm      = new SaleModel();
        $pm      = new ProductModel();
        $tm      = new TargetModel();
        $today   = $sm->todaySummary();
        $week    = $sm->weekSummary();
        $targets = $tm->getActualVsTarget();
        $topSizes   = $pm->topSellers('week');
        $lowStock   = $pm->all(['low_stock' => true]);
        $stockValue = $pm->getStockValue();
        $recentSales = $sm->all(['week' => true]);
        $chartData  = $sm->dailyRevenueLast30();
        view('owner/dashboard', compact(
            'today','week','targets','topSizes','lowStock','stockValue','recentSales','chartData'
        ));
    }
}

// ════════════════════════════════════════════════════════════
//  ProductController
// ════════════════════════════════════════════════════════════
class ProductController {
    private ProductModel  $pm;
    private CategoryModel $cm;
    public function __construct() { $this->pm = new ProductModel(); $this->cm = new CategoryModel(); }

    public function index(): void {
        $filters = array_filter($_GET, fn($v) => $v !== '' && $v !== null);
        unset($filters['page'], $filters['per_page'], $filters['csrf']);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(5, (int)($_GET['per_page'] ?? 10)));
        try {
            $bundle = $this->pm->allStyles($filters, $page, $perPage);
        } catch (Throwable $e) {
            flash('error', 'Could not load products. Please refresh or contact support.');
            $bundle = [
                'items' => [], 'total' => 0, 'page' => 1, 'perPage' => $perPage,
                'totalPages' => 1, 'variantTotal' => 0,
            ];
        }
        $products = $bundle['items'];
        $pagination = [
            'page'       => $bundle['page'],
            'perPage'    => $bundle['perPage'],
            'total'      => $bundle['total'],
            'totalPages' => $bundle['totalPages'],
        ];
        $categories = $this->cm->all();
        $stockValue = $this->pm->getStockValue();
        $styleCount = $bundle['total'];
        $variantCount = $bundle['variantTotal'];
        view('owner/products', compact(
            'products','categories','filters','stockValue','variantCount','styleCount','pagination'
        ));
    }

    public function create(): void {
        $categories = $this->cm->all();
        $styleBundle = $this->pm->allStyles([], 1, 200);
        $styleOptions = $styleBundle['items'] ?? [];
        view('owner/product_form', [
            'categories' => $categories,
            'product' => null,
            'variants' => [],
            'styleOptions' => $styleOptions,
            'error' => flash('error'),
        ]);
    }

    public function store(): void {
        verifyCsrf();
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = $this->handleUpload();
            if (!$image) {
                flash('error', 'Could not upload image. Use JPG, PNG, or WebP under 8MB.');
                redirect('/products/create');
            }
        }

        $sizes = $this->parseSizeRows($_POST['sizes'] ?? []);
        if (!$sizes) {
            flash('error', 'Add at least one size with a selling price.');
            redirect('/products/create');
        }

        try {
            $this->pm->createWithSizes([
                'category_id'         => (int)$_POST['category_id'],
                'name'                => trim($_POST['name'] ?? ''),
                'gender'              => $_POST['gender'] ?? 'Unisex',
                'design'              => trim($_POST['design'] ?? '') ?: null,
                'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? LOW_STOCK_THRESHOLD),
                'image'               => $image,
            ], $sizes);
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not create product.');
            redirect('/products/create');
        }

        flash('success', count($sizes).' size'.(count($sizes)===1?'':'s').' added.');
        redirect('/products');
    }

    public function edit(string $id): void {
        $product = $this->pm->findById((int)$id);
        if (!$product) redirect('/products');
        $categories = $this->cm->all();
        $variants = $this->pm->siblings((int)$id, true);
        view('owner/product_form', compact('product','categories','variants') + ['error'=>flash('error')]);
    }

    public function update(string $id): void {
        verifyCsrf();
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = $this->handleUpload();
            if (!$image) {
                flash('error', 'Could not upload image. Use JPG, PNG, or WebP under 8MB.');
                redirect('/products/'.$id.'/edit');
            }
        }
        $d = [
            'category_id'         => (int)$_POST['category_id'],
            'name'                => trim($_POST['name'] ?? ''),
            'gender'              => $_POST['gender'] ?? 'Unisex',
            'design'              => trim($_POST['design'] ?? '') ?: null,
            'size'                => trim($_POST['size'] ?? ''),
            'sku'                 => trim($_POST['sku'] ?? '') ?: null,
            'barcode'             => trim($_POST['barcode'] ?? '') ?: null,
            'cost_price'          => (float)($_POST['cost_price'] ?? 0),
            'selling_price'       => (float)($_POST['selling_price'] ?? 0),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? LOW_STOCK_THRESHOLD),
            'image'               => $image,
        ];
        if ($d['size'] === '') {
            flash('error', 'Size is required.');
            redirect('/products/'.$id.'/edit');
        }
        $this->pm->update((int)$id, $d);
        flash('success','Product updated.');
        redirect('/products/'.$id.'/edit');
    }

    /** Apply cost and/or sell price to all sizes of this style. */
    public function applyPrices(string $id): void {
        verifyCsrf();
        $costRaw = trim((string)($_POST['cost_price'] ?? ''));
        $sellRaw = trim((string)($_POST['selling_price'] ?? ''));
        $cost = $costRaw === '' ? null : (float)$costRaw;
        $sell = $sellRaw === '' ? null : (float)$sellRaw;
        if ($cost === null && $sell === null) {
            flash('error', 'Enter a cost and/or selling price to apply.');
            redirect('/products/'.$id.'/edit');
        }
        $n = $this->pm->applyPricesToFamily((int)$id, $cost, $sell);
        flash('success', $n > 0
            ? "Updated prices on {$n} size".($n === 1 ? '' : 's').'.'
            : 'No sizes updated.');
        redirect('/products/'.$id.'/edit');
    }

    /** Apply the same SKU to every size of this style. */
    public function applySku(string $id): void {
        verifyCsrf();
        $sku = trim((string)($_POST['sku'] ?? ''));
        $appendSize = !empty($_POST['append_size']);
        if ($sku === '') {
            flash('error', 'Enter a SKU to apply to all sizes.');
            redirect('/products/'.$id.'/edit');
        }
        $n = $this->pm->applySkuToFamily((int)$id, $sku, $appendSize);
        flash('success', $n > 0
            ? "Updated SKU on {$n} size".($n === 1 ? '' : 's').'.'
            : 'No sizes updated (SKU column may be missing).');
        redirect('/products/'.$id.'/edit');
    }

    /** Duplicate a product style (all sizes). */
    public function duplicate(string $id): void {
        verifyCsrf();
        try {
            $newId = $this->pm->duplicateStyle((int)$id);
            flash('success', 'Product duplicated. Review sizes and stock, then save any changes.');
            redirect('/products/'.$newId.'/edit');
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not duplicate product.');
            redirect('/products');
        }
    }

    /** JSON: size → cost/sell/sku map for importing into the create form. */
    public function sizePrices(string $id): void {
        header('Content-Type: application/json; charset=utf-8');
        $product = $this->pm->findById((int)$id);
        if (!$product) {
            echo json_encode(['ok' => false, 'sizes' => []]);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'name' => $product['name'],
            'category_id' => (int)$product['category_id'],
            'gender' => $product['gender'],
            'design' => $product['design'] ?? '',
            'sizes' => $this->pm->sizePriceMap((int)$id),
        ]);
        exit;
    }

    /** Bulk apply prices across selected styles (all sizes or one size). */
    public function bulkPrices(): void {
        verifyCsrf();
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), static fn($id) => $id > 0));
        // Modal include list overrides table selection when present
        if (isset($_POST['include_ids']) && is_array($_POST['include_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $_POST['include_ids']), static fn($id) => $id > 0));
        }
        $scope = $_POST['scope'] ?? 'all';
        $size = trim((string)($_POST['size'] ?? ''));
        $mode = ($_POST['price_mode'] ?? 'set') === 'adjust' ? 'adjust' : 'set';
        $onlyEmpty = !empty($_POST['only_empty']);
        $costRaw = trim((string)($_POST['cost_price'] ?? ''));
        $sellRaw = trim((string)($_POST['selling_price'] ?? ''));
        $cost = $costRaw === '' ? null : (float)$costRaw;
        $sell = $sellRaw === '' ? null : (float)$sellRaw;

        $returnQs = http_build_query(array_filter([
            'search' => $_POST['return_search'] ?? '',
            'category_id' => $_POST['return_category_id'] ?? '',
            'gender' => $_POST['return_gender'] ?? '',
            'low_stock' => $_POST['return_low_stock'] ?? '',
            'page' => $_POST['return_page'] ?? '',
            'per_page' => $_POST['return_per_page'] ?? '',
        ], static fn($v) => $v !== '' && $v !== null));

        $back = '/products' . ($returnQs !== '' ? '?'.$returnQs : '');

        if (!$ids) {
            flash('error', 'Select at least one product to update (or un-exclude some).');
            redirect($back);
        }
        if ($cost === null && $sell === null) {
            flash('error', 'Enter a cost and/or selling price to apply.');
            redirect($back);
        }
        if ($scope === 'size' && $size === '') {
            flash('error', 'Enter the size to update (e.g. 32).');
            redirect($back);
        }

        $sizeFilter = $scope === 'size' ? $size : null;
        $r = $this->pm->applyPricesToStyles($ids, $cost, $sell, $sizeFilter, [
            'only_empty' => $onlyEmpty,
            'mode' => $mode,
        ]);

        if ($r['updated'] === 0) {
            $msg = $scope === 'size'
                ? "No size \"{$size}\" found on the included products."
                : 'No prices were updated.';
            if ($onlyEmpty && ($r['skipped'] ?? 0) > 0) {
                $msg = 'Nothing changed — those prices were already set (empty-only mode).';
            }
            flash('error', $msg);
        } else {
            $parts = [];
            $action = $mode === 'adjust' ? 'Adjusted' : 'Updated';
            $parts[] = "{$action} {$r['updated']} size variant".($r['updated']===1?'':'s');
            $parts[] = "across {$r['matched_styles']} product".($r['matched_styles']===1?'':'s');
            if ($scope === 'size') $parts[] = "(size {$size})";
            if ($onlyEmpty) $parts[] = '(empty prices only)';
            if (($r['skipped'] ?? 0) > 0) $parts[] = "— skipped {$r['skipped']} already priced";
            flash('success', implode(' ', $parts).'.');
        }
        redirect($back);
    }

    public function addSize(string $id): void {
        verifyCsrf();
        $size = trim($_POST['size'] ?? '');
        $price = (float)($_POST['selling_price'] ?? 0);
        if ($size === '' || $price <= 0) {
            flash('error', 'Size and selling price are required.');
            redirect('/products/'.$id.'/edit');
        }
        try {
            $newId = $this->pm->addSizeToFamily((int)$id, [
                'size'          => $size,
                'sku'           => trim($_POST['sku'] ?? '') ?: null,
                'barcode'       => trim($_POST['barcode'] ?? '') ?: null,
                'cost_price'    => (float)($_POST['cost_price'] ?? 0),
                'selling_price' => $price,
                'quantity'      => (int)($_POST['quantity'] ?? 0),
            ]);
            flash('success', 'Size '.$size.' added.');
            redirect('/products/'.$newId.'/edit');
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not add size.');
            redirect('/products/'.$id.'/edit');
        }
    }

    /** @return list<array{size:string,cost_price:float,selling_price:float,quantity:int,sku:?string,barcode:?string}> */
    private function parseSizeRows(mixed $raw): array {
        if (!is_array($raw)) return [];
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) continue;
            $size = trim((string)($row['size'] ?? ''));
            if ($size === '') continue;
            $sell = (float)($row['selling_price'] ?? 0);
            if ($sell < 0) continue;
            $out[] = [
                'size'          => $size,
                'cost_price'    => (float)($row['cost_price'] ?? 0),
                'selling_price' => $sell,
                'quantity'      => (int)($row['quantity'] ?? 0),
                'sku'           => trim((string)($row['sku'] ?? '')) ?: null,
                'barcode'       => trim((string)($row['barcode'] ?? '')) ?: null,
            ];
        }
        return $out;
    }

    public function delete(string $id): void {
        verifyCsrf();
        $n = $this->pm->softDeleteStyle((int)$id);
        flash('success', $n > 1 ? "Product and {$n} sizes removed." : 'Product removed.');
        redirect('/products');
    }

    public function adjustStock(string $id): void {
        verifyCsrf();
        $qty  = (int)$_POST['quantity'];
        $type = $_POST['type'] ?? 'addition';
        $note = trim($_POST['note'] ?? '');
        // Sign by type: addition/return add stock; damaged removes; correction uses signed qty intent
        if (in_array($type, ['addition', 'return'], true)) {
            $qty = abs($qty);
        } elseif ($type === 'damaged') {
            $qty = -abs($qty);
        } else {
            // correction: form sends positive units — treat as signed via optional direction
            $dir = $_POST['direction'] ?? 'add';
            $qty = ($dir === 'remove') ? -abs($qty) : abs($qty);
        }
        $this->pm->adjustStock((int)$id, $qty, $type, $note, $_SESSION['user_id']);
        flash('success','Stock updated.');
        redirect('/products/'.$id.'/edit');
    }

    private function handleUpload(): ?string {
        if (empty($_FILES['image']) || !is_array($_FILES['image'])) return null;
        $f = $_FILES['image'];
        $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) return null;
        if ($err !== UPLOAD_ERR_OK) return null;
        if (!is_uploaded_file($f['tmp_name'] ?? '')) return null;

        $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
        // iPhone sometimes sends HEIC/empty ext — sniff MIME and map
        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = (string)finfo_file($fi, $f['tmp_name']);
                finfo_close($fi);
            }
        }
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (isset($mimeMap[$mime])) {
            $ext = $mimeMap[$mime];
        } elseif (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }
        if ($ext === 'jpeg') $ext = 'jpg';

        // Phone photos can be large; allow up to 8MB (client also compresses)
        if ((int)$f['size'] > 8 * 1024 * 1024) return null;

        if (!is_dir(UPLOAD_PATH)) {
            @mkdir(UPLOAD_PATH, 0755, true);
        }
        $name = 'prod_' . uniqid('', true) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], UPLOAD_PATH . $name)) return null;
        return $name;
    }
}

// ════════════════════════════════════════════════════════════
//  CategoryController
// ════════════════════════════════════════════════════════════
class CategoryController {
    public function index(): void {
        $cats = (new CategoryModel())->all();
        view('owner/categories', ['categories'=>$cats,'error'=>flash('error'),'success'=>flash('success')]);
    }
    public function store(): void {
        verifyCsrf();
        (new CategoryModel())->create($_POST);
        flash('success','Category added.');
        redirect('/categories');
    }
    public function delete(string $id): void {
        verifyCsrf();
        (new CategoryModel())->delete((int)$id);
        flash('success','Category deleted.');
        redirect('/categories');
    }
}

// ════════════════════════════════════════════════════════════
//  LocationController
// ════════════════════════════════════════════════════════════
class LocationController {
    public function index(): void {
        $locations = (new LocationModel())->all();
        view('owner/locations', ['locations'=>$locations,'success'=>flash('success')]);
    }
    public function store(): void {
        verifyCsrf();
        (new LocationModel())->create($_POST);
        flash('success','Location added.');
        redirect('/locations');
    }
    public function delete(string $id): void {
        verifyCsrf();
        (new LocationModel())->delete((int)$id);
        flash('success','Location removed.');
        redirect('/locations');
    }
}

// ════════════════════════════════════════════════════════════
//  StaffController
// ════════════════════════════════════════════════════════════
class StaffController {
    public function index(): void {
        $staff = (new UserModel())->allStaff();
        $sm = new SaleModel();
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        foreach ($staff as &$s) {
            $s['today_sales']  = count($sm->all(['staff_id'=>$s['id'],'date'=>$today]));
            $s['week_sales']   = count($sm->all(['staff_id'=>$s['id'],'week'=>true]));
        }
        view('owner/staff', compact('staff'));
    }
    public function create(): void {
        view('owner/staff_form', ['error'=>flash('error')]);
    }
    public function store(): void {
        verifyCsrf();
        $um = new UserModel();
        if ($um->findByEmail(trim($_POST['email']??''))) {
            flash('error','Email already in use.'); redirect('/staff/create');
        }
        $um->create($_POST);
        flash('success','Staff account created.');
        redirect('/staff');
    }
    public function toggle(string $id): void {
        verifyCsrf();
        (new UserModel())->toggle((int)$id);
        redirect('/staff');
    }
}

// ════════════════════════════════════════════════════════════
//  CustomerController
// ════════════════════════════════════════════════════════════
class CustomerController {
    private CustomerModel $cm;
    public function __construct() { $this->cm = new CustomerModel(); }

    public function index(): void {
        $search    = trim($_GET['search'] ?? '');
        $customers = $this->cm->all($search);
        view('owner/customers', compact('customers','search'));
    }
    public function store(): void {
        verifyCsrf();
        $this->cm->create($_POST);
        flash('success','Customer added.');
        redirect('/customers');
    }
    public function export(): void {
        $customers = $this->cm->all();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customers_'.date('Ymd').'.csv"');
        $f = fopen('php://output','w');
        fputcsv($f,['Name','Phone','Shoe Size','Notes','Created']);
        foreach ($customers as $c) fputcsv($f,[$c['name'],$c['phone'],$c['shoe_size'],$c['notes'],$c['created_at']]);
        fclose($f); exit;
    }
}

// ════════════════════════════════════════════════════════════
//  TargetController
// ════════════════════════════════════════════════════════════
class TargetController {
    public function index(): void {
        $tm = new TargetModel();
        $targets     = $tm->all();
        $performance = $tm->getActualVsTarget();
        $categories  = (new CategoryModel())->all();
        // Week start/end defaults
        $weekStart   = date('Y-m-d', strtotime('monday this week'));
        $weekEnd     = date('Y-m-d', strtotime('sunday this week'));
        view('owner/targets', compact('targets','performance','categories','weekStart','weekEnd'));
    }
    public function store(): void {
        verifyCsrf();
        $d = $_POST;
        $d['created_by']  = $_SESSION['user_id'];
        $d['category_id'] = $d['category_id'] ?: null;
        (new TargetModel())->create($d);
        flash('success','Target set.');
        redirect('/targets');
    }
    public function delete(string $id): void {
        verifyCsrf();
        (new TargetModel())->delete((int)$id);
        redirect('/targets');
    }
}

// ════════════════════════════════════════════════════════════
//  SalesController
// ════════════════════════════════════════════════════════════
class SalesController {
    private SaleModel $sm;
    public function __construct() { $this->sm = new SaleModel(); }

    public function index(): void {
        $filters = array_filter($_GET, fn($v) => $v !== '' && $v !== null);
        unset($filters['csrf']);
        $sales     = $this->sm->all($filters);
        $locations = (new LocationModel())->all();
        $staff     = (new UserModel())->allStaff();
        view('owner/sales', compact('sales','locations','staff','filters'));
    }

    public function view(string $id): void {
        $sale  = $this->sm->findById((int)$id);
        if (!$sale) redirect('/sales');
        $items = $this->sm->getItems((int)$id);
        $rm = new SaleReturnModel();
        $returnedQty = $rm->returnedQtyBySaleItem((int)$id);
        $returns = $rm->forSale((int)$id);
        view('owner/sale_detail', compact('sale','items','returnedQty','returns'));
    }

    public function returnForm(string $id): void {
        $sale = $this->sm->findById((int)$id);
        if (!$sale) redirect('/sales');
        $items = $this->sm->getItems((int)$id);
        $rm = new SaleReturnModel();
        $returnedQty = $rm->returnedQtyBySaleItem((int)$id);
        $pm = new ProductModel();
        // Sibling sizes per line for exchange dropdowns
        $siblings = [];
        foreach ($items as $it) {
            $siblings[(int)$it['product_id']] = $pm->siblings((int)$it['product_id'], true);
        }
        $allProducts = $pm->all([]);
        view('owner/sale_return', [
            'sale' => $sale,
            'items' => $items,
            'returnedQty' => $returnedQty,
            'siblings' => $siblings,
            'allProducts' => $allProducts,
            'error' => flash('error'),
        ]);
    }

    public function processReturn(string $id): void {
        verifyCsrf();
        $saleId = (int)$id;
        if (!$this->sm->findById($saleId)) redirect('/sales');

        $items = [];
        foreach ((array)($_POST['qty'] ?? []) as $saleItemId => $qty) {
            $items[] = ['sale_item_id' => (int)$saleItemId, 'quantity' => (int)$qty];
        }
        $exchanges = [];
        // Per-line exchange product (size swap)
        foreach ((array)($_POST['exchange_product'] ?? []) as $saleItemId => $productId) {
            $pid = (int)$productId;
            $qty = (int)($_POST['qty'][$saleItemId] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $exchanges[] = ['product_id' => $pid, 'quantity' => $qty];
            }
        }
        // Extra exchange rows
        foreach ((array)($_POST['extra_exchange'] ?? []) as $row) {
            if (!is_array($row)) continue;
            $exchanges[] = [
                'product_id' => (int)($row['product_id'] ?? 0),
                'quantity'   => (int)($row['quantity'] ?? 0),
            ];
        }

        try {
            $rm = new SaleReturnModel();
            $r = $rm->process([
                'sale_id'           => $saleId,
                'reason'            => trim($_POST['reason'] ?? ''),
                'refund_method'     => $_POST['refund_method'] ?? 'cash',
                'exchange_payment'  => $_POST['exchange_payment'] ?? 'cash',
                'notes'             => trim($_POST['notes'] ?? ''),
                'items'             => $items,
                'exchanges'         => $exchanges,
            ], (int)$_SESSION['user_id']);

            $msg = 'Return '.$r['return_ref'].' processed. Stock restored.';
            if ($r['refund_amount'] > 0) {
                $msg .= ' Refund: GHS '.number_format($r['refund_amount'], 2).'.';
            }
            if ($r['exchange_sale_id']) {
                $msg .= ' Exchange sale created.';
            }
            flash('success', $msg);
            redirect('/returns/'.$r['id']);
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not process return.');
            redirect('/sales/'.$saleId.'/return');
        }
    }

    public function edit(string $id): void {
        $sale = $this->sm->findById((int)$id);
        if (!$sale) redirect('/sales');
        $items     = $this->sm->getItems((int)$id);
        $locations = (new LocationModel())->all();
        $staff     = (new UserModel())->all();
        view('owner/sale_edit', [
            'sale' => $sale,
            'items' => $items,
            'locations' => $locations,
            'staff' => $staff,
            'error' => flash('error'),
        ]);
    }

    public function update(string $id): void {
        verifyCsrf();
        $id = (int)$id;
        if (!$this->sm->findById($id)) redirect('/sales');
        $pay = $_POST['payment_method'] ?? 'cash';
        if (!in_array($pay, ['cash','momo','card','split'], true)) $pay = 'cash';
        $ok = $this->sm->update($id, [
            'location_id'     => (int)($_POST['location_id'] ?? 0),
            'staff_id'        => (int)($_POST['staff_id'] ?? 0),
            'discount'        => (float)($_POST['discount'] ?? 0),
            'payment_method'  => $pay,
            'amount_tendered' => $_POST['amount_tendered'] ?? null,
            'momo_ref'        => $_POST['momo_ref'] ?? null,
            'notes'           => $_POST['notes'] ?? null,
        ]);
        if ($ok) flash('success', 'Sale updated.');
        else flash('error', 'Could not update sale.');
        redirect('/sales/'.$id);
    }

    public function delete(string $id): void {
        verifyCsrf();
        if ($this->sm->delete((int)$id)) flash('success', 'Sale deleted and stock restored.');
        else flash('error', 'Sale not found.');
        redirect('/sales');
    }

    public function bulk(): void {
        verifyCsrf();
        $action = $_POST['bulk_action'] ?? '';
        $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), fn($id) => $id > 0));

        if ($action === 'export') {
            $this->exportCsv($ids);
            return;
        }

        if ($action === 'delete') {
            if (!$ids) {
                flash('error', 'Select at least one sale.');
                redirect('/sales');
            }
            $n = $this->sm->deleteMany($ids);
            flash('success', $n.' sale'.($n===1?'':'s').' deleted and stock restored.');
            redirect('/sales');
        }

        flash('error', 'Unknown bulk action.');
        redirect('/sales');
    }

    private function exportCsv(array $ids = []): void {
        if ($ids) {
            $sales = [];
            foreach ($ids as $id) {
                $s = $this->sm->findById($id);
                if ($s) $sales[] = $s;
            }
        } else {
            $filters = array_filter((array)($_POST['filters'] ?? []), fn($v) => $v !== '' && $v !== null);
            $sales = $this->sm->all($filters);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sales-'.date('Ymd-His').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Ref','Date','Staff','Location','Payment','Subtotal','Discount','Total','MoMo Ref','Notes']);
        foreach ($sales as $s) {
            fputcsv($out, [
                $s['sale_ref'],
                $s['created_at'],
                $s['staff_name'],
                $s['location_name'],
                $s['payment_method'],
                $s['subtotal'],
                $s['discount'],
                $s['total'],
                $s['momo_ref'] ?? '',
                $s['notes'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }
}

// ════════════════════════════════════════════════════════════
//  ReportController
// ════════════════════════════════════════════════════════════
class ReportController {
    private SaleModel $sm;
    public function __construct() { $this->sm = new SaleModel(); }

    public function daily(): void {
        $date  = $_GET['date'] ?? date('Y-m-d');
        $sum   = $this->sm->summaryForPeriod($date, $date);
        $byCat = $this->sm->byCategory($date, $date);
        $byLoc = $this->sm->byLocation($date, $date);
        $byStaff = $this->sm->byStaff($date, $date);
        $sales   = $this->sm->all(['date' => $date]);
        view('owner/report_daily', compact('date','sum','byCat','byLoc','byStaff','sales'));
    }

    public function weekly(): void {
        $weekStart = $_GET['week_start'] ?? date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime($weekStart.' +6 days'));
        $sum     = $this->sm->summaryForPeriod($weekStart, $weekEnd);
        $byCat   = $this->sm->byCategory($weekStart, $weekEnd);
        $byLoc   = $this->sm->byLocation($weekStart, $weekEnd);
        $byStaff = $this->sm->byStaff($weekStart, $weekEnd);
        $pm      = new ProductModel();
        $topSellers = $pm->topSellers('week');
        view('owner/report_weekly', compact('weekStart','weekEnd','sum','byCat','byLoc','byStaff','topSellers'));
    }

    public function monthly(): void {
        $month = $_GET['month'] ?? date('Y-m');
        [$y,$m] = explode('-', $month);
        $start = "$y-$m-01";
        $end   = date('Y-m-t', strtotime($start));
        $sum     = $this->sm->summaryForPeriod($start, $end);
        $byCat   = $this->sm->byCategory($start, $end);
        $byLoc   = $this->sm->byLocation($start, $end);
        $byStaff = $this->sm->byStaff($start, $end);
        $pm      = new ProductModel();
        $topSellers = $pm->topSellers('month');
        view('owner/report_monthly', compact('month','start','end','sum','byCat','byLoc','byStaff','topSellers'));
    }

    public function staff(): void {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-d');
        $data  = $this->sm->byStaff($start, $end);
        $staffList = (new UserModel())->allStaff();
        view('owner/report_staff', compact('start','end','data','staffList'));
    }

    public function locations(): void {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-d');
        $data  = $this->sm->byLocation($start, $end);
        view('owner/report_locations', compact('start','end','data'));
    }
}

// ════════════════════════════════════════════════════════════
//  ApiController
// ════════════════════════════════════════════════════════════
class ApiController {
    private function json(mixed $d): void {
        header('Content-Type: application/json');
        echo json_encode($d); exit;
    }
    public function alerts(): void {
        $pm     = new ProductModel();
        $alerts = $pm->getLowStockAlerts(true);
        $this->json(['alerts' => $alerts, 'count' => count($alerts)]);
    }
    public function markAlertsRead(): void {
        (new ProductModel())->markAlertsRead();
        $this->json(['ok' => true]);
    }
}

// ════════════════════════════════════════════════════════════
//  PurchaseController — purchase orders / receive stock
// ════════════════════════════════════════════════════════════
class PurchaseController {
    private PurchaseOrderModel $pom;
    private ProductModel $pm;

    public function __construct() {
        $this->pom = new PurchaseOrderModel();
        $this->pm = new ProductModel();
    }

    public function index(): void {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];
        $orders = $this->pom->all(array_filter($filters, static fn($v) => $v !== '' && $v !== null));
        view('owner/purchases', compact('orders', 'filters'));
    }

    public function create(): void {
        $products = $this->pm->all([]);
        $suppliers = (new SupplierModel())->all();
        view('owner/purchase_form', [
            'products' => $products,
            'suppliers' => $suppliers,
            'error' => flash('error'),
        ]);
    }

    public function store(): void {
        verifyCsrf();
        $items = $this->parseItems($_POST['items'] ?? []);
        $action = $_POST['action'] ?? 'draft';
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $supplierName = trim($_POST['supplier'] ?? '');
        if ($supplierId > 0 && $supplierName === '') {
            $sup = (new SupplierModel())->findById($supplierId);
            if ($sup) $supplierName = $sup['name'];
        }
        $header = [
            'supplier'    => $supplierName,
            'supplier_id' => $supplierId > 0 ? $supplierId : null,
            'notes'       => trim($_POST['notes'] ?? ''),
            'status'      => $action === 'order' ? 'ordered' : 'draft',
        ];
        $updateCost = !empty($_POST['update_cost']);

        try {
            if ($action === 'receive') {
                $r = $this->pom->createAndReceive($header, $items, (int)$_SESSION['user_id'], $updateCost);
                flash('success', "Stock received: {$r['received_units']} units on PO.");
                redirect('/purchases/'.$r['po_id']);
            }
            $poId = $this->pom->create($header, $items, (int)$_SESSION['user_id']);
            flash('success', $action === 'order'
                ? 'Purchase order saved as ordered.'
                : 'Purchase order saved as draft.');
            redirect('/purchases/'.$poId);
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not save purchase order.');
            redirect('/purchases/create');
        }
    }

    public function show(string $id): void {
        $order = $this->pom->findById((int)$id);
        if (!$order) redirect('/purchases');
        $items = $this->pom->getItems((int)$id);
        view('owner/purchase_show', compact('order', 'items'));
    }

    public function receive(string $id): void {
        verifyCsrf();
        $map = [];
        foreach ((array)($_POST['recv'] ?? []) as $itemId => $qty) {
            $map[(int)$itemId] = (int)$qty;
        }
        $updateCost = !empty($_POST['update_cost']);
        try {
            $r = $this->pom->receive((int)$id, $map, (int)$_SESSION['user_id'], $updateCost);
            flash('success', "Received {$r['received_units']} units ({$r['lines']} line".($r['lines']===1?'':'s')."). Status: {$r['status']}.");
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not receive stock.');
        }
        redirect('/purchases/'.$id);
    }

    public function markOrdered(string $id): void {
        verifyCsrf();
        if ($this->pom->markOrdered((int)$id)) {
            flash('success', 'Marked as ordered.');
        } else {
            flash('error', 'Could not update status.');
        }
        redirect('/purchases/'.$id);
    }

    public function cancel(string $id): void {
        verifyCsrf();
        try {
            $this->pom->cancel((int)$id);
            flash('success', 'Purchase order cancelled.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not cancel.');
        }
        redirect('/purchases/'.$id);
    }

    /** @return list<array{product_id:int,quantity:int,unit_cost:float}> */
    private function parseItems($raw): array {
        if (!is_array($raw)) return [];
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) continue;
            $pid = (int)($row['product_id'] ?? 0);
            $qty = (int)($row['quantity'] ?? 0);
            if ($pid < 1 || $qty < 1) continue;
            $out[] = [
                'product_id' => $pid,
                'quantity'   => $qty,
                'unit_cost'  => (float)($row['unit_cost'] ?? 0),
            ];
        }
        return $out;
    }
}

// ════════════════════════════════════════════════════════════
//  SmsCampaignController — bulk SMS via Arkesel
// ════════════════════════════════════════════════════════════
class SmsCampaignController {
    private SmsCampaignModel $sms;
    private CustomerModel $cm;

    public function __construct() {
        $this->sms = new SmsCampaignModel();
        $this->cm = new CustomerModel();
    }

    public function index(): void {
        $campaigns = $this->sms->all(50);
        $customerPhoneCount = $this->cm->countWithPhones();
        view('owner/sms_campaigns', compact('campaigns', 'customerPhoneCount'));
    }

    public function create(): void {
        $customerPhoneCount = $this->cm->countWithPhones();
        $balance = $this->sms->checkBalance();
        view('owner/sms_campaign_form', [
            'customerPhoneCount' => $customerPhoneCount,
            'balance' => $balance,
            'error' => flash('error'),
            'old' => $_SESSION['sms_form'] ?? [],
        ]);
        unset($_SESSION['sms_form']);
    }

    public function store(): void {
        verifyCsrf();
        $form = [
            'title'             => trim($_POST['title'] ?? ''),
            'message'           => trim($_POST['message'] ?? ''),
            'include_customers' => !empty($_POST['include_customers']),
            'custom_numbers'    => trim($_POST['custom_numbers'] ?? ''),
        ];
        $_SESSION['sms_form'] = $form;

        try {
            $r = $this->sms->createAndSend($form, (int)$_SESSION['user_id']);
            unset($_SESSION['sms_form']);
            $msg = "SMS campaign sent: {$r['sent']} delivered";
            if ($r['failed'] > 0) {
                $msg .= ", {$r['failed']} failed";
            }
            $msg .= " of {$r['total']}.";
            flash($r['failed'] > 0 && $r['sent'] === 0 ? 'error' : 'success', $msg);
            redirect('/sms/'.$r['id']);
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not send SMS campaign.');
            redirect('/sms/create');
        }
    }

    public function show(string $id): void {
        $campaign = $this->sms->findById((int)$id);
        if (!$campaign) redirect('/sms');
        $recipients = $this->sms->getRecipients((int)$id);
        view('owner/sms_campaign_show', compact('campaign', 'recipients'));
    }
}

// ════════════════════════════════════════════════════════════
//  ReturnController — list / view processed returns
// ════════════════════════════════════════════════════════════
class ReturnController {
    private SaleReturnModel $rm;
    public function __construct() { $this->rm = new SaleReturnModel(); }

    public function index(): void {
        $filters = [
            'date'   => $_GET['date'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];
        $returns = $this->rm->all(array_filter($filters, static fn($v) => $v !== '' && $v !== null));
        view('owner/returns', compact('returns', 'filters'));
    }

    public function show(string $id): void {
        $return = $this->rm->findById((int)$id);
        if (!$return) redirect('/returns');
        $items = $this->rm->getItems((int)$id);
        $exchanges = $this->rm->getExchanges((int)$id);
        view('owner/return_show', compact('return', 'items', 'exchanges'));
    }
}

// ════════════════════════════════════════════════════════════
//  StockHistoryController
// ════════════════════════════════════════════════════════════
class StockHistoryController {
    public function index(): void {
        $filters = [
            'search'     => trim($_GET['search'] ?? ''),
            'type'       => $_GET['type'] ?? '',
            'source'     => $_GET['source'] ?? '',
            'date_from'  => $_GET['date_from'] ?? '',
            'date_to'    => $_GET['date_to'] ?? '',
            'product_id' => $_GET['product_id'] ?? '',
        ];
        $pm = new ProductModel();
        $history = $pm->stockHistory(array_filter($filters, static fn($v) => $v !== '' && $v !== null));
        view('owner/stock_history', compact('history', 'filters'));
    }
}

// ════════════════════════════════════════════════════════════
//  SupplierController
// ════════════════════════════════════════════════════════════
class SupplierController {
    private SupplierModel $sm;
    public function __construct() { $this->sm = new SupplierModel(); }

    public function index(): void {
        $search = trim($_GET['search'] ?? '');
        $suppliers = $this->sm->all($search, false);
        view('owner/suppliers', compact('suppliers', 'search'));
    }

    public function store(): void {
        verifyCsrf();
        try {
            $this->sm->create($_POST);
            flash('success', 'Supplier added.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage() ?: 'Could not add supplier.');
        }
        redirect('/suppliers');
    }

    public function delete(string $id): void {
        verifyCsrf();
        $this->sm->deactivate((int)$id);
        flash('success', 'Supplier deactivated.');
        redirect('/suppliers');
    }
}

// ════════════════════════════════════════════════════════════
//  HelpController — in-app user guide
// ════════════════════════════════════════════════════════════
class HelpController {
    public function manual(): void {
        view('help/manual', [
            'isOwner' => isOwner(),
        ]);
    }
}
