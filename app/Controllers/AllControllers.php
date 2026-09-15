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
        $filters   = array_filter($_GET, fn($v) => $v !== '');
        $products  = $this->pm->all($filters);
        $categories = $this->cm->all();
        $stockValue = $this->pm->getStockValue();
        view('owner/products', compact('products','categories','filters','stockValue'));
    }

    public function create(): void {
        $categories = $this->cm->all();
        view('owner/product_form', ['categories'=>$categories,'product'=>null,'error'=>flash('error')]);
    }

    public function store(): void {
        verifyCsrf();
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = $this->handleUpload();
            if (!$image) { flash('error','Invalid image file.'); redirect('/products/create'); }
        }
        $d = $_POST;
        $d['image'] = $image;
        $d['barcode'] = $d['barcode'] ?: null;
        $this->pm->create($d);
        flash('success','Product created.');
        redirect('/products');
    }

    public function edit(string $id): void {
        $product = $this->pm->findById((int)$id);
        if (!$product) redirect('/products');
        $categories = $this->cm->all();
        view('owner/product_form', compact('product','categories') + ['error'=>flash('error')]);
    }

    public function update(string $id): void {
        verifyCsrf();
        $image = null;
        if (!empty($_FILES['image']['name'])) {
            $image = $this->handleUpload();
        }
        $d = $_POST; $d['image'] = $image;
        $this->pm->update((int)$id, $d);
        flash('success','Product updated.');
        redirect('/products');
    }

    public function delete(string $id): void {
        verifyCsrf();
        $this->pm->softDelete((int)$id);
        flash('success','Product removed.');
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
        $f   = $_FILES['image'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext,['jpg','jpeg','png','webp'])) return null;
        if ($f['size'] > 3*1024*1024) return null;
        $name = 'prod_'.uniqid().'.'.$ext;
        move_uploaded_file($f['tmp_name'], UPLOAD_PATH.$name);
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
