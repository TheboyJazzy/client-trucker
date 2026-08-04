<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
$projectId = (int) ($_GET['project_id'] ?? 0);

if ($id > 0) {
    $stmt = $mysqli->prepare('DELETE FROM milestones WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    set_flash('Milestone deleted.');
}

if ($projectId > 0) {
    redirect(base_url('projects/view.php?id=' . $projectId));
}

redirect(base_url('projects/index.php'));
