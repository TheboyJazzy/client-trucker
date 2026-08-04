<?php
// Shared helper functions used across the app.

function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function format_currency($amount)
{
    return '$' . number_format((float) $amount, 2);
}

function format_date($date)
{
    if (empty($date)) {
        return '—';
    }
    return date('M j, Y', strtotime($date));
}

function status_badge($status)
{
    $label = ucwords(str_replace('_', ' ', $status));
    $class = 'badge badge-' . str_replace('_', '-', $status);
    return '<span class="' . $class . '">' . e($label) . '</span>';
}

// An invoice is treated as overdue for display purposes when it is still
// unpaid (or partially paid) and its due date has passed. The stored
// `status` column is left as-is; this only affects what the UI shows.
function invoice_display_status($invoice)
{
    if (in_array($invoice['status'], ['unpaid', 'partially_paid'], true)
        && $invoice['due_date'] !== null
        && $invoice['due_date'] < date('Y-m-d')
    ) {
        return 'overdue';
    }
    return $invoice['status'];
}

function redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function set_flash($message, $type = 'success')
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function old($key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old()
{
    unset($_SESSION['old']);
}

function post($key, $default = '')
{
    return trim($_POST[$key] ?? $default);
}

function base_url($path = '')
{
    return '/client-tracker/' . ltrim($path, '/');
}

// Recomputes an invoice's paid/unpaid/partially_paid status from its
// payment records. Called whenever a payment is added or removed.
function recalculate_invoice_status(mysqli $mysqli, int $invoiceId)
{
    $stmt = $mysqli->prepare('SELECT amount FROM invoices WHERE id = ?');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$invoice) {
        return;
    }

    $stmt = $mysqli->prepare('SELECT COALESCE(SUM(amount), 0) AS total_paid FROM payments WHERE invoice_id = ?');
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $totalPaid = (float) $stmt->get_result()->fetch_assoc()['total_paid'];
    $stmt->close();

    if ($totalPaid <= 0) {
        $status = 'unpaid';
    } elseif ($totalPaid >= (float) $invoice['amount']) {
        $status = 'paid';
    } else {
        $status = 'partially_paid';
    }

    $stmt = $mysqli->prepare('UPDATE invoices SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $status, $invoiceId);
    $stmt->execute();
    $stmt->close();
}
