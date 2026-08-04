<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $mysqli->prepare('DELETE FROM clients WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        set_flash('Client deleted.');
    } catch (mysqli_sql_exception $e) {
        set_flash('This client has projects or invoices and cannot be deleted. Remove those first.', 'error');
    }
}

redirect(base_url('clients/index.php'));
