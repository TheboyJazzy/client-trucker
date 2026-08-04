<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('projects/index.php'));
}

$stmt = $mysqli->prepare(
    'SELECT projects.*, clients.name AS client_name, clients.id AS client_id
     FROM projects JOIN clients ON clients.id = projects.client_id
     WHERE projects.id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    set_flash('Project not found.', 'error');
    redirect(base_url('projects/index.php'));
}

$stmt = $mysqli->prepare('SELECT * FROM milestones WHERE project_id = ? ORDER BY due_date IS NULL, due_date ASC');
$stmt->bind_param('i', $id);
$stmt->execute();
$milestones = $stmt->get_result();
$stmt->close();

$pageTitle = $project['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <h1><?= e($project['name']) ?></h1>
    <div class="actions">
        <a href="<?= base_url('projects/edit.php?id=' . $project['id']) ?>" class="btn">Edit</a>
        <a href="<?= base_url('projects/index.php') ?>" class="btn">Back to Projects</a>
    </div>
</div>

<div class="panel">
    <div class="detail-grid">
        <div>
            <div class="detail-label">Client</div>
            <div class="detail-value"><a href="<?= base_url('clients/view.php?id=' . $project['client_id']) ?>"><?= e($project['client_name']) ?></a></div>
        </div>
        <div>
            <div class="detail-label">Status</div>
            <div class="detail-value"><?= status_badge($project['status']) ?></div>
        </div>
        <div>
            <div class="detail-label">Start Date</div>
            <div class="detail-value"><?= format_date($project['start_date']) ?></div>
        </div>
        <div>
            <div class="detail-label">End Date</div>
            <div class="detail-value"><?= format_date($project['end_date']) ?></div>
        </div>
        <div>
            <div class="detail-label">Budget</div>
            <div class="detail-value"><?= $project['budget'] !== null ? format_currency($project['budget']) : '—' ?></div>
        </div>
    </div>
    <?php if (!empty($project['description'])): ?>
        <div class="detail-label">Description</div>
        <div class="detail-value"><?= nl2br(e($project['description'])) ?></div>
    <?php endif; ?>
</div>

<div class="section-title">Milestones</div>
<?php if ($milestones->num_rows > 5): ?>
    <div class="form-group">
        <input type="search" placeholder="Filter milestones..." data-table-filter="#milestones-table">
    </div>
<?php endif; ?>
<table id="milestones-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Due Date</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($milestones->num_rows === 0): ?>
            <tr><td colspan="4" class="table-empty">No milestones yet.</td></tr>
        <?php else: ?>
            <?php while ($m = $milestones->fetch_assoc()): ?>
                <tr>
                    <td><?= e($m['title']) ?></td>
                    <td><?= format_date($m['due_date']) ?></td>
                    <td><?= status_badge($m['status']) ?></td>
                    <td class="actions-cell">
                        <a href="<?= base_url('milestones/edit.php?id=' . $m['id']) ?>">Edit</a>
                        <a href="<?= base_url('milestones/delete.php?id=' . $m['id'] . '&project_id=' . $id) ?>" class="js-confirm-delete" data-message="Delete this milestone?">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
<div class="form-actions">
    <a href="<?= base_url('milestones/create.php?project_id=' . $id) ?>" class="btn btn-small">Add Milestone</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
