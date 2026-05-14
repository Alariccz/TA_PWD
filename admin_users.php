<?php
// admin_users.php - kelola semua user (lihat, ganti role, hapus)

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAdmin();

$pageTitle  = 'Daftar Semua User';
$activePage = 'admin_users';
$db         = getDB();
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ganti role user
    if ($action === 'ganti_role') {
        $targetId  = (int)($_POST['target_id'] ?? 0);
        $roleBarou = $_POST['role_baru'] ?? 'user';

        // admin tidak bisa turunkan role dirinya sendiri
        if ($targetId === currentUserId()) {
            setFlash('error', 'Tidak bisa mengubah role akun sendiri.');
        } elseif (in_array($roleBarou, ['admin', 'user'])) {
            $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$roleBarou, $targetId]);
            setFlash('success', 'Role user berhasil diubah.');
        }
        header('Location: admin_users.php');
        exit;
    }

    // hapus user
    if ($action === 'hapus_user') {
        $targetId = (int)($_POST['target_id'] ?? 0);
        if ($targetId === currentUserId()) {
            setFlash('error', 'Tidak bisa menghapus akun sendiri.');
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            setFlash('success', 'User berhasil dihapus.');
        }
        header('Location: admin_users.php');
        exit;
    }
}

$users = $db->query("
    SELECT u.*,
        COUNT(b.id)                                              AS total_batch,
        COALESCE(SUM(b.volume_liters), 0)                        AS total_volume,
        SUM(CASE WHEN b.status = 'active' THEN 1 ELSE 0 END)    AS batch_aktif
    FROM users u
    LEFT JOIN batches b ON b.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

require_once 'includes/header.php';
?>

<div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Batch (Aktif)</th>
                <th>Volume</th>
                <th>Bergabung</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr>
                    <td style="color: var(--text-muted);"><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="sb-avatar" style="width:26px;height:26px;font-size:11px;">
                                <?= htmlspecialchars($u['avatar']) ?>
                            </div>
                            <?= htmlspecialchars($u['name']) ?>
                            <?php if ($u['id'] === currentUserId()): ?>
                                <span style="font-size:10px;color:var(--text-muted)">(Kamu)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <span class="role-badge <?= $u['role'] === 'admin' ? 'role-badge--admin' : 'role-badge--user' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td><?= $u['total_batch'] ?> (<?= $u['batch_aktif'] ?>)</td>
                    <td><?= round($u['total_volume'], 1) ?>L</td>
                    <td style="font-size:11px"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] !== currentUserId()): ?>
                            <div style="display:flex;gap:5px;align-items:center">
                                <!-- ganti role -->
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action"    value="ganti_role">
                                    <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="role_baru" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                                    <button type="submit" class="btn-info" style="font-size:11px;padding:3px 9px"
                                        onclick="return confirm('Ubah role <?= htmlspecialchars($u['name']) ?> jadi <?= $u['role'] === 'admin' ? 'User' : 'Admin' ?>?')">
                                        <?= $u['role'] === 'admin' ? '↓ Jadikan User' : '↑ Jadikan Admin' ?>
                                    </button>
                                </form>
                                <!-- hapus -->
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="action"    value="hapus_user">
                                    <input type="hidden" name="target_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn-danger" style="font-size:11px;padding:3px 8px"
                                        onclick="return confirm('Hapus user <?= htmlspecialchars($u['name']) ?>? Semua batch-nya ikut terhapus!')">🗑</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span style="font-size:11px;color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>