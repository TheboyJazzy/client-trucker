<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = 'SELECT projects.id, projects.name, projects.status, projects.start_date, projects.end_date,
               clients.id AS client_id, clients.name AS client_name
        FROM projects
        JOIN clients ON clients.id = projects.client_id
        WHERE 1 = 1';
$types = '';
$params = [];

if ($search !== '') {
    $sql .= ' AND (projects.name LIKE ? OR clients.name LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

if ($statusFilter !== '') {
    $sql .= ' AND projects.status = ?';
    $types .= 's';
    $params[] = $statusFilter;
}

$sql .= ' ORDER BY projects.created_at DESC';

$stmt = $mysqli->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$projects = $stmt->get_result();
$stmt->close();

$pageTitle = 'Projects';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <h1>Projects</h1>
    <div class="actions">
        <a href="<?= base_url('projects/create.php') ?>" class="btn btn-primary">Add Project</a>
    </div>
</div>

<form method="get" action="" class="filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" placeholder="Project or client name" value="<?= e($search) ?>">
    </div>
    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">All</option>
            <?php foreach (['active', 'on_hold', 'completed', 'cancelled'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn">Filter</button>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
        <a href="<?= base_url('projects/index.php') ?>" class="btn">Clear</a>
    <?php endif; ?>
</form>

<table>
    <thead>
        <tr>
            <th>Project</th>
            <th>Client</th>
            <th>Status</th>
            <th>Start</th>
            <th>End</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($projects->num_rows === 0): ?>
            <tr><td colspan="6" class="table-empty">No projects found.</td></tr>
        <?php else: ?>
            <?php while ($project = $projects->fetch_assoc()): ?>
                <tr>
                    <td><a href="<?= base_url('projects/view.php?id=' . $project['id']) ?>"><?= e($project['name']) ?></a></td>
                    <td><a href="<?= base_url('clients/view.php?id=' . $project['client_id']) ?>"><?= e($project['client_name']) ?></a></td>
                    <td><?= status_badge($project['status']) ?></td>
                    <td><?= format_date($project['start_date']) ?></td>
                    <td><?= format_date($project['end_date']) ?></td>
                    <td class="actions-cell">
                        <a href="<?= base_url('projects/edit.php?id=' . $project['id']) ?>">Edit</a>
                        <a href="<?= base_url('projects/delete.php?id=' . $project['id']) ?>" class="js-confirm-delete" data-message="Delete this project and its milestones? This cannot be undone.">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
