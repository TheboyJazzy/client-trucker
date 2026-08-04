<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('invoices/index.php'));
}

$errors = [];
$paymentAmount = '';
$paymentDate = date('Y-m-d');
$paymentMethod = 'bank_transfer';
$paymentNotes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentAmount = post('amount');
    $paymentDate = post('payment_date');
    $paymentMethod = post('method');
    $paymentNotes = post('notes');

    $validMethods = ['bank_transfer', 'card', 'cash', 'paypal', 'other'];

    if ($paymentAmount === '' || !is_numeric($paymentAmount) || (float) $paymentAmount <= 0) {
        $errors['amount'] = 'Enter a valid amount greater than zero.';
    }
    if ($paymentDate === '') {
        $errors['payment_date'] = 'Payment date is required.';
    }
    if (!in_array($paymentMethod, $validMethods, true)) {
        $errors['method'] = 'Select a valid payment method.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare(
            'INSERT INTO payments (invoice_id, amount, payment_date, method, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('idsss', $id, $paymentAmount, $paymentDate, $paymentMethod, $paymentNotes);
        $stmt->execute();
        $stmt->close();

        recalculate_invoice_status($mysqli, $id);

        set_flash('Payment recorded.');
        redirect(base_url('invoices/view.php?id=' . $id));
    }
}

$stmt = $mysqli->prepare(
    'SELECT invoices.*, clients.name AS client_name, clients.id AS client_id,
            projects.name AS project_name, projects.id AS project_id
     FROM invoices
     JOIN clients ON clients.id = invoices.client_id
     LEFT JOIN projects ON projects.id = invoices.project_id
     WHERE invoices.id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    set_flash('Invoice not found.', 'error');
    redirect(base_url('invoices/index.php'));
}

$stmt = $mysqli->prepare('SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC');
$stmt->bind_param('i', $id);
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();

$totalPaid = 0.0;
$paymentRows = [];
while ($p = $payments->fetch_assoc()) {
    $totalPaid += (float) $p['amount'];
    $paymentRows[] = $p;
}
$balance = (float) $invoice['amount'] - $totalPaid;

$pageTitle = $invoice['invoice_number'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <h1><?= e($invoice['invoice_number']) ?></h1>
    <div class="actions">
        <a href="<?= base_url('invoices/edit.php?id=' . $invoice['id']) ?>" class="btn">Edit</a>
        <a href="<?= base_url('invoices/index.php') ?>" class="btn">Back to Invoices</a>
    </div>
</div>

<div class="panel">
    <div class="detail-grid">
        <div>
            <div class="detail-label">Client</div>
            <div class="detail-value"><a href="<?= base_url('clients/view.php?id=' . $invoice['client_id']) ?>"><?= e($invoice['client_name']) ?></a></div>
        </div>
        <div>
            <div class="detail-label">Project</div>
            <div class="detail-value">
                <?php if ($invoice['project_id']): ?>
                    <a href="<?= base_url('projects/view.php?id=' . $invoice['project_id']) ?>"><?= e($invoice['project_name']) ?></a>
                <?php else: ?>
                    —
                <?php endif; ?>
            </div>
        </div>
        <div>
            <div class="detail-label">Status</div>
            <div class="detail-value"><?= status_badge(invoice_display_status($invoice)) ?></div>
        </div>
        <div>
            <div class="detail-label">Amount</div>
            <div class="detail-value"><?= format_currency($invoice['amount']) ?></div>
        </div>
        <div>
            <div class="detail-label">Issue Date</div>
            <div class="detail-value"><?= format_date($invoice['issue_date']) ?></div>
        </div>
        <div>
            <div class="detail-label">Due Date</div>
            <div class="detail-value"><?= format_date($invoice['due_date']) ?></div>
        </div>
        <div>
            <div class="detail-label">Paid</div>
            <div class="detail-value"><?= format_currency($totalPaid) ?></div>
        </div>
        <div>
            <div class="detail-label">Balance</div>
            <div class="detail-value"><?= format_currency($balance) ?></div>
        </div>
    </div>
    <?php if (!empty($invoice['notes'])): ?>
        <div class="detail-label">Notes</div>
        <div class="detail-value"><?= nl2br(e($invoice['notes'])) ?></div>
    <?php endif; ?>
</div>

<div class="section-title">Payments</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Notes</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($paymentRows)): ?>
            <tr><td colspan="5" class="table-empty">No payments recorded yet.</td></tr>
        <?php else: ?>
            <?php foreach ($paymentRows as $p): ?>
                <tr>
                    <td><?= format_date($p['payment_date']) ?></td>
                    <td><?= format_currency($p['amount']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $p['method']))) ?></td>
                    <td><?= e($p['notes'] ?: '—') ?></td>
                    <td class="actions-cell">
                        <a href="<?= base_url('payments/delete.php?id=' . $p['id'] . '&invoice_id=' . $id) ?>" class="js-confirm-delete" data-message="Delete this payment record?">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="panel">
    <div class="panel-title">Record a Payment</div>
    <form method="post" action="" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Amount *</label>
                <input type="number" step="0.01" id="amount" name="amount" value="<?= e($paymentAmount) ?>" required>
                <?php if (!empty($errors['amount'])): ?><div class="field-error"><?= e($errors['amount']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="payment_date">Date *</label>
                <input type="date" id="payment_date" name="payment_date" value="<?= e($paymentDate) ?>" required>
                <?php if (!empty($errors['payment_date'])): ?><div class="field-error"><?= e($errors['payment_date']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="method">Method</label>
                <select id="method" name="method">
                    <?php foreach (['bank_transfer', 'card', 'cash', 'paypal', 'other'] as $m): ?>
                        <option value="<?= e($m) ?>" <?= $paymentMethod === $m ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $m))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <input type="text" id="notes" name="notes" value="<?= e($paymentNotes) ?>">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Payment</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
