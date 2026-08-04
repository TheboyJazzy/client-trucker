<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
$invoiceId = (int) ($_GET['invoice_id'] ?? 0);

if ($id > 0) {
    $stmt = $mysqli->prepare('DELETE FROM payments WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    if ($invoiceId > 0) {
        recalculate_invoice_status($mysqli, $invoiceId);
    }

    set_flash('Payment deleted.');
}

if ($invoiceId > 0) {
    redirect(base_url('invoices/view.php?id=' . $invoiceId));
}

redirect(base_url('invoices/index.php'));
