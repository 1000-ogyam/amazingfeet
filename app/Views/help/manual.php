<?php
$pageTitle = 'User Guide';
$cp = '/help';
$extraHead = '<link rel="stylesheet" href="'.BASE_PATH.'/assets/css/guide.css">';
ob_start();
$role = $isOwner ? 'owner' : 'staff';
?>
<div class="guide-layout">
  <aside class="guide-toc no-print" aria-label="Guide contents">
    <p class="guide-toc-title">Contents</p>
    <a href="#overview">Overview &amp; roles</a>
    <a href="#signin">Sign in &amp; out</a>
    <a href="#navigation">Navigation</a>
    <a href="#pos">POS Terminal</a>
    <a href="#receipts">Receipts</a>
    <?php if ($isOwner): ?>
    <a href="#dashboard">Dashboard</a>
    <a href="#sales">All Sales</a>
    <a href="#products">Products &amp; stock</a>
    <a href="#categories">Categories</a>
    <a href="#locations">Locations</a>
    <a href="#customers">Customers</a>
    <a href="#staff">Staff accounts</a>
    <a href="#targets">Sales targets</a>
    <a href="#reports">Reports</a>
    <a href="#alerts">Low stock alerts</a>
    <?php endif; ?>
    <a href="#tips">Tips &amp; troubleshooting</a>
  </aside>

  <div class="guide-main">
    <header class="guide-hero">
      <h1><i class="fa-solid fa-book-open" aria-hidden="true"></i> Amazing Feet User Guide</h1>
      <p>Everything you need to sell shoes, manage stock, and run the business — written for both shop floor staff and owners.</p>
      <div class="guide-hero-meta">
        <span class="guide-badge guide-badge-all">Everyone</span>
        <span class="guide-badge guide-badge-staff">Staff</span>
        <span class="guide-badge guide-badge-owner">Owner</span>
        <span class="text-sm" style="color:rgba(245,242,238,.7)">You are signed in as <strong style="color:#fff"><?= e(ucfirst($role)) ?></strong></span>
      </div>
      <div class="guide-actions">
        <a href="<?= BASE_PATH ?>/pos" class="btn btn-primary btn-sm"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Open POS</a>
        <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()"><i class="fa-solid fa-print" aria-hidden="true"></i> Print guide</button>
      </div>
    </header>

    <!-- Overview -->
    <section class="guide-section" id="overview">
      <h2><i class="fa-solid fa-compass" aria-hidden="true"></i> Overview &amp; roles <span class="guide-badge guide-badge-all">Everyone</span></h2>
      <p>Amazing Feet is a point-of-sale and operations system for shoe retail. Staff focus on selling; owners manage inventory, people, and reports.</p>
      <div class="guide-grid">
        <div class="guide-tile">
          <strong>Staff</strong>
          <span>Use the POS to find products, take payment, and print receipts. Access the User Guide anytime.</span>
        </div>
        <div class="guide-tile">
          <strong>Owner</strong>
          <span>Everything staff can do, plus dashboard, products, sales history, reports, staff, customers, locations, and targets.</span>
        </div>
      </div>
      <div class="guide-table-wrap">
        <table class="guide-table">
          <thead><tr><th>Area</th><th>Staff</th><th>Owner</th></tr></thead>
          <tbody>
            <tr><td>POS / checkout / receipts</td><td>Yes</td><td>Yes</td></tr>
            <tr><td>Dashboard &amp; reports</td><td>—</td><td>Yes</td></tr>
            <tr><td>Products, categories, stock</td><td>—</td><td>Yes</td></tr>
            <tr><td>Edit / delete sales</td><td>—</td><td>Yes</td></tr>
            <tr><td>Staff, customers, locations, targets</td><td>—</td><td>Yes</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Sign in -->
    <section class="guide-section" id="signin">
      <h2><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Sign in &amp; out <span class="guide-badge guide-badge-all">Everyone</span></h2>
      <ol class="guide-steps">
        <li>Open the Amazing Feet home / login page in your browser.</li>
        <li>Enter the <strong>email</strong> and <strong>password</strong> given by the owner.</li>
        <li>After login, <strong>owners</strong> land on the Dashboard; <strong>staff</strong> go straight to the POS.</li>
        <li>To sign out, use <strong>Logout</strong> in the sidebar footer (or on the POS top bar).</li>
      </ol>
      <div class="guide-callout warn">Never share your password. Ask the owner to reset your account if you forget it.</div>
    </section>

    <!-- Navigation -->
    <section class="guide-section" id="navigation">
      <h2><i class="fa-solid fa-bars" aria-hidden="true"></i> Navigation <span class="guide-badge guide-badge-all">Everyone</span></h2>
      <ul>
        <li><strong>Sidebar</strong> — main menu. On desktop, collapse it with the chevron; on phone, open it with the menu (☰) button.</li>
        <li><strong>Open POS</strong> — always available in the top bar for quick selling.</li>
        <li><strong>User Guide</strong> — this page, under Help in the sidebar (also linked from POS as Guide).</li>
        <?php if ($isOwner): ?>
        <li><strong>Alerts</strong> — warning icon in the top bar shows low-stock products.</li>
        <?php endif; ?>
      </ul>
    </section>

    <!-- POS -->
    <section class="guide-section" id="pos">
      <h2><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> POS Terminal <span class="guide-badge guide-badge-all">Everyone</span></h2>
      <p>The POS is where every sale starts. Open it from the sidebar, top bar, or after staff login.</p>

      <h3>Find products</h3>
      <ul>
        <li>Type in the search box (name, size, gender, design).</li>
        <li>Filter by <strong>category</strong> or <strong>gender</strong> (Boys, Girls, Unisex, Ladies).</li>
        <li>Switch <strong>grid / list</strong> view with the toggle (remembered on this device).</li>
        <li>Browse pages with pagination (10 products per page).</li>
        <li>Use the <strong>barcode</strong> button, then scan or type a barcode into the barcode field.</li>
      </ul>

      <h3>Build the cart</h3>
      <ol class="guide-steps">
        <li>Click a product to add it. Click again to increase quantity (stock allowing).</li>
        <li>Adjust quantity or remove lines in the cart on the right.</li>
        <li>Optional: enter a cart <strong>Discount (GHS)</strong> before payment.</li>
        <li>When ready, tap <strong>Proceed to Payment</strong>.</li>
      </ol>

      <h3>Complete payment</h3>
      <ul>
        <li>Choose <strong>Sale Location</strong> (which shop / stall).</li>
        <li>Choose payment: <strong>Cash</strong>, <strong>Mobile Money</strong>, or <strong>Card</strong>.</li>
        <li><strong>Cash</strong> — enter amount tendered; change is calculated automatically.</li>
        <li><strong>MoMo</strong> — enter the transaction / reference ID when available.</li>
        <li>Optionally add customer name, phone, shoe size, and notes.</li>
        <li>Tap <strong>Complete Sale</strong>. Stock is deducted immediately.</li>
      </ul>
      <div class="guide-callout ok">After a successful sale, a receipt opens in an overlay. You can print it without leaving the POS.</div>
    </section>

    <!-- Receipts -->
    <section class="guide-section" id="receipts">
      <h2><i class="fa-solid fa-receipt" aria-hidden="true"></i> Receipts <span class="guide-badge guide-badge-all">Everyone</span></h2>
      <ul>
        <li>From POS: print from the receipt overlay after checkout.</li>
        <?php if ($isOwner): ?>
        <li>From <strong>All Sales → View</strong>: use <strong>Print</strong> to open the receipt again.</li>
        <?php endif; ?>
        <li>Receipts include sale reference (e.g. <span class="guide-kbd">AF-…</span>), items, totals, payment method, and staff name.</li>
      </ul>
    </section>

    <?php if ($isOwner): ?>
    <!-- Dashboard -->
    <section class="guide-section" id="dashboard">
      <h2><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Dashboard <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <p>Your home screen after login. Use it for a quick health check of the business.</p>
      <ul>
        <li><strong>Today / week</strong> sales summaries</li>
        <li><strong>Targets</strong> progress vs weekly goals</li>
        <li><strong>Top sellers</strong> and <strong>low stock</strong> highlights</li>
        <li><strong>Stock value</strong> (retail / cost)</li>
        <li><strong>Recent sales</strong> and a 30-day revenue chart</li>
      </ul>
    </section>

    <!-- Sales -->
    <section class="guide-section" id="sales">
      <h2><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> All Sales <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <p>New sales are created in POS. This screen is for reviewing and correcting history.</p>

      <h3>Filter &amp; find</h3>
      <ul>
        <li>Search by sale ref, staff, location, or notes.</li>
        <li>Filter by date, location, staff, or payment method.</li>
        <li><strong>New Sale</strong> opens the POS.</li>
      </ul>

      <h3>View a sale</h3>
      <ul>
        <li>Open any row with the eye icon to see line items, totals, and profit margin (owner view).</li>
        <li>From detail: <strong>Edit</strong>, <strong>Print</strong>, or <strong>Delete</strong>.</li>
      </ul>

      <h3>Edit a sale</h3>
      <p>You can change location, staff, payment method, discount (total recalculates), cash tendered, MoMo ref, and notes. Line items stay fixed — to reverse stock, delete the sale instead.</p>

      <h3>Delete &amp; bulk actions</h3>
      <ol class="guide-steps">
        <li>Select rows with checkboxes (or Select all).</li>
        <li><strong>Export</strong> — CSV of selected sales, or all filtered results if none selected.</li>
        <li><strong>Delete</strong> — removes sales and <strong>restores stock</strong> for those items. Confirm carefully.</li>
      </ol>
      <div class="guide-callout warn">Deleting a sale cannot be undone. Stock is put back; the sale record is gone.</div>
    </section>

    <!-- Products -->
    <section class="guide-section" id="products">
      <h2><i class="fa-solid fa-box" aria-hidden="true"></i> Products &amp; stock <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <h3>Browse inventory</h3>
      <ul>
        <li>Search and filter by category, gender, or low stock only.</li>
        <li>See selling price, cost, margin %, and quantity. Low stock is highlighted.</li>
      </ul>
      <h3>Add or edit a product</h3>
      <ul>
        <li><strong>Add Product</strong> — name, category, gender, design, size, barcode, cost &amp; selling price, quantity, low-stock threshold, optional image.</li>
        <li><strong>Edit</strong> — update any field; use stock adjustment when you receive stock or correct counts.</li>
        <li><strong>Delete</strong> — removes the product (use carefully if it has sales history).</li>
      </ul>
      <div class="guide-callout">Keep sizes as separate products (e.g. same shoe in size 30 and 31). That keeps stock accurate per size.</div>
    </section>

    <!-- Categories -->
    <section class="guide-section" id="categories">
      <h2><i class="fa-solid fa-tag" aria-hidden="true"></i> Categories <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <p>Categories group products on the POS filters and in reports (e.g. School, Ladies, Preloved).</p>
      <ul>
        <li>Add a category from the Categories page.</li>
        <li>Delete only when no products still use it (or reassign products first).</li>
      </ul>
    </section>

    <!-- Locations -->
    <section class="guide-section" id="locations">
      <h2><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Locations <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <p>Each physical selling point (shop, market stall, pop-up) should be a location. Cashiers pick it at checkout so reports can split sales by place.</p>
      <ul>
        <li>Add active locations from Manage → Locations.</li>
        <li>Inactive / deleted locations stop appearing on new sales.</li>
      </ul>
    </section>

    <!-- Customers -->
    <section class="guide-section" id="customers">
      <h2><i class="fa-solid fa-address-book" aria-hidden="true"></i> Customers <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <ul>
        <li>Customers can be captured during POS checkout (optional) or added manually here.</li>
        <li>Store name, phone, shoe size, and notes for follow-up.</li>
        <li>Search by name or phone; <strong>Export CSV</strong> for marketing or backups.</li>
      </ul>
    </section>

    <!-- Staff -->
    <section class="guide-section" id="staff">
      <h2><i class="fa-solid fa-users" aria-hidden="true"></i> Staff accounts <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <ol class="guide-steps">
        <li>Go to Manage → Staff → create account (name, email, phone, password, optional PIN).</li>
        <li>Staff see only POS + User Guide after login.</li>
        <li>Use toggle to activate / deactivate an account without deleting it.</li>
      </ol>
      <div class="guide-callout">Deactivate leavers promptly so they cannot sign in.</div>
    </section>

    <!-- Targets -->
    <section class="guide-section" id="targets">
      <h2><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Sales targets <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <p>Set weekly unit and revenue targets (overall or per category). The page shows progress bars for the current week and a history of past targets.</p>
      <ul>
        <li>Create a target with week dates, optional category, unit goal, and revenue goal.</li>
        <li>Dashboard also surfaces target progress.</li>
      </ul>
    </section>

    <!-- Reports -->
    <section class="guide-section" id="reports">
      <h2><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Reports <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <div class="guide-table-wrap">
        <table class="guide-table">
          <thead><tr><th>Report</th><th>What it shows</th></tr></thead>
          <tbody>
            <tr><td><strong>Daily</strong></td><td>One day’s totals, breakdown by category / location / staff, and the sale list. Pick any date.</td></tr>
            <tr><td><strong>Weekly</strong></td><td>Monday–Sunday window (choose week start). Good for comparing against weekly targets.</td></tr>
            <tr><td><strong>Monthly</strong></td><td>Month-to-date or custom month performance.</td></tr>
            <tr><td><strong>Staff</strong></td><td>Who sold how much over a date range — useful for incentives.</td></tr>
            <tr><td><strong>Locations</strong></td><td>Revenue by shop / stall for a period.</td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Alerts -->
    <section class="guide-section" id="alerts">
      <h2><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Low stock alerts <span class="guide-badge guide-badge-owner">Owner</span></h2>
      <ul>
        <li>When quantity falls to or below a product’s low-stock threshold, an alert is logged.</li>
        <li>Open the warning icon in the top bar to review alerts.</li>
        <li>Restock via Products (edit quantity / stock adjust), then the badge clears as you mark alerts read.</li>
      </ul>
    </section>
    <?php else: ?>
    <section class="guide-section" id="owner-note">
      <h2><i class="fa-solid fa-lock" aria-hidden="true"></i> Owner-only tools <span class="guide-badge guide-badge-staff">Staff</span></h2>
      <p>Dashboard, sales history edits, inventory, reports, staff, customers, locations, and targets are managed by the owner. If you need a price change, stock count fix, or sale correction, ask the owner — do not invent workarounds at the till.</p>
    </section>
    <?php endif; ?>

    <!-- Tips -->
    <section class="guide-section" id="tips">
      <h2><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Tips &amp; troubleshooting <span class="guide-badge guide-badge-all">Everyone</span></h2>
      <h3>Selling smoothly</h3>
      <ul>
        <li>Confirm size with the customer before adding to cart.</li>
        <li>For MoMo, save the reference — it helps resolve payment disputes later.</li>
        <li>Add customer phone when they may return for another size or pair.</li>
      </ul>
      <h3>Common issues</h3>
      <div class="guide-table-wrap">
        <table class="guide-table">
          <thead><tr><th>Problem</th><th>What to try</th></tr></thead>
          <tbody>
            <tr><td>Product won’t add</td><td>It may be out of stock. Ask the owner to restock or check the correct size.</td></tr>
            <tr><td>Wrong price on POS</td><td>Only the owner can edit product prices. Complete the sale with a discount note if urgent, then correct later.</td></tr>
            <tr><td>Wrong sale recorded</td><td>Staff: tell the owner the sale ref. Owner: Edit or Delete (restores stock) from All Sales.</td></tr>
            <tr><td>Cannot log in</td><td>Check email/password; confirm the owner has not deactivated the account.</td></tr>
            <tr><td>Receipt won’t print</td><td>Allow pop-ups/printing for this site; try Print again from the receipt view.</td></tr>
            <tr><td>Page looks cut off on phone</td><td>Use the menu button for navigation; rotate to landscape for POS if needed.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="guide-callout ok">Need this on paper? Use <strong>Print guide</strong> at the top — the table of contents is hidden on print so the manual reads cleanly.</div>
    </section>
  </div>
</div>

<script>
(function () {
  const links = [...document.querySelectorAll('.guide-toc a')];
  const sections = links.map(a => document.querySelector(a.getAttribute('href'))).filter(Boolean);
  function setActive() {
    let current = sections[0];
    for (const s of sections) {
      if (s.getBoundingClientRect().top <= 120) current = s;
    }
    links.forEach(a => a.classList.toggle('is-active', a.getAttribute('href') === '#' + current.id));
  }
  window.addEventListener('scroll', setActive, { passive: true });
  setActive();
})();
</script>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
