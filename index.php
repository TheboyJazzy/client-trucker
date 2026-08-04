<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_guard.php';

$totalClients = (int) $mysqli->query('SELECT COUNT(*) AS c FROM clients')->fetch_assoc()['c'];
$activeProjects = (int) $mysqli->query("SELECT COUNT(*) AS c FROM projects WHERE status = 'active'")->fetch_assoc()['c'];
$unpaidInvoices = (int) $mysqli->query("SELECT COUNT(*) AS c FROM invoices WHERE status IN ('unpaid', 'partially_paid')")->fetch_assoc()['c'];
$overdueInvoices = (int) $mysqli->query("SELECT COUNT(*) AS c FROM invoices WHERE status IN ('unpaid', 'partially_paid') AND due_date < CURDATE()")->fetch_assoc()['c'];

$paidThisMonthResult = $mysqli->query(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM payments
     WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())"
);
$paidThisMonth = (float) $paidThisMonthResult->fetch_assoc()['total'];

$totalInvoiced = (float) $mysqli->query('SELECT COALESCE(SUM(amount), 0) AS total FROM invoices')->fetch_assoc()['total'];
$totalCollected = (float) $mysqli->query('SELECT COALESCE(SUM(amount), 0) AS total FROM payments')->fetch_assoc()['total'];
$totalOutstanding = $totalInvoiced - $totalCollected;

$recentInvoices = $mysqli->query(
    'SELECT invoices.id, invoices.invoice_number, invoices.amount, invoices.status, invoices.due_date,
            clients.name AS client_name
     FROM invoices
     JOIN clients ON clients.id = invoices.client_id
     ORDER BY invoices.issue_date DESC, invoices.id DESC
     LIMIT 5'
);

$upcomingMilestones = $mysqli->query(
    "SELECT milestones.id, milestones.title, milestones.due_date, milestones.status,
            projects.id AS project_id, projects.name AS project_name
     FROM milestones
     JOIN projects ON projects.id = milestones.project_id
     WHERE milestones.status != 'completed' AND milestones.due_date IS NOT NULL AND milestones.due_date >= CURDATE()
     ORDER BY milestones.due_date ASC
     LIMIT 5"
);

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="stats-row">
    <div class="stat-tile">
        <div class="stat-label">Total Clients</div>
        <div class="stat-value"><?= $totalClients ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Active Projects</div>
        <div class="stat-value"><?= $activeProjects ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Unpaid Invoices</div>
        <div class="stat-value"><?= $unpaidInvoices ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Paid This Month</div>
        <div class="stat-value"><?= format_currency($paidThisMonth) ?></div>
    </div>
    <div class="stat-tile">
        <div class="stat-label">Overdue Invoices</div>
        <div class="stat-value"><?= $overdueInvoices ?></div>
    </div>
</div>

<div class="form-row">
    <div style="flex: 2;">
        <div class="section-title">Recent Invoices</div>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Due</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentInvoices->num_rows === 0): ?>
                    <tr><td colspan="5" class="table-empty">No invoices yet.</td></tr>
                <?php else: ?>
                    <?php while ($inv = $recentInvoices->fetch_assoc()): ?>
                        <tr>
                            <td><a href="<?= base_url('invoices/view.php?id=' . $inv['id']) ?>"><?= e($inv['invoice_number']) ?></a></td>
                            <td><?= e($inv['client_name']) ?></td>
                            <td><?= format_currency($inv['amount']) ?></td>
                            <td><?= status_badge(invoice_display_status($inv)) ?></td>
                            <td><?= format_date($inv['due_date']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="section-title">Upcoming Milestones</div>
        <table>
            <thead>
                <tr>
                    <th>Milestone</th>
                    <th>Project</th>
                    <th>Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($upcomingMilestones->num_rows === 0): ?>
                    <tr><td colspan="4" class="table-empty">Nothing coming up.</td></tr>
                <?php else: ?>
                    <?php while ($m = $upcomingMilestones->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($m['title']) ?></td>
                            <td><a href="<?= base_url('projects/view.php?id=' . $m['project_id']) ?>"><?= e($m['project_name']) ?></a></td>
                            <td><?= format_date($m['due_date']) ?></td>
                            <td><?= status_badge($m['status']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="flex: 1;">
        <div class="section-title">Payment Summary</div>
        <div class="panel">
            <div class="detail-label">Total Invoiced</div>
            <div class="detail-value"><?= format_currency($totalInvoiced) ?></div>
            <div class="detail-label">Total Collected</div>
            <div class="detail-value"><?= format_currency($totalCollected) ?></div>
            <div class="detail-label">Outstanding Balance</div>
            <div class="detail-value"><?= format_currency($totalOutstanding) ?></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
