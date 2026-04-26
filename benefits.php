<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Manfaat Enzim';
$activePage = 'benefits';
$db         = getDB();
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_benefit') {
        $title   = trim($_POST['title'] ?? '');
        $iconKey = trim($_POST['icon_key'] ?? 'leaf');

        if (empty($title)) {
            $error = 'Judul manfaat wajib diisi.';
        } else {
            $stmt = $db->prepare("INSERT INTO benefits (title, icon_key) VALUES (?, ?)");
            $stmt->execute([$title, $iconKey]);
            setFlash('success', 'Manfaat berhasil ditambahkan!');
            header('Location: benefits.php');
            exit;
        }
    }

    if ($action === 'hapus_benefit') {
        $id = (int)($_POST['benefit_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM benefits WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Manfaat berhasil dihapus.');
        header('Location: benefits.php');
        exit;
    }
}

$stmt     = $db->query("SELECT * FROM benefits ORDER BY id");
$benefits = $stmt->fetchAll();

$icons = [
    'leaf'     => '🌿',
    'clean'    => '🧹',
    'plant'    => '🌱',
    'bug'      => '🐛',
    'air'      => '💨',
    'recycle'  => '♻️',
    'drain'    => '🚿',
    'toilet'   => '🚽',
    'bacteria' => '🦠',
    'heart'    => '💚',
    'drop'     => '💧',
];

require_once 'includes/header.php';
?>

<div class="row g-3">

    <div class="col-md-4">
        <div class="card">
            <h3 class="card-judul">➕ Tambah Manfaat</h3>

            <?php if ($error): ?>
                <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="benefits.php">
                <input type="hidden" name="action" value="tambah_benefit">

                <div class="form-group">
                    <label>Judul Manfaat</label>
                    <input type="text" name="title" placeholder="contoh: Pembersih alami serbaguna" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Pilih Ikon</label>
                    <select name="icon_key" class="form-select-plain">
                        <?php foreach ($icons as $key => $emoji): ?>
                            <option value="<?= $key ?>"><?= $emoji ?> <?= ucfirst($key) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Tambah →</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <h3 class="section-title mb-3">
            Semua Manfaat (<?= count($benefits) ?>)
        </h3>

        <?php if (empty($benefits)): ?>
            <p class="text-muted small">Belum ada data manfaat.</p>
        <?php else: ?>
            <div class="benefit-grid">
                <?php foreach ($benefits as $b): ?>
                    <div class="benefit-card" style="position: relative;">
                        <form method="POST" action="benefits.php" class="inline-form">
                            <input type="hidden" name="action" value="hapus_benefit">
                            <input type="hidden" name="benefit_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="benefit-hapus-btn" onclick="return confirm('Hapus manfaat ini?')" title="Hapus">✕</button>
                        </form>
                        <div class="benefit-icon"><?= $icons[$b['icon_key']] ?? '🌿' ?></div>
                        <div class="benefit-label"><?= htmlspecialchars($b['title']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>