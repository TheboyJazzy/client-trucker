<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$projectId = (int) ($_GET['project_id'] ?? post('project_id'));
if ($projectId <= 0) {
    redirect(base_url('projects/index.php'));
}

$stmt = $mysqli->prepare('SELECT id, name FROM projects WHERE id = ?');
$stmt->bind_param('i', $projectId);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    set_flash('Project not found.', 'error');
    redirect(base_url('projects/index.php'));
}

$errors = [];
$title = '';
$description = '';
$dueDate = '';
$status = 'pending';

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
            'INSERT INTO milestones (project_id, title, description, due_date, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issss', $projectId, $title, $description, $dueDateValue, $status);
        $stmt->execute();
        $stmt->close();

        set_flash('Milestone added.');
        redirect(base_url('projects/view.php?id=' . $projectId));
    }
}

$pageTitle = 'Add Milestone';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <p class="text-muted">Project: <?= e($project['name']) ?></p>
    <form method="post" action="">
        <input type="hidden" name="project_id" value="<?= (int) $projectId ?>">
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
            <button type="submit" class="btn btn-primary">Save Milestone</button>
            <a href="<?= base_url('projects/view.php?id=' . $projectId) ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
