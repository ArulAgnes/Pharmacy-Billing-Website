<?php

require_once 'config.php';

$conn = getDBConnection();

$result = $conn->query("
    SELECT s.*, 
           COUNT(si.id) as item_count
    FROM sales s
    LEFT JOIN sale_items si ON si.sale_id = s.id
    GROUP BY s.id
    ORDER BY s.id DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sales List — Pharmacy Billing</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="rx-styles.css" />
  <style>
    /* ── Page-specific overrides ── */
    .sl-header {
      padding: 22px 28px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      background: var(--surface);
    }
    .sl-title {
      font-family: 'Syne', sans-serif;
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--navy);
    }
    .sl-subtitle {
      font-size: .75rem;
      color: var(--muted);
      margin-top: 2px;
    }
    .new-sale-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: var(--teal);
      color: #fff;
      border: none;
      border-radius: var(--r-sm);
      padding: 9px 18px;
      font-size: .82rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background .15s;
    }
    .new-sale-btn:hover { background: #0891b2; }

    .sl-body { padding: 24px 28px 40px; }

    /* Search & filter bar */
    .sl-toolbar {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }
    .sl-search {
      flex: 1;
      max-width: 320px;
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      padding: 8px 12px;
      font-size: .82rem;
      font-family: 'Inter', sans-serif;
      color: var(--text);
      background: #fafbfd;
    }
    .sl-search:focus {
      outline: none;
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(14,165,160,.12);
    }
    .sl-count {
      font-size: .78rem;
      color: var(--muted);
      margin-left: auto;
    }

    /* Table */
    .sl-table-wrap {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--r-lg);
      box-shadow: var(--sh-card);
      overflow: hidden;
    }
    .sl-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .8rem;
    }
    .sl-table thead tr {
      background: var(--navy);
      color: rgba(255,255,255,.9);
    }
    .sl-table thead th {
      padding: 11px 14px;
      font-weight: 600;
      letter-spacing: .04em;
      font-size: .7rem;
      text-transform: uppercase;
      white-space: nowrap;
      text-align: left;
    }
    .sl-table thead th:last-child { text-align: center; }
    .sl-table tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .12s;
    }
    .sl-table tbody tr:last-child { border-bottom: none; }
    .sl-table tbody tr:hover { background: #f0fdfb; }
    .sl-table tbody tr:nth-child(even) { background: #f9fbfd; }
    .sl-table tbody tr:nth-child(even):hover { background: #f0fdfb; }
    .sl-table td { padding: 10px 14px; vertical-align: middle; }
    .sl-table td:last-child { text-align: center; }

    .inv-no {
      font-family: 'Syne', sans-serif;
      font-weight: 700;
      color: var(--navy);
      font-size: .82rem;
    }
    .patient-name { font-weight: 600; color: var(--text); }
    .doctor-name  { color: var(--teal); font-size: .78rem; }
    .grand-total  {
      font-weight: 700;
      color: var(--emerald);
      font-size: .85rem;
    }

    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .status-saved     { background: #fef3c7; color: #92400e; }
    .status-submitted { background: #d1fae5; color: #065f46; }

    .action-cell { display: flex; align-items: center; gap: 6px; justify-content: center; }
    .tbl-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 12px;
      border-radius: 6px;
      font-size: .72rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      text-decoration: none;
      transition: all .15s;
    }
    .tbl-btn-print {
      background: var(--navy);
      color: #fff;
    }
    .tbl-btn-print:hover { background: var(--navy-light); }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }
    .empty-state svg { margin-bottom: 12px; opacity: .3; }
    .empty-state p { font-size: .9rem; }

    /* Summary cards */
    .sl-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--r-md);
      padding: 14px 18px;
      box-shadow: var(--sh-card);
    }
    .stat-label {
      font-size: .68rem;
      text-transform: uppercase;
      letter-spacing: .07em;
      color: var(--muted);
      font-weight: 600;
      margin-bottom: 5px;
    }
    .stat-value {
      font-family: 'Syne', sans-serif;
      font-size: 1.4rem;
      font-weight: 800;
      color: var(--navy);
    }
    .stat-value.teal  { color: var(--teal); }
    .stat-value.green { color: var(--emerald); }
  </style>
</head>
<body>

<!-- ━━━━━━━━━━━━━━━━━━━━━━ SIDEBAR ━━━━━━━━━━━━━━━━━━━━━━ -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
    </div>
    <div>
      <div class="brand-name">Pharmacy</div>
      <div class="brand-sub">Billing System</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="invoice.html" class="nav-item">
      <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      New Sale
    </a>
    <a href="sales_list.php" class="nav-item active">
      <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
             M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
      </svg>
      Sales List
    </a>
  </nav>
  <div class="sidebar-bottom">
    <a href="invoice.html" class="sales-list-btn">
      <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      New Sale
    </a>
  </div>
</aside>

<div class="main-wrap">
  <div class="sl-header">
    <div>
      <div class="sl-title">Sales List</div>
      <div class="sl-subtitle">All recorded invoices</div>
    </div>
    <a href="invoice.html" class="new-sale-btn">
      <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
      </svg>
      New Sale
    </a>
  </div>

  <div class="sl-body">

    <?php
      // Compute summary stats
      $conn2 = getDBConnection();
      $statsRow = $conn2->query("SELECT COUNT(*) as total_bills, COALESCE(SUM(grand_total),0) as total_revenue, COALESCE(SUM(CASE WHEN status='submitted' THEN 1 ELSE 0 END),0) as submitted FROM sales")->fetch_assoc();
    ?>

    <!-- Stats -->
    <div class="sl-stats">
      <div class="stat-card">
        <div class="stat-label">Total Bills</div>
        <div class="stat-value teal"><?= number_format($statsRow['total_bills']) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value green">₹<?= number_format($statsRow['total_revenue'], 2) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Submitted</div>
        <div class="stat-value"><?= number_format($statsRow['submitted']) ?></div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="sl-toolbar">
      <input type="text" class="sl-search" placeholder="Search invoice, patient, doctor…" id="searchInput" oninput="filterTable()" />
      <span class="sl-count" id="rowCount"></span>
    </div>

    <!-- Table -->
    <div class="sl-table-wrap">
      <?php if ($result && $result->num_rows > 0): ?>
      <table class="sl-table" id="salesTable">
        <thead>
          <tr>
            <th>#</th>
            <th>Invoice No</th>
            <th>Date</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Items</th>
            <th>Lab</th>
            <th>Doctor Fee</th>
            <th>Injection</th>
            <th>Nursing</th>
            <th>Discount</th>
            <th>Grand Total</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td style="color:var(--muted);font-size:.75rem;"><?= $i++ ?></td>
            <td><span class="inv-no"><?= htmlspecialchars($row['invoice_no']) ?></span></td>
            <td style="white-space:nowrap;color:var(--muted);font-size:.78rem;">
              <?= date('d M Y', strtotime($row['invoice_date'])) ?>
            </td>
            <td>
              <div class="patient-name"><?= htmlspecialchars($row['patient_name']) ?></div>
              <?php if ($row['patient_phone']): ?>
              <div style="font-size:.72rem;color:var(--muted);"><?= htmlspecialchars($row['patient_phone']) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($row['doctor_name']): ?>
              <span class="doctor-name"><?= htmlspecialchars($row['doctor_name']) ?></span>
              <?php else: ?>
              <span style="color:var(--faint);font-size:.75rem;">—</span>
              <?php endif; ?>
              <?php if ($row['reminder']): ?>
              <span style="display:inline-block;margin-left:4px;width:7px;height:7px;background:var(--amber);border-radius:50%;" title="Remainder set"></span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;color:var(--muted);"><?= (int)$row['item_count'] ?></td>
            <td style="text-align:right;">
              <?php if ($row['lab_charge'] > 0): ?>
                <span style="color:var(--text);">₹<?= number_format($row['lab_charge'], 2) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="text-align:right;">
              <?php if ($row['doctor_charge'] > 0): ?>
                <span style="color:var(--text);">₹<?= number_format($row['doctor_charge'], 2) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="text-align:right;">
              <?php if ($row['injection_charge'] > 0): ?>
                <span style="color:var(--text);">₹<?= number_format($row['injection_charge'], 2) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="text-align:right;">
              <?php if ($row['nursing_charge'] > 0): ?>
                <span style="color:var(--text);">₹<?= number_format($row['nursing_charge'], 2) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td style="text-align:right;color:var(--red);font-weight:600;">
              <?php if ($row['total_discount'] > 0): ?>
                -₹<?= number_format($row['total_discount'], 2) ?>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><span class="grand-total">₹<?= number_format($row['grand_total'], 2) ?></span></td>
            <td>
              <span class="status-badge status-<?= $row['status'] ?>">
                <?= ucfirst($row['status']) ?>
              </span>
            </td>
            <td>
              <div class="action-cell">
                <a href="print_invoice.php?id=<?= $row['id'] ?>" class="tbl-btn tbl-btn-print" target="_blank">
                  <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2
                         m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm1-12V5a2 2 0 012-2h2l3 3v3"/>
                  </svg>
                  Print
                </a>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="empty-state">
        <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
               M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
        </svg>
        <p>No sales found. <a href="invoice.html" style="color:var(--teal);">Create your first invoice →</a></p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  const rows = document.querySelectorAll('#salesTable tbody tr');
  let visible = 0;
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    const show = text.includes(q);
    row.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('rowCount').textContent = visible + ' record' + (visible !== 1 ? 's' : '');
}

// Init count
window.addEventListener('DOMContentLoaded', () => {
  const rows = document.querySelectorAll('#salesTable tbody tr');
  const el = document.getElementById('rowCount');
  if (el) el.textContent = rows.length + ' record' + (rows.length !== 1 ? 's' : '');
});
</script>
</body>
</html>
