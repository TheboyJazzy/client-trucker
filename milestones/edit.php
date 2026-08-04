<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('projects/index.php'));
}

$stmt = $mysqli->prepare(
    'SELECT milestones.*, projects.name AS project_name
     FROM milestones JOIN projects ON projects.id = milestones.project_id
     WHERE milestones.id = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$milestone = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$milestone) {
    set_flash('Milestone not found.', 'error');
    redirect(base_url('projects/index.php'));
}

$projectId = (int) $milestone['project_id'];
$errors = [];
$title = $milestone['title'];
$description = $milestone['description'];
$dueDate = $milestone['due_date'];
$status = $milestone['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post('title');
    $description = post('description');
    $dueDate = post('due_date');
    $status = post('status');

    $validStatuses = ['pending', 'in_progress', 'completed'];

    if ($title === '') {
        $errors['title'] = 'Title is required.';
    }
    if (!in_array($status, $validStatuses, true)) {
        $errors['status'] = 'Select a valid status.';
    }

    if (empty($errors)) {
        $dueDateValue = $dueDate !== '' ? $dueDate : null;

        $stmt = $mysqli->prepare(
            'UPDATE milestones SET title = ?, description = ?, due_date = ?, status = ? WHERE id = ?'
        );
        $stmt->bind_param('ssssi', $title, $description, $dueDateValue, $status, $id);
        $stmt->execute();
        $stmt->close();

        set_flash('Milestone updated.');
        redirect(base_url('projects/view.php?id=' . $projectId));
    }
}

$pageTitle = 'Edit Milestone';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <p class="text-muted">Project: <?= e($milestone['project_name']) ?></p>
    <form method="post" action="">
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required>
            <?php if (!empty($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($description) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="<?= e($dueDate) ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['pending', 'in_progress', 'completed'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= base_url('projects/view.php?id=' . $projectId) ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
