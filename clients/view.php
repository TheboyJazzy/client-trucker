<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('clients/index.php'));
}

$stmt = $mysqli->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$client) {
    set_flash('Client not found.', 'error');
    redirect(base_url('clients/index.php'));
}

$stmt = $mysqli->prepare('SELECT id, name, status, start_date, end_date FROM projects WHERE client_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$projects = $stmt->get_result();
$stmt->close();

$stmt = $mysqli->prepare('SELECT id, invoice_number, amount, status, due_date FROM invoices WHERE client_id = ? ORDER BY issue_date DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$invoices = $stmt->get_result();
$stmt->close();

$pageTitle = $client['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <h1><?= e($client['name']) ?></h1>
    <div class="actions">
        <a href="<?= base_url('clients/edit.php?id=' . $client['id']) ?>" class="btn">Edit</a>
        <a href="<?= base_url('clients/index.php') ?>" class="btn">Back to Clients</a>
    </div>
</div>

<div class="panel">
    <div class="detail-grid">
        <div>
            <div class="detail-label">Company</div>
            <div class="detail-value"><?= e($client['company'] ?: '—') ?></div>
        </div>
        <div>
            <div class="detail-label">Email</div>
            <div class="detail-value"><?= e($client['email'] ?: '—') ?></div>
        </div>
        <div>
            <div class="detail-label">Phone</div>
            <div class="detail-value"><?= e($client['phone'] ?: '—') ?></div>
        </div>
        <div>
            <div class="detail-label">Address</div>
            <div class="detail-value"><?= e($client['address'] ?: '—') ?></div>
        </div>
    </div>
    <?php if (!empty($client['notes'])): ?>
        <div class="detail-label">Notes</div>
        <div class="detail-value"><?= nl2br(e($client['notes'])) ?></div>
    <?php endif; ?>
</div>

<div class="section-title">Projects</div>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Start</th>
            <th>End</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($projects->num_rows === 0): ?>
            <tr><td colspan="5" class="table-empty">No projects yet.</td></tr>
        <?php else: ?>
            <?php while ($project = $projects->fetch_assoc()): ?>
                <tr>
                    <td><a href="<?= base_url('projects/view.php?id=' . $project['id']) ?>"><?= e($project['name']) ?></a></td>
                    <td><?= status_badge($project['status']) ?></td>
                    <td><?= format_date($project['start_date']) ?></td>
                    <td><?= format_date($project['end_date']) ?></td>
                    <td class="actions-cell"><a href="<?= base_url('projects/edit.php?id=' . $project['id']) ?>">Edit</a></td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
<div class="form-actions">
    <a href="<?= base_url('projects/create.php?client_id=' . $client['id']) ?>" class="btn btn-small">Add Project</a>
</div>

<div class="section-title">Invoices</div>
<table>
    <thead>
        <tr>
            <th>Invoice #</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Due</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($invoices->num_rows === 0): ?>
            <tr><td colspan="5" class="table-empty">No invoices yet.</td></tr>
        <?php else: ?>
            <?php while ($invoice = $invoices->fetch_assoc()): ?>
                <tr>
                    <td><a href="<?= base_url('invoices/view.php?id=' . $invoice['id']) ?>"><?= e($invoice['invoice_number']) ?></a></td>
                    <td><?= format_currency($invoice['amount']) ?></td>
                    <td><?= status_badge($invoice['status']) ?></td>
                    <td><?= format_date($invoice['due_date']) ?></td>
                    <td class="actions-cell"><a href="<?= base_url('invoices/edit.php?id=' . $invoice['id']) ?>">Edit</a></td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
<div class="form-actions">
    <a href="<?= base_url('invoices/create.php?client_id=' . $client['id']) ?>" class="btn btn-small">Add Invoice</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
