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
        view('owner/product_form', ['categories'=>$categories,'product'=>null,'variants'=>[],'error'=>flash('error')]);
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
        if ($type !== 'addition') $qty = -abs($qty);
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
        view('owner/sale_detail', compact('sale','items'));
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
//  HelpController — in-app user guide
// ════════════════════════════════════════════════════════════
class HelpController {
    public function manual(): void {
        view('help/manual', [
            'isOwner' => isOwner(),
        ]);
    }
}
