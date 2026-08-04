<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(base_url('clients/index.php'));
}

$stmt = $mysqli->prepare('SELECT * FROM clients WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$client) {
    set_flash('Client not found.', 'error');
    redirect(base_url('clients/index.php'));
}

$errors = [];
$name = $client['name'];
$company = $client['company'];
$email = $client['email'];
$phone = $client['phone'];
$address = $client['address'];
$notes = $client['notes'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = post('name');
    $company = post('company');
    $email = post('email');
    $phone = post('phone');
    $address = post('address');
    $notes = post('notes');

    if ($name === '') {
        $errors['name'] = 'Client name is required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare(
            'UPDATE clients SET name = ?, company = ?, email = ?, phone = ?, address = ?, notes = ? WHERE id = ?'
        );
        $stmt->bind_param('ssssssi', $name, $company, $email, $phone, $address, $notes, $id);
        $stmt->execute();
        $stmt->close();

        set_flash('Client updated.');
        redirect(base_url('clients/view.php?id=' . $id));
    }
}

$pageTitle = 'Edit Client';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <form method="post" action="" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?= e($name) ?>" required>
                <?php if (!empty($errors['name'])): ?><div class="field-error"><?= e($errors['name']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="company">Company</label>
                <input type="text" id="company" name="company" value="<?= e($company) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>">
                <?php if (!empty($errors['email'])): ?><div class="field-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?= e($phone) ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?= e($address) ?>">
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes"><?= e($notes) ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?= base_url('clients/view.php?id=' . $id) ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
