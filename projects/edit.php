<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('projects/index.php'));
}

$stmt = $mysqli->prepare('SELECT * FROM projects WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
    set_flash('Project not found.', 'error');
    redirect(base_url('projects/index.php'));
}

$errors = [];
$clientId = (int) $project['client_id'];
$name = $project['name'];
$description = $project['description'];
$status = $project['status'];
$startDate = $project['start_date'];
$endDate = $project['end_date'];
$budget = $project['budget'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = (int) post('client_id');
    $name = post('name');
    $description = post('description');
    $status = post('status');
    $startDate = post('start_date');
    $endDate = post('end_date');
    $budget = post('budget');

    $validStatuses = ['active', 'on_hold', 'completed', 'cancelled'];

    if ($clientId <= 0) {
        $errors['client_id'] = 'Select a client.';
    }
    if ($name === '') {
        $errors['name'] = 'Project name is required.';
    }
    if (!in_array($status, $validStatuses, true)) {
        $errors['status'] = 'Select a valid status.';
    }
    if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
        $errors['end_date'] = 'End date cannot be before the start date.';
    }
    if ($budget !== '' && !is_numeric($budget)) {
        $errors['budget'] = 'Budget must be a number.';
    }

    if (empty($errors)) {
        $startDateValue = $startDate !== '' ? $startDate : null;
        $endDateValue = $endDate !== '' ? $endDate : null;
        $budgetValue = $budget !== '' ? $budget : null;

        $stmt = $mysqli->prepare(
            'UPDATE projects SET client_id = ?, name = ?, description = ?, status = ?, start_date = ?, end_date = ?, budget = ? WHERE id = ?'
        );
        $stmt->bind_param('isssssdi', $clientId, $name, $description, $status, $startDateValue, $endDateValue, $budgetValue, $id);
        $stmt->execute();
        $stmt->close();

        set_flash('Project updated.');
        redirect(base_url('projects/view.php?id=' . $id));
    }
}

$clients = $mysqli->query('SELECT id, name FROM clients ORDER BY name ASC');

$pageTitle = 'Edit Project';
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
                <label for="name">Project Name *</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" required>
                <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($description) ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php foreach (['active', 'on_hold', 'completed', 'cancelled'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="budget">Budget</label>
                <input type="number" step="0.01" id="budget" name="budget" value="<?= e($budget) ?>">
                <?php if (!empty($errors['budget'])): ?><div class="field-error"><?= e($errors['budget']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?= e($startDate) ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?= e($endDate) ?>">
                <?php if (!empty($errors['end_date'])): ?><div class="field-error"><?= e($errors['end_date']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= base_url('projects/view.php?id=' . $id) ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
