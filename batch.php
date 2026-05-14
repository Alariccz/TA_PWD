<?php
// batch.php - halaman kelola batch (CRUD lengkap)
// CREATE: tambah batch baru
// READ: tampilkan daftar batch
// UPDATE: ubah status batch
// DELETE: hapus batch

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Kelola Batch';
$activePage = 'batch';
$db         = getDB();
$userId     = currentUserId();
$error      = '';

// ============================================================
// PROSES FORM (POST)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // tambah batch baru
    if ($action === 'tambah_batch') {
        $name   = trim($_POST['name'] ?? '');
        $flavor = trim($_POST['flavor_label'] ?? '');
        $start  = $_POST['start_date'] ?? '';
        $target = $_POST['target_date'] ?? '';
        $volume = (float)($_POST['volume_liters'] ?? 0);
        $notes  = trim($_POST['notes'] ?? '');

        if (empty($name) || empty($flavor) || empty($start) || empty($target) || $volume <= 0) {
            $error = 'Semua kolom wajib diisi, volume harus lebih dari 0.';
        } else {
            $stmt = $db->prepare("
                INSERT INTO batches (user_id, name, flavor_label, start_date, tanggal_buat, target_date, volume_liters, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $name, $flavor, $start, $start, $target, $volume, $notes]);
            $batchId = $db->lastInsertId();

            // simpan bahan-bahan kalau ada
            $bahanNama  = $_POST['bahan_nama']  ?? [];
            $bahanTipe  = $_POST['bahan_tipe']  ?? [];
            $bahanBerat = $_POST['bahan_berat'] ?? [];

            $stmtBahan = $db->prepare("
                INSERT INTO batch_ingredients (batch_id, name, type, weight_grams)
                VALUES (?, ?, ?, ?)
            ");

            for ($i = 0; $i < count($bahanNama); $i++) {
                if (!empty(trim($bahanNama[$i]))) {
                    $stmtBahan->execute([
                        $batchId,
                        trim($bahanNama[$i]),
                        trim($bahanTipe[$i] ?? ''),
                        (float)($bahanBerat[$i] ?? 0)
                    ]);
                }
            }

            setFlash('success', 'Batch "' . $name . '" berhasil ditambahkan!');
            header('Location: batch.php');
            exit;
        }
    }

    // tandai selesai
    if ($action === 'selesaikan_batch') {
        $id = (int)($_POST['batch_id'] ?? 0);
        $stmt = $db->prepare("UPDATE batches SET status = 'completed' WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Batch berhasil ditandai sebagai selesai!');
        header('Location: batch.php');
        exit;
    }

    // tandai gagal
    if ($action === 'gagalkan_batch') {
        $id = (int)($_POST['batch_id'] ?? 0);
        $stmt = $db->prepare("UPDATE batches SET status = 'failed' WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Status batch diperbarui.');
        header('Location: batch.php');
        exit;
    }

    // hapus batch
    if ($action === 'hapus_batch') {
        $id = (int)($_POST['batch_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM batches WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        setFlash('success', 'Batch berhasil dihapus.');
        header('Location: batch.php');
        exit;
    }
}

// ============================================================
// AMBIL DATA
// ============================================================

$filterStatus = $_GET['status'] ?? 'active';
if (!in_array($filterStatus, ['active', 'completed', 'failed'])) {
    $filterStatus = 'active';
}

$stmtList = $db->prepare("
    SELECT *,
        DATEDIFF(target_date, CURDATE())  AS days_left,
        DATEDIFF(CURDATE(), start_date)   AS days_elapsed,
        DATEDIFF(target_date, start_date) AS total_days,
        DATEDIFF(DATE_ADD(COALESCE(tanggal_buat, start_date), INTERVAL 90 DAY), CURDATE()) AS hari_panen
    FROM batches
    WHERE user_id = ? AND status = ?
    ORDER BY start_date DESC
");
$stmtList->execute([$userId, $filterStatus]);
$batches = $stmtList->fetchAll();

// cek kalau ada ?detail=ID
$detailBatch       = null;
$detailIngredients = [];
if (isset($_GET['detail'])) {
    $detailId = (int)$_GET['detail'];
    $stmtDetail = $db->prepare("SELECT * FROM batches WHERE id = ? AND user_id = ?");
    $stmtDetail->execute([$detailId, $userId]);
    $detailBatch = $stmtDetail->fetch();

    if ($detailBatch) {
        $stmtIng = $db->prepare("SELECT * FROM batch_ingredients WHERE batch_id = ?");
        $stmtIng->execute([$detailId]);
        $detailIngredients = $stmtIng->fetchAll();
    }
}

require_once 'includes/header.php';
?>

<!-- detail batch (kalau ada) -->
<?php if ($detailBatch): ?>
    <div class="card mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 style="font-family: var(--font-judul); font-size: 20px; margin-bottom: 4px;">
                    <?= htmlspecialchars($detailBatch['name']) ?>
                    <span class="badge badge-<?= $detailBatch['status'] ?>"><?= ucfirst($detailBatch['status']) ?></span>
                </h3>
                <div class="text-muted small"><?= htmlspecialchars($detailBatch['flavor_label']) ?></div>
            </div>
            <a href="batch.php" class="text-muted small">← Kembali</a>
        </div>

        <div class="detail-info-grid">
            <div>
                <strong>Tanggal Mulai</strong>
                <?= date('d M Y', strtotime($detailBatch['start_date'])) ?>
            </div>
            <div>
                <strong>Target Selesai</strong>
                <?= date('d M Y', strtotime($detailBatch['target_date'])) ?>
            </div>
            <div>
                <strong>Volume</strong>
                <?= $detailBatch['volume_liters'] ?> liter
            </div>
        </div>

        <?php if ($detailBatch['notes']): ?>
            <div class="detail-catatan">
                <strong>Catatan:</strong> <?= htmlspecialchars($detailBatch['notes']) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($detailIngredients)): ?>
            <h4 class="mb-2 mt-3" style="font-size: 15px;">Komposisi Bahan</h4>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Tipe</th>
                            <th>Berat (gram)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailIngredients as $ing): ?>
                            <tr>
                                <td><?= htmlspecialchars($ing['name']) ?></td>
                                <td><?= htmlspecialchars($ing['type']) ?></td>
                                <td><?= number_format($ing['weight_grams'], 0) ?> g</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="logs.php?batch_id=<?= $detailBatch['id'] ?>" class="btn-info">📋 Lihat/Tambah Log Batch Ini</a>
        </div>
    </div>
<?php endif; ?>

<!-- form tambah batch baru -->
<div class="card mb-3">
    <h3 class="card-judul">➕ Tambah Batch Baru</h3>

    <?php if ($error): ?>
        <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="batch.php">
        <input type="hidden" name="action" value="tambah_batch">

        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nama Batch</label>
                    <input type="text" name="name" placeholder="contoh: Batch Jeruk April" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Label Rasa / Bahan Utama</label>
                    <input type="text" name="flavor_label" placeholder="contoh: Jeruk Nanas" value="<?= htmlspecialchars($_POST['flavor_label'] ?? '') ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Target Selesai</label>
                    <input type="date" name="target_date" value="<?= htmlspecialchars($_POST['target_date'] ?? '') ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Volume (liter)</label>
                    <input type="number" name="volume_liters" step="0.1" min="0.1" placeholder="contoh: 3.5" value="<?= htmlspecialchars($_POST['volume_liters'] ?? '') ?>" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Catatan (opsional)</label>
                    <input type="text" name="notes" placeholder="catatan singkat..." value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- input bahan - bisa ditambah dinamis pakai JS -->
        <h4 class="mt-3 mb-2" style="font-size: 14px;">Komposisi Bahan (opsional)</h4>
        <div id="bahan-list">
            <!-- baris bahan pertama -->
            <div class="bahan-row">
                <div>
                    <label class="bahan-row-label">Nama Bahan</label>
                    <input type="text" name="bahan_nama[]" placeholder="Kulit jeruk">
                </div>
                <div>
                    <label class="bahan-row-label">Tipe</label>
                    <select name="bahan_tipe[]">
                        <option value="Limbah organik">Limbah organik</option>
                        <option value="Sumber gula">Sumber gula</option>
                        <option value="Pelarut">Pelarut</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="bahan-row-label">Berat (gram)</label>
                    <input type="number" name="bahan_berat[]" placeholder="500">
                </div>
                <div>
                    <button type="button" class="btn-hapus-bahan" onclick="hapusBaris(this)">✕</button>
                </div>
            </div>
        </div>

        <!-- tombol tambah baris bahan -->
        <button type="button" class="btn-tambah-bahan" onclick="tambahBahan()">+ Tambah Bahan</button>

        <button type="submit" class="btn btn-primary">Simpan Batch →</button>
    </form>
</div>

<!-- daftar batch -->
<div class="section-header">
    <h3 class="section-title">Daftar Batch</h3>
    <div class="d-flex gap-2">
        <a href="batch.php?status=active"    class="btn btn-sm <?= $filterStatus === 'active'    ? 'btn-primary' : '' ?>">Aktif</a>
        <a href="batch.php?status=completed" class="btn btn-sm <?= $filterStatus === 'completed' ? 'btn-primary' : '' ?>">Selesai</a>
        <a href="batch.php?status=failed"    class="btn btn-sm <?= $filterStatus === 'failed'    ? 'btn-primary' : '' ?>">Gagal</a>
    </div>
</div>

<?php if (empty($batches)): ?>
    <p class="text-muted small">Tidak ada batch dengan status "<?= $filterStatus ?>".</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Batch</th>
                    <th>Rasa</th>
                    <th>Mulai</th>
                    <th>Target</th>
                    <th>Volume</th>
                    <th>Progress</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($batches as $b): ?>
                    <?php
                    $pct      = $b['total_days'] > 0 ? min(100, round(($b['days_elapsed'] / $b['total_days']) * 100)) : 0;
                    $daysLeft = (int)$b['days_left'];
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                        <td><?= htmlspecialchars($b['flavor_label']) ?></td>
                        <td><?= date('d M Y', strtotime($b['start_date'])) ?></td>
                        <td><?= date('d M Y', strtotime($b['target_date'])) ?></td>
                        <td><?= $b['volume_liters'] ?>L</td>
                        <td>
                            <div class="tabel-progress-track">
                                <div class="tabel-progress-fill" style="width: <?= $pct ?>%"></div>
                            </div>
                            <div class="tabel-progress-pct"><?= $pct ?>%</div>
                        </td>
                        <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="batch.php?detail=<?= $b['id'] ?>&status=<?= $filterStatus ?>" class="btn-info" style="font-size: 11px; padding: 4px 8px;">Detail</a>
                                <a href="logs.php?batch_id=<?= $b['id'] ?>" class="btn-purple">Log</a>

                                <?php if ($b['status'] === 'active'): ?>
                                    <form method="POST" action="batch.php" class="inline-form">
                                        <input type="hidden" name="action" value="selesaikan_batch">
                                        <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn-success" style="font-size: 11px; padding: 4px 8px;" onclick="return confirm('Tandai batch ini sebagai selesai?')">✓ Selesai</button>
                                    </form>
                                    <form method="POST" action="batch.php" class="inline-form">
                                        <input type="hidden" name="action" value="gagalkan_batch">
                                        <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
                                        <button type="submit" class="btn-danger" style="font-size: 11px; padding: 4px 8px; background: #f59e0b;" onclick="return confirm('Tandai batch ini sebagai gagal?')">✗ Gagal</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" action="batch.php" class="inline-form">
                                    <input type="hidden" name="action" value="hapus_batch">
                                    <input type="hidden" name="batch_id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn-danger" style="font-size: 11px; padding: 4px 8px;" onclick="return confirm('Yakin hapus batch \'<?= addslashes($b['name']) ?>\'? Semua data terkait akan ikut terhapus!')">🗑 Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>