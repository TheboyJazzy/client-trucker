<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$clientFilter = (int) ($_GET['client_id'] ?? 0);

$sql = 'SELECT invoices.id, invoices.invoice_number, invoices.amount, invoices.status,
               invoices.issue_date, invoices.due_date,
               clients.id AS client_id, clients.name AS client_name
        FROM invoices
        JOIN clients ON clients.id = invoices.client_id
        WHERE 1 = 1';
$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (invoices.invoice_number LIKE ? OR clients.name LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== '') {
    $sql .= ' AND invoices.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

if ($clientFilter > 0) {
    $sql .= ' AND invoices.client_id = ?';
    $types .= 'i';
    $params[] = $clientFilter;
}

$sql .= ' ORDER BY invoices.issue_date DESC';

$stmt = $mysqli->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$invoices = $stmt->get_result();
$stmt->close();

$clients = $mysqli->query('SELECT id, name FROM clients ORDER BY name ASC');

$pageTitle = 'Invoices';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <h1>Invoices</h1>
    <div class="actions">
        <a href="<?= base_url('invoices/create.php') ?>" class="btn btn-primary">Add Invoice</a>
    </div>
</div>

<form method="get" action="" class="filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" placeholder="Invoice # or client" value="<?= e($search) ?>">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['unpaid', 'partially_paid', 'paid', 'overdue'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="client_id">Client</label>
        <select id="client_id" name="client_id">
            <option value="">All</option>
            <?php while ($c = $clients->fetch_assoc()): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $clientFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <button type="submit" class="btn">Filter</button>
    <?php if ($search !== '' || $statusFilter !== '' || $clientFilter > 0): ?>
        <a href="<?= base_url('invoices/index.php') ?>" class="btn">Clear</a>
    <?php endif; ?>
</form>

<table>
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Client</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Issued</th>
            <th>Due</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($invoices->num_rows === 0): ?>
            <tr><td colspan="7" class="table-empty">No invoices found.</td></tr>
        <?php else: ?>
            <?php while ($invoice = $invoices->fetch_assoc()): ?>
                <tr>
                    <td><a href="<?= base_url('invoices/view.php?id=' . $invoice['id']) ?>"><?= e($invoice['invoice_number']) ?></a></td>
                    <td><a href="<?= base_url('clients/view.php?id=' . $invoice['client_id']) ?>"><?= e($invoice['client_name']) ?></a></td>
                    <td><?= format_currency($invoice['amount']) ?></td>
                    <td><?= status_badge(invoice_display_status($invoice)) ?></td>
                    <td><?= format_date($invoice['issue_date']) ?></td>
                    <td><?= format_date($invoice['due_date']) ?></td>
                    <td class="actions-cell">
                        <a href="<?= base_url('invoices/edit.php?id=' . $invoice['id']) ?>">Edit</a>
                        <a href="<?= base_url('invoices/delete.php?id=' . $invoice['id']) ?>" class="js-confirm-delete" data-message="Delete this invoice and its payment records?">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
