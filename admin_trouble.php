<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

// Pastikan hanya admin yang bisa buka halaman ini
if (!isAdmin()) {
    header('Location: trouble.php');
    exit;
}

$pageTitle  = 'Kelola Tip Masalah';
$activePage = 'trouble';
$db         = getDB();
$error      = '';

// ============================================================
// PROSES POST (TAMBAH / HAPUS TIP)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_tip') {
        $title = trim($_POST['title'] ?? '');
        $fix = trim($_POST['fix'] ?? '');
        $severity = $_POST['severity'] ?? 'amber';
        
        if (!in_array($severity, ['red', 'amber', 'green'])) {
            $severity = 'amber';
        }

        if (empty($title) || empty($fix)) {
            $error = 'Judul dan solusi wajib diisi.';
        } else {
            $stmt = $db->prepare("INSERT INTO troubleshooting (title, fix, severity) VALUES (?, ?, ?)");
            $stmt->execute([$title, $fix, $severity]);
            setFlash('success', 'Tip baru berhasil disimpan.');
            header('Location: admin_trouble.php');
            exit;
        }
    }

    if ($action === 'hapus_tip') {
        $id = (int)($_POST['tip_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM troubleshooting WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Tip berhasil dihapus.');
        header('Location: admin_trouble.php');
        exit;
    }
}

// AMBIL DATA TIP
$filterSeverity = $_GET['severity'] ?? 'all';
if (!in_array($filterSeverity, ['all', 'red', 'amber', 'green'])) {
    $filterSeverity = 'all';
}

if ($filterSeverity === 'all') {
    $stmt = $db->query("SELECT * FROM troubleshooting ORDER BY FIELD(severity,'red','amber','green'), id DESC");
} else {
    $stmt = $db->prepare("SELECT * FROM troubleshooting WHERE severity = ? ORDER BY id DESC");
    $stmt->execute([$filterSeverity]);
}
$tips = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <h3 class="card-judul">➕ Tambah Tip Masalah</h3>

            <?php if ($error): ?>
                <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="admin_trouble.php">
                <input type="hidden" name="action" value="tambah_tip">

                <div class="form-group">
                    <label>Judul Masalah</label>
                    <input type="text" name="title" placeholder="contoh: Bau busuk menyengat" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Solusi / Penanganan</label>
                    <textarea name="fix" rows="4" placeholder="Jelaskan cara menanganinya..." required><?= htmlspecialchars($_POST['fix'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Tingkat Keparahan</label>
                    <select name="severity" class="form-control">
                        <option value="green">Ringan (Hijau)</option>
                        <option value="amber" selected>Sedang (Kuning)</option>
                        <option value="red">Serius (Merah)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">Simpan Tip</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div class="section-header mb-0">
                <h3 class="section-title">Knowledge Base</h3>
            </div>
            
            <div class="d-flex gap-2">
                <a href="admin_trouble.php?severity=all"   class="btn btn-sm <?= $filterSeverity === 'all'   ? 'btn-primary' : '' ?>">Semua</a>
                <a href="admin_trouble.php?severity=red"   class="btn btn-sm <?= $filterSeverity === 'red'   ? 'btn-primary' : '' ?>">Serius</a>
                <a href="admin_trouble.php?severity=amber" class="btn btn-sm <?= $filterSeverity === 'amber' ? 'btn-primary' : '' ?>">Sedang</a>
                <a href="admin_trouble.php?severity=green" class="btn btn-sm <?= $filterSeverity === 'green' ? 'btn-primary' : '' ?>">Ringan</a>
            </div>
        </div>

        <?php if (empty($tips)): ?>
            <p class="text-muted small">Belum ada data tip tersimpan.</p>
        <?php else: ?>
            <div class="tip-grid">
                <?php foreach ($tips as $tip): ?>
                    <div class="tip-card <?= htmlspecialchars($tip['severity']) ?>">
                        <div class="tip-card-header">
                            <div class="tip-title"><?= htmlspecialchars($tip['title']) ?></div>
                            
                            <form method="POST" action="admin_trouble.php" class="inline-form">
                                <input type="hidden" name="action" value="hapus_tip">
                                <input type="hidden" name="tip_id" value="<?= $tip['id'] ?>">
                                <button type="submit" class="btn-hapus-x" onclick="return confirm('Hapus tip ini?')" title="Hapus">✕</button>
                            </form>
                        </div>
                        <div class="tip-fix"><?= nl2br(htmlspecialchars($tip['fix'])) ?></div>
                        <div class="tip-severity-label mt-2">
                            <?php
                            $sevLabel = ['red' => '🔴 Serius', 'amber' => '🟡 Sedang', 'green' => '🟢 Ringan'];
                            echo $sevLabel[$tip['severity']] ?? '';
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>