<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_guard.php';

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $mysqli->prepare(
        'SELECT id, name, company, email, phone, created_at FROM clients
         WHERE name LIKE ? OR company LIKE ? OR email LIKE ?
         ORDER BY name ASC'
    );
    $stmt->bind_param('sss', $like, $like, $like);
} else {
    $stmt = $mysqli->prepare(
        'SELECT id, name, company, email, phone, created_at FROM clients ORDER BY name ASC'
    );
}

$stmt->execute();
$clients = $stmt->get_result();
$stmt->close();

$pageTitle = 'Clients';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="content-header">
    <h1>Clients</h1>
    <div class="actions">
        <a href="<?= base_url('clients/create.php') ?>" class="btn btn-primary">Add Client</a>
    </div>
</div>

<form method="get" action="" class="filter-bar">
    <div class="form-group">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" placeholder="Name, company, or email" value="<?= e($search) ?>">
    </div>
    <button type="submit" class="btn">Search</button>
    <?php if ($search !== ''): ?>
        <a href="<?= base_url('clients/index.php') ?>" class="btn">Clear</a>
    <?php endif; ?>
</form>

<table id="clients-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Company</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Added</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($clients->num_rows === 0): ?>
            <tr><td colspan="6" class="table-empty">No clients found.</td></tr>
        <?php else: ?>
            <?php while ($client = $clients->fetch_assoc()): ?>
                <tr>
                    <td><a href="<?= base_url('clients/view.php?id=' . $client['id']) ?>"><?= e($client['name']) ?></a></td>
                    <td><?= e($client['company'] ?: '—') ?></td>
                    <td><?= e($client['email'] ?: '—') ?></td>
                    <td><?= e($client['phone'] ?: '—') ?></td>
                    <td><?= format_date($client['created_at']) ?></td>
                    <td class="actions-cell">
                        <a href="<?= base_url('clients/edit.php?id=' . $client['id']) ?>">Edit</a>
                        <a href="<?= base_url('clients/delete.php?id=' . $client['id']) ?>" class="js-confirm-delete" data-message="Delete this client? This cannot be undone.">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
