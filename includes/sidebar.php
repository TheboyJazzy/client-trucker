<?php
// Determine active section from the current script path for nav highlighting.
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$section = 'dashboard';
foreach (['clients', 'projects', 'invoices'] as $s) {
    if (strpos($scriptPath, '/' . $s . '/') !== false) {
        $section = $s;
        break;
    }
}
if (strpos($scriptPath, '/index.php') !== false && substr_count($scriptPath, '/') <= 2) {
    $section = 'dashboard';
}

function nav_class($current, $section)
{
    return $current === $section ? 'nav-link active' : 'nav-link';
}
?>
<nav class="sidebar">
    <div class="sidebar-brand">Client Tracker</div>
    <ul class="nav-list">
        <li><a class="<?= nav_class($section, 'dashboard') ?>" href="<?= base_url('index.php') ?>">Dashboard</a></li>
        <li><a class="<?= nav_class($section, 'clients') ?>" href="<?= base_url('clients/index.php') ?>">Clients</a></li>
        <li><a class="<?= nav_class($section, 'projects') ?>" href="<?= base_url('projects/index.php') ?>">Projects</a></li>
        <li><a class="<?= nav_class($section, 'invoices') ?>" href="<?= base_url('invoices/index.php') ?>">Invoices</a></li>
    </ul>
    <div class="sidebar-footer">
        <div class="sidebar-user"><?= e($_SESSION['user_name'] ?? '') ?></div>
        <a class="nav-link" href="<?= base_url('auth/logout.php') ?>">Log out</a>
    </div>
</nav>
