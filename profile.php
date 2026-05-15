<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Profil Saya';
$activePage = 'profile';
$db         = getDB();
$userId     = currentUserId();
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($nama) || empty($email)) {
            $error = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            try {
                $avatar = strtoupper(substr($nama, 0, 1));

                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$nama, $email, $avatar, $userId]);

                $_SESSION['user_name']   = $nama;
                $_SESSION['user_avatar'] = $avatar;

                setFlash('success', 'Profil berhasil diperbarui!');
                header('Location: profile.php');
                exit;

            } catch (PDOException $e) {
                $error = 'Email sudah digunakan akun lain.';
            }
        }
    }

    if ($action === 'ganti_password') {
        $passLama   = $_POST['password_lama'] ?? '';
        $passBaru   = $_POST['password_baru'] ?? '';
        $konfirmasi = $_POST['konfirmasi_password'] ?? '';

        if (empty($passLama) || empty($passBaru)) {
            $error = 'Semua kolom password wajib diisi.';
        } elseif (strlen($passBaru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($passBaru !== $konfirmasi) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!password_verify($passLama, $user['password'])) {
                $error = 'Password lama salah.';
            } else {
                $hashBaru = password_hash($passBaru, PASSWORD_BCRYPT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashBaru, $userId]);

                $_SESSION = [];
                session_destroy();
                setFlash('success', 'Password berhasil diubah! Silakan login ulang.');
                header('Location: login.php');
                exit;
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$stmtStats = $db->prepare("
    SELECT
        COUNT(*) AS total_batch,
        SUM(CASE WHEN status = 'active'    THEN 1 ELSE 0 END) AS aktif,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS selesai,
        COALESCE(SUM(volume_liters), 0) AS total_volume
    FROM batches
    WHERE user_id = ?
");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch();

require_once 'includes/header.php';
?>

<div class="row g-3">

    <div class="col-md-4">
        <div class="card mb-3 text-center">
            <div class="profil-avatar-wrap"><?= htmlspecialchars($user['avatar']) ?></div>
            <div class="profil-nama"><?= htmlspecialchars($user['name']) ?></div>
            <div class="profil-email"><?= htmlspecialchars($user['email']) ?></div>
            <div class="profil-join-date">Bergabung sejak <?= date('d M Y', strtotime($user['created_at'])) ?></div>
        </div>

        <div class="card">
            <h4 class="mb-2" style="font-size: 14px;">Statistik Kamu</h4>
            <div class="stat-grid">
                <div>
                    <div class="stat-angka"><?= $stats['total_batch'] ?></div>
                    <div class="stat-label">Total Batch</div>
                </div>
                <div>
                    <div class="stat-angka"><?= $stats['aktif'] ?></div>
                    <div class="stat-label">Batch Aktif</div>
                </div>
                <div>
                    <div class="stat-angka"><?= $stats['selesai'] ?></div>
                    <div class="stat-label">Selesai</div>
                </div>
                <div>
                    <div class="stat-angka"><?= round($stats['total_volume'], 1) ?>L</div>
                    <div class="stat-label">Total Volume</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php if ($error): ?>
            <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card mb-3">
            <h3 class="card-judul">Edit Profil</h3>
            <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="update_profil">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan →</button>
            </form>
        </div>

        <div class="card">
            <h3 class="card-judul">Ganti Password</h3>
            <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="ganti_password">
                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="password_lama" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="konfirmasi_password" placeholder="Ketik ulang password baru" required>
                </div>

                <div class="warning-ganti-pass">
                    ⚠️ Setelah ganti password, kamu akan otomatis keluar dan perlu login ulang.
                </div>

                <button type="submit" class="btn btn-primary" style="background-color: #ef4444; border-color: #ef4444;">Ganti Password →</button>
            </form>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>