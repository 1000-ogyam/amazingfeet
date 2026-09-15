<?php
class AssessmentController {
    private AssessmentModel $am;
    private SaleModel $sm;

    public function __construct() {
        $this->am = new AssessmentModel();
        $this->sm = new SaleModel();
    }

    /** Staff only: daily assessment form. */
    public function form(): void {
        if (isOwner()) redirect('/assessments');
        requireStaff();
        $staffId = (int)$_SESSION['user_id'];
        $date = $_GET['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

        $existing = $this->am->findForStaffDate($staffId, $date);
        $stats = $this->salesSnapshot($staffId, $date);
        $error = flash('error');
        view('staff/assessment_form', compact('date','existing','stats','error') + [
            'staffName' => $_SESSION['user_name'] ?? '',
        ]);
    }

    public function save(): void {
        if (isOwner()) redirect('/assessments');
        requireStaff();
        verifyCsrf();
        $staffId = (int)$_SESSION['user_id'];
        $date = $_POST['report_date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            flash('error', 'Invalid date.');
            redirect('/assessment');
        }

        $stats = $this->salesSnapshot($staffId, $date);
        $yn = static fn(string $k): string => (($_POST[$k] ?? '') === 'yes') ? 'yes' : 'no';

        $this->am->upsert($staffId, $date, [
            'pairs_day'          => $stats['pairs_day'],
            'pairs_week'         => $stats['pairs_week'],
            'pairs_month'        => $stats['pairs_month'],
            'week_target'        => AssessmentModel::WEEK_TARGET,
            'month_target'       => AssessmentModel::MONTH_TARGET,
            'commission_level'   => $stats['commission_level'],
            'customers_today'    => $stats['customers_today'],
            'contacts_collected' => $stats['contacts_collected'],
            'asked_all_customers'=> $yn('asked_all_customers'),
            'floor_cleaned'      => $yn('floor_cleaned'),
            'glass_cleaned'      => $yn('glass_cleaned'),
            'shelves_dusted'     => $yn('shelves_dusted'),
            'shoes_arranged'     => $yn('shoes_arranged'),
            'counter_cleaned'    => $yn('counter_cleaned'),
            'toilet_cleaned'     => $yn('toilet_cleaned'),
            'sink_cleaned'       => $yn('sink_cleaned'),
            'washroom_floor_ok'  => $yn('washroom_floor_ok'),
            'tissue_soap_ok'     => $yn('tissue_soap_ok'),
            'neat_dressing'      => $yn('neat_dressing'),
            'clean_footwear'     => $yn('clean_footwear'),
            'hair_tidy'          => $yn('hair_tidy'),
            'presentation_ok'    => $yn('presentation_ok'),
            'whatsapp_posted'    => $yn('whatsapp_posted'),
            'whatsapp_content'   => trim($_POST['whatsapp_content'] ?? '') ?: null,
            'has_complaints'     => $yn('has_complaints'),
            'complaints_detail'  => trim($_POST['complaints_detail'] ?? '') ?: null,
            'notes'              => trim($_POST['notes'] ?? '') ?: null,
        ]);

        flash('success', 'Daily report saved for '.date('d M Y', strtotime($date)).'.');
        redirect('/assessment?date='.$date);
    }

    public function myHistory(): void {
        if (isOwner()) redirect('/assessments');
        requireStaff();
        $rows = $this->am->forStaff((int)$_SESSION['user_id']);
        view('staff/assessment_history', compact('rows'));
    }

    /** Owner: list staff assessments only. */
    public function index(): void {
        requireOwner();
        $filters = array_filter($_GET, fn($v) => $v !== '' && $v !== null);
        unset($filters['csrf']);
        $filters['staff_only'] = true;
        $rows = $this->am->all($filters);
        $staff = (new UserModel())->allStaff();
        view('owner/assessments', compact('rows','staff','filters'));
    }

    public function show(string $id): void {
        requireOwner();
        $row = $this->am->findById((int)$id);
        if (!$row || ($row['staff_role'] ?? '') !== 'staff') redirect('/assessments');
        $score = $this->am->checklistScore($row);
        view('owner/assessment_detail', compact('row','score'));
    }

    private function salesSnapshot(int $staffId, string $date): array {
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $monthStart = date('Y-m-01', strtotime($date));
        $pairsDay = $this->sm->staffUnits($staffId, $date, $date);
        $pairsWeek = $this->sm->staffUnits($staffId, $weekStart, $date);
        $pairsMonth = $this->sm->staffUnits($staffId, $monthStart, $date);
        $cust = $this->sm->staffCustomerStats($staffId, $date);
        return [
            'pairs_day'          => $pairsDay,
            'pairs_week'         => $pairsWeek,
            'pairs_month'        => $pairsMonth,
            'week_target'        => AssessmentModel::WEEK_TARGET,
            'month_target'       => AssessmentModel::MONTH_TARGET,
            'commission_level'   => AssessmentModel::commissionLevel($pairsMonth),
            'customers_today'    => $cust['customers_today'],
            'contacts_collected' => $cust['contacts_collected'],
            'week_start'         => $weekStart,
            'month_start'        => $monthStart,
        ];
    }
}
