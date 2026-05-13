<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Bahan & Resep';
$activePage = 'ingredients';
$db         = getDB();
$userId     = currentUserId();
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_bahan') {
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $nama    = trim($_POST['nama'] ?? '');
        $tipe    = trim($_POST['tipe'] ?? '');
        $berat   = (float)($_POST['berat'] ?? 0);

        if (empty($nama) || $berat <= 0) {
            $error = 'Nama dan berat bahan wajib diisi.';
        } else {
            $cek = $db->prepare("SELECT id FROM batches WHERE id = ? AND user_id = ?");
            $cek->execute([$batchId, $userId]);
            if (!$cek->fetch()) {
                $error = 'Batch tidak valid.';
            } else {
                $stmt = $db->prepare("INSERT INTO batch_ingredients (batch_id, name, type, weight_grams) VALUES (?, ?, ?, ?)");
                $stmt->execute([$batchId, $nama, $tipe, $berat]);
                setFlash('success', 'Bahan berhasil ditambahkan!');
                header('Location: ingredients.php?batch_id=' . $batchId);
                exit;
            }
        }
    }

    if ($action === 'hapus_bahan') {
        $ingId   = (int)($_POST['ing_id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);

        $stmt = $db->prepare("
            DELETE bi FROM batch_ingredients bi
            JOIN batches b ON bi.batch_id = b.id
            WHERE bi.id = ? AND b.user_id = ?
        ");
        $stmt->execute([$ingId, $userId]);

        setFlash('success', 'Bahan berhasil dihapus.');
        header('Location: ingredients.php?batch_id=' . $batchId);
        exit;
    }
}

$stmtBatch = $db->prepare("SELECT id, name, status FROM batches WHERE user_id = ? ORDER BY start_date DESC");
$stmtBatch->execute([$userId]);
$semuaBatch = $stmtBatch->fetchAll();

$selectedBatchId = (int)($_GET['batch_id'] ?? 0);
$selectedBatch   = null;
$ingredients     = [];
$totalBerat      = 0;

if ($selectedBatchId > 0) {
    $cek = $db->prepare("SELECT * FROM batches WHERE id = ? AND user_id = ?");
    $cek->execute([$selectedBatchId, $userId]);
    $selectedBatch = $cek->fetch();

    if ($selectedBatch) {
        $stmt = $db->prepare("SELECT * FROM batch_ingredients WHERE batch_id = ? ORDER BY id");
        $stmt->execute([$selectedBatchId]);
        $ingredients = $stmt->fetchAll();

        foreach ($ingredients as $ing) {
            $totalBerat += $ing['weight_grams'];
        }
    }
}

require_once 'includes/header.php';
?>

<div class="card mb-3">
    <h3 class="card-judul"> Pilih Batch</h3>
    <form method="GET" action="ingredients.php" class="d-flex gap-2 align-items-end">
        <div class="form-group mb-0 flex-grow-1">
            <select name="batch_id" class="form-select-plain">
                <option value="">-- Pilih Batch --</option>
                <?php foreach ($semuaBatch as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $selectedBatchId === (int)$b['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['name']) ?> (<?= $b['status'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-sm">Lihat Bahan</button>
    </form>
</div>

<?php if ($selectedBatch): ?>
    <div class="row g-3">

        <div class="col-md-4">
            <div class="card">
                <h3 class="card-judul" style="font-size: 16px;"> Tambah Bahan ke <?= htmlspecialchars($selectedBatch['name']) ?></h3>

                <?php if ($error): ?>
                    <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="ingredients.php?batch_id=<?= $selectedBatchId ?>">
                    <input type="hidden" name="action" value="tambah_bahan">
                    <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">

                    <div class="form-group">
                        <label>Nama Bahan</label>
                        <input type="text" name="nama" placeholder="contoh: Kulit jeruk" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe Bahan</label>
                        <select name="tipe" class="form-select-plain">
                            <option value="Limbah organik">Limbah organik</option>
                            <option value="Sumber gula">Sumber gula</option>
                            <option value="Pelarut">Pelarut</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Berat (gram)</label>
                        <input type="number" name="berat" step="0.1" min="0.1" placeholder="contoh: 500" value="<?= htmlspecialchars($_POST['berat'] ?? '') ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Tambah Bahan →</button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 style="font-family: var(--font-judul); font-size: 16px;">Komposisi Bahan</h3>
                <span class="text-muted small">
                    Total: <strong><?= number_format($totalBerat, 0) ?> gram</strong>
                </span>
            </div>

            <?php if (empty($ingredients)): ?>
                <p class="text-muted small">Belum ada bahan tercatat.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nama Bahan</th>
                                <th>Tipe</th>
                                <th>Berat</th>
                                <th>%</th>
                                <th>Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingredients as $ing): ?>
                                <?php $pct = $totalBerat > 0 ? round(($ing['weight_grams'] / $totalBerat) * 100, 1) : 0; ?>
                                <tr>
                                    <td><?= htmlspecialchars($ing['name']) ?></td>
                                    <td>
                                        <span class="badge-tipe"><?= htmlspecialchars($ing['type']) ?></span>
                                    </td>
                                    <td><?= number_format($ing['weight_grams'], 0) ?> g</td>
                                    <td>
                                        <div class="mini-progress-wrap">
                                            <div class="mini-progress-track">
                                                <div class="mini-progress-fill" style="width: <?= min(100, $pct) ?>%"></div>
                                            </div>
                                            <span class="mini-progress-pct"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" action="ingredients.php?batch_id=<?= $selectedBatchId ?>" class="inline-form">
                                            <input type="hidden" name="action" value="hapus_bahan">
                                            <input type="hidden" name="ing_id" value="<?= $ing['id'] ?>">
                                            <input type="hidden" name="batch_id" value="<?= $selectedBatchId ?>">
                                            < <button type="submit" class="btn btn-sm btn-danger" style="font-size: 14px; padding: 5px 10px;" onclick="return confirm('Hapus bahan ini?')"> 🗑</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong>Total</strong></td>
                                <td><strong><?= number_format($totalBerat, 0) ?> g</strong></td>
                                <td><strong>100%</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="rasio-info">
                     <strong>Rasio ideal eco-enzyme:</strong> Limbah organik : Gula : Air = 3 : 1 : 10
                </div>
            <?php endif; ?>
        </div>

    </div>
<?php elseif (!empty($semuaBatch)): ?>
    <p class="text-muted small">Pilih batch di atas untuk melihat atau mengedit komposisi bahan.</p>
<?php else: ?>
    <div class="card text-center p-4">
        <p class="text-muted small">
            Belum ada batch. <a href="batch.php">Tambah batch pertama</a> dahulu.
        </p>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>