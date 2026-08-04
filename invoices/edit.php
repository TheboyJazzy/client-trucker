<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('invoices/index.php'));
}

$stmt = $mysqli->prepare('SELECT * FROM invoices WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    set_flash('Invoice not found.', 'error');
    redirect(base_url('invoices/index.php'));
}

$errors = [];
$clientId = (int) $invoice['client_id'];
$projectId = (int) $invoice['project_id'];
$invoiceNumber = $invoice['invoice_number'];
$issueDate = $invoice['issue_date'];
$dueDate = $invoice['due_date'];
$amount = $invoice['amount'];
$status = $invoice['status'];
$notes = $invoice['notes'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = (int) post('client_id');
    $projectId = (int) post('project_id');
    $invoiceNumber = post('invoice_number');
    $issueDate = post('issue_date');
    $dueDate = post('due_date');
    $amount = post('amount');
    $status = post('status');
    $notes = post('notes');

    $validStatuses = ['unpaid', 'partially_paid', 'paid', 'overdue'];

    if ($clientId <= 0) {
        $errors['client_id'] = 'Select a client.';
    }
    if ($invoiceNumber === '') {
        $errors['invoice_number'] = 'Invoice number is required.';
    }
    if ($issueDate === '') {
        $errors['issue_date'] = 'Issue date is required.';
    }
    if ($dueDate === '') {
        $errors['due_date'] = 'Due date is required.';
    } elseif ($issueDate !== '' && $dueDate < $issueDate) {
        $errors['due_date'] = 'Due date cannot be before the issue date.';
    }
    if ($amount === '' || !is_numeric($amount) || (float) $amount <= 0) {
        $errors['amount'] = 'Enter a valid amount greater than zero.';
    }
    if (!in_array($status, $validStatuses, true)) {
        $errors['status'] = 'Select a valid status.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare('SELECT id FROM invoices WHERE invoice_number = ? AND id != ?');
        $stmt->bind_param('si', $invoiceNumber, $id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $errors['invoice_number'] = 'That invoice number is already in use.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $projectIdValue = $projectId > 0 ? $projectId : null;

        $stmt = $mysqli->prepare(
            'UPDATE invoices SET client_id = ?, project_id = ?, invoice_number = ?, issue_date = ?, due_date = ?, amount = ?, status = ?, notes = ? WHERE id = ?'
        );
        $stmt->bind_param('iisssdssi', $clientId, $projectIdValue, $invoiceNumber, $issueDate, $dueDate, $amount, $status, $notes, $id);
        $stmt->execute();
        $stmt->close();

        set_flash('Invoice updated.');
        redirect(base_url('invoices/view.php?id=' . $id));
    }
}

$clients = $mysqli->query('SELECT id, name FROM clients ORDER BY name ASC');
$projects = $mysqli->query(
    'SELECT projects.id, projects.name, clients.name AS client_name
     FROM projects JOIN clients ON clients.id = projects.client_id
     ORDER BY clients.name ASC, projects.name ASC'
);

$pageTitle = 'Edit Invoice';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="post" action="" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label for="client_id">Client *</label>
                <select id="client_id" name="client_id" required>
                    <?php while ($c = $clients->fetch_assoc()): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $clientId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <?php if (!empty($errors['client_id'])): ?><div class="field-error"><?= e($errors['client_id']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="project_id">Project (optional)</label>
                <select id="project_id" name="project_id">
                    <option value="">No specific project</option>
                    <?php while ($p = $projects->fetch_assoc()): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= $projectId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['client_name'] . ' — ' . $p['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="invoice_number">Invoice Number *</label>
                <input type="text" id="invoice_number" name="invoice_number" value="<?= e($invoiceNumber) ?>" required>
                <?php if (!empty($errors['invoice_number'])): ?><div class="field-error"><?= e($errors['invoice_number']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="amount">Amount *</label>
                <input type="number" step="0.01" id="amount" name="amount" value="<?= e($amount) ?>" required>
                <?php if (!empty($errors['amount'])): ?><div class="field-error"><?= e($errors['amount']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="issue_date">Issue Date *</label>
                <input type="date" id="issue_date" name="issue_date" value="<?= e($issueDate) ?>" required>
                <?php if (!empty($errors['issue_date'])): ?><div class="field-error"><?= e($errors['issue_date']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date *</label>
                <input type="date" id="due_date" name="due_date" value="<?= e($dueDate) ?>" required>
                <?php if (!empty($errors['due_date'])): ?><div class="field-error"><?= e($errors['due_date']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['unpaid', 'partially_paid', 'paid'] as $s): ?>
                    <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?= e($notes) ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= base_url('invoices/view.php?id=' . $id) ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
