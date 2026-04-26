<?php

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Pemecahan Masalah';
$activePage = 'trouble';
$db         = getDB();
$error      = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_tip') {
        $title    = trim($_POST['title'] ?? '');
        $fix      = trim($_POST['fix'] ?? '');
        $severity = $_POST['severity'] ?? 'amber';

        if (!in_array($severity, ['red', 'amber', 'green'])) {
            $severity = 'amber';
        }

        if (empty($title) || empty($fix)) {
            $error = 'Judul masalah dan solusi wajib diisi.';
        } else {
            $stmt = $db->prepare("INSERT INTO troubleshooting (title, fix, severity) VALUES (?, ?, ?)");
            $stmt->execute([$title, $fix, $severity]);
            setFlash('success', 'Tip baru berhasil ditambahkan!');
            header('Location: trouble.php');
            exit;
        }
    }

    if ($action === 'hapus_tip') {
        $id = (int)($_POST['tip_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM troubleshooting WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Tip berhasil dihapus.');
        header('Location: trouble.php');
        exit;
    }
}


$filterSeverity = $_GET['severity'] ?? 'all';
$validSeverity  = ['all', 'red', 'amber', 'green'];
if (!in_array($filterSeverity, $validSeverity)) {
    $filterSeverity = 'all';
}

if ($filterSeverity === 'all') {
    $stmt = $db->query("SELECT * FROM troubleshooting ORDER BY FIELD(severity,'red','amber','green'), id ASC");
} else {
    $stmt = $db->prepare("SELECT * FROM troubleshooting WHERE severity = ? ORDER BY id ASC");
    $stmt->execute([$filterSeverity]);
}
$tips = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="card mb-3">
    <h3 class="card-judul">➕ Tambah Tip Baru</h3>

    <?php if ($error): ?>
        <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="trouble.php">
        <input type="hidden" name="action" value="tambah_tip">

        <div class="form-group">
            <label>Judul Masalah</label>
            <input type="text" name="title" placeholder="contoh: Bau busuk menyengat" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Solusi / Cara Mengatasi</label>
            <textarea name="fix" rows="3" placeholder="Jelaskan cara mengatasinya..." required><?= htmlspecialchars($_POST['fix'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label>Tingkat Keparahan</label>
            <select name="severity" class="form-select-plain" style="width: 200px;">
                <option value="green">🟢 Ringan</option>
                <option value="amber" selected>🟡 Sedang</option>
                <option value="red">🔴 Serius</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Tip →</button>
    </form>
</div>

<div class="d-flex gap-2 mb-3">
    <a href="trouble.php?severity=all"   class="btn btn-sm <?= $filterSeverity === 'all'   ? 'btn-primary' : '' ?>">Semua</a>
    <a href="trouble.php?severity=red"   class="btn btn-sm <?= $filterSeverity === 'red'   ? 'btn-primary' : '' ?>">🔴 Serius</a>
    <a href="trouble.php?severity=amber" class="btn btn-sm <?= $filterSeverity === 'amber' ? 'btn-primary' : '' ?>">🟡 Sedang</a>
    <a href="trouble.php?severity=green" class="btn btn-sm <?= $filterSeverity === 'green' ? 'btn-primary' : '' ?>">🟢 Ringan</a>
</div>

<?php if (empty($tips)): ?>
    <p class="text-muted small">Belum ada tip tersimpan.</p>
<?php else: ?>
    <div class="tip-grid">
        <?php foreach ($tips as $tip): ?>
            <div class="tip-card <?= htmlspecialchars($tip['severity']) ?>">
                <div class="tip-card-header">
                    <div class="tip-title"><?= htmlspecialchars($tip['title']) ?></div>
                    <form method="POST" action="trouble.php" class="inline-form">
                        <input type="hidden" name="action" value="hapus_tip">
                        <input type="hidden" name="tip_id" value="<?= $tip['id'] ?>">
                        <button type="submit" class="btn-hapus-x" onclick="return confirm('Hapus tip ini?')" title="Hapus">✕</button>
                    </form>
                </div>
                <div class="tip-fix"><?= htmlspecialchars($tip['fix']) ?></div>
                <div class="tip-severity-label">
                    <?php
                    $sevLabel = ['red' => '🔴 Serius', 'amber' => '🟡 Sedang', 'green' => '🟢 Ringan'];
                    echo $sevLabel[$tip['severity']] ?? '';
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
