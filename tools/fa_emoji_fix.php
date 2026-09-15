<?php
$faCdn = '  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">';

$replacements = [
    // exact common button/header snippets without relying on emoji in PHP source where possible
];

$files = glob(dirname(__DIR__) . '/app/Views/{owner,pos,auth,layouts}/*.php', GLOB_BRACE)
    ?: [];
// recursive
$all = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/app/Views'));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') $all[] = $f->getPathname();
}

foreach ($all as $path) {
    $c = file_get_contents($path);
    $orig = $c;

    // Remove emoji by unicode property and leave placeholders we'll fix with context-aware replaces below
    // Header patterns (match any emoji before known titles)
    $c = preg_replace('/<h3>\X+\s*Sale Locations<\/h3>/u', '<h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sale Locations</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Add Location<\/h3>/u', '<h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Location</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Product Categories<\/h3>/u', '<h3><i class="fa-solid fa-tag" aria-hidden="true"></i> Product Categories</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Add Category<\/h3>/u', '<h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Category</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Customers/u', '<h3><i class="fa-solid fa-users" aria-hidden="true"></i> Customers', $c);
    $c = preg_replace('/<h3>\X+\s*Add Customer<\/h3>/u', '<h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Customer</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Add Staff Member<\/h3>/u', '<h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Staff Member</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*This Week\'s Performance<\/h3>/u', '<h3><i class="fa-solid fa-bullseye" aria-hidden="true"></i> This Week\'s Performance</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*All Targets<\/h3>/u', '<h3><i class="fa-solid fa-list" aria-hidden="true"></i> All Targets</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Set New Target<\/h3>/u', '<h3><i class="fa-solid fa-plus" aria-hidden="true"></i> Set New Target</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Financials<\/h3>/u', '<h3><i class="fa-solid fa-wallet" aria-hidden="true"></i> Financials</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Stock Adjustment<\/h3>/u', '<h3><i class="fa-solid fa-box" aria-hidden="true"></i> Stock Adjustment</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*Top Selling Products/u', '<h3><i class="fa-solid fa-trophy" aria-hidden="true"></i> Top Selling Products', $c);
    $c = preg_replace('/<h3>\X+\s*Top Selling Sizes<\/h3>/u', '<h3><i class="fa-solid fa-trophy" aria-hidden="true"></i> Top Selling Sizes</h3>', $c);
    $c = preg_replace('/<h3>\X+\s*All Transactions/u', '<h3><i class="fa-solid fa-list" aria-hidden="true"></i> All Transactions', $c);
    $c = preg_replace('/<h3>\X+\s*Sales by Location/u', '<h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Sales by Location', $c);
    $c = preg_replace('/<h3>\X+\s*Staff Sales Performance/u', '<h3><i class="fa-solid fa-user" aria-hidden="true"></i> Staff Sales Performance', $c);
    $c = preg_replace('/<h2[^>]*>\X+\s*Staff<\/h2>/u', '<h2 style="flex:1;font-size:1.25rem;display:flex;align-items:center;gap:.5rem"><i class="fa-solid fa-users" aria-hidden="true"></i> Staff</h2>', $c);

    // Edit/Add product header
    $c = preg_replace(
        "/<h3><\?= \\\$isEdit \? '\X+ Edit' : '\X+ Add' \?\> Product<\/h3>/u",
        '<h3><?= $isEdit ? \'<i class="fa-solid fa-pen" aria-hidden="true"></i> Edit\' : \'<i class="fa-solid fa-plus" aria-hidden="true"></i> Add\' ?> Product</h3>',
        $c
    );

    // Buttons
    $c = preg_replace('/class="btn btn-primary">\X+ Add Product<\/a>/u', 'class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product</a>', $c);
    $c = preg_replace('/class="btn btn-primary">\X+ Add Staff<\/a>/u', 'class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Staff</a>', $c);
    $c = preg_replace('/class="btn btn-ghost btn-sm">\X+ Export CSV<\/a>/u', 'class="btn btn-ghost btn-sm"><i class="fa-solid fa-download" aria-hidden="true"></i> Export CSV</a>', $c);
    $c = preg_replace('/class="btn btn-ghost btn-sm[^"]*"[^>]*>\X*\s*Print/u', 'class="btn btn-ghost btn-sm ml-auto no-print"><i class="fa-solid fa-print" aria-hidden="true"></i> Print', $c);
    $c = preg_replace('/no-print">\X+\s*Print<\/a>/u', 'no-print"><i class="fa-solid fa-print" aria-hidden="true"></i> Print</a>', $c);

    // Select options - strip leading emoji
    $c = preg_replace('/<option value="shop">\X+\s*Shop<\/option>/u', '<option value="shop">Shop</option>', $c);
    $c = preg_replace('/<option value="school">\X+\s*School[^<]*<\/option>/u', '<option value="school">School activation</option>', $c);
    $c = preg_replace('/<option value="other">\X+\s*Other<\/option>/u', '<option value="other">Other</option>', $c);
    $c = preg_replace('/<option value="cash">\X+\s*Cash<\/option>/u', '<option value="cash">Cash</option>', $c);
    $c = preg_replace('/<option value="momo">\X+\s*Mobile Money<\/option>/u', '<option value="momo">Mobile Money</option>', $c);
    $c = preg_replace('/<option value="card">\X+\s*Card<\/option>/u', '<option value="card">Card</option>', $c);

    // Arrows / symbols
    $c = str_replace(' →</button>', ' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>', $c);
    $c = str_replace(' →</a>', ' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>', $c);
    $c = str_replace('Create Staff →', 'Create Staff <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Add Customer →', 'Add Customer <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Add Category →', 'Add Category <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Add Location →', 'Add Location <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Set Target →', 'Set Target <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('View →', 'View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Proceed to Payment →', 'Proceed to Payment <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Sign In →', 'Sign In <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('All Sales →', 'All Sales <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('✓ Complete Sale', '<i class="fa-solid fa-check" aria-hidden="true"></i> Complete Sale', $c);
    $c = str_replace('✓ ', '<i class="fa-solid fa-check" aria-hidden="true"></i> ', $c);
    $c = str_replace('✗ ', '<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> ', $c);

    // Product form save button pattern
    $c = preg_replace(
        "/<\?= \\\$isEdit \? 'Save Changes' : 'Add Product' \?\> →/",
        '<?= $isEdit ? \'<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Changes\' : \'<i class="fa-solid fa-plus" aria-hidden="true"></i> Add Product\' ?>',
        $c
    );

    // Strip remaining emoji chars (safety) — keep password bullets
    $c = preg_replace('/(?<!placeholder=")[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}]/u', '', $c);

    // Clean double spaces left by emoji removal in text nodes (careful)
    $c = preg_replace('/>\s{2,}</', '> <', $c);

    if ($c !== $orig) {
        file_put_contents($path, $c);
        echo "emoji-fixed: " . basename($path) . "\n";
    }
}
echo "done\n";
