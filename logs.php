<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Catatan Produksi';
$activePage = 'logs';
$db         = getDB();
$userId     = currentUserId();
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah_log') {
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $note    = trim($_POST['note'] ?? '');
        $ph      = !empty($_POST['ph_level']) ? (float)$_POST['ph_level'] : null;
        $bubbles = isset($_POST['has_bubbles']) ? 1 : 0;
        $mold    = isset($_POST['has_mold'])    ? 1 : 0;
        $smell   = $_POST['smell'] ?? 'normal';

        $validSmell = ['normal', 'sour', 'rotten', 'fragrant'];
        if (!in_array($smell, $validSmell)) {
            $smell = 'normal';
        }

        if (empty($note)) {
            $error = 'Catatan tidak boleh kosong.';
        } elseif ($batchId <= 0) {
            $error = 'Pilih batch terlebih dahulu.';
        } else {
            $checkStmt = $db->prepare("SELECT id FROM batches WHERE id = ? AND user_id = ?");
            $checkStmt->execute([$batchId, $userId]);
            if (!$checkStmt->fetch()) {
                $error = 'Batch tidak ditemukan.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO batch_logs (batch_id, user_id, note, ph_level, has_bubbles, has_mold, smell)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$batchId, $userId, $note, $ph, $bubbles, $mold, $smell]);

                setFlash('success', 'Log berhasil ditambahkan!');
                header('Location: logs.php?batch_id=' . $batchId);
                exit;
            }
        }
    }

    if ($action === 'hapus_log') {
        $logId   = (int)($_POST['log_id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? 0);

        $stmt = $db->prepare("DELETE FROM batch_logs WHERE id = ? AND user_id = ?");
        $stmt->execute([$logId, $userId]);

        setFlash('success', 'Log berhasil dihapus.');
        header('Location: logs.php?batch_id=' . $batchId);
        exit;
    }
}

$stmtBatches = $db->prepare("SELECT id, name, flavor_label, status FROM batches WHERE user_id = ? ORDER BY start_date DESC");
$stmtBatches->execute([$userId]);
$semuaBatch = $stmtBatches->fetchAll();

$selectedBatchId = (int)($_GET['batch_id'] ?? 0);
$selectedBatch   = null;
$logs            = [];

if ($selectedBatchId > 0) {
    $stmtSel = $db->prepare("SELECT * FROM batches WHERE id = ? AND user_id = ?");
    $stmtSel->execute([$selectedBatchId, $userId]);
    $selectedBatch = $stmtSel->fetch();

    if ($selectedBatch) {
        $stmtLogs = $db->prepare("
            SELECT l.*, u.name AS creator_name
            FROM batch_logs l
            JOIN users u ON l.user_id = u.id
            WHERE l.batch_id = ?
            ORDER BY l.log_date DESC
        ");
        $stmtLogs->execute([$selectedBatchId]);
        $logs = $stmtLogs->fetchAll();
    }
} else {
    $stmtAllLogs = $db->prepare("
        SELECT l.*, u.name AS creator_name, b.name AS batch_name
        FROM batch_logs l
        JOIN users u  ON l.user_id  = u.id
        JOIN batches b ON l.batch_id = b.id
        WHERE b.user_id = ?
        ORDER BY l.log_date DESC
        LIMIT 20
    ");
    $stmtAllLogs->execute([$userId]);
    $logs = $stmtAllLogs->fetchAll();
}

require_once 'includes/header.php';
?>

<div class="row g-3">

    <div class="col-md-4">
        <div class="card">
            <h3 class="card-judul">Tambah Catatan</h3>

            <?php if ($error): ?>
                <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="logs.php<?= $selectedBatchId ? '?batch_id='.$selectedBatchId : '' ?>">
                <input type="hidden" name="action" value="tambah_log">

                <div class="form-group">
                    <label>Pilih Batch</label>
                    <select name="batch_id" required class="form-select-plain">
                        <option value="">-- Pilih Batch --</option>
                        <?php foreach ($semuaBatch as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $selectedBatchId === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?> (<?= $b['status'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Catatan Hari Ini</label>
                    <textarea name="note" rows="4" placeholder="Tulis observasi, perubahan, atau perkembangan batch..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Level pH (opsional)</label>
                    <input type="number" name="ph_level" step="0.01" min="0" max="14" placeholder="contoh: 3.5">
                </div>

                <div class="form-group">
                    <label>Bau Fermentasi</label>
                    <select name="smell" class="form-select-plain">
                        <option value="normal">Normal</option>
                        <option value="sour">Asam</option>
                        <option value="fragrant">Wangi</option>
                        <option value="rotten">Busuk</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="has_bubbles" value="1">
                        Ada gelembung fermentasi?
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="has_mold" value="1">
                        Ada jamur?
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Catatan</button>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <?php if ($selectedBatch): ?>
            <div class="section-header mb-3">
                <h3 class="section-title">
                    Log: <?= htmlspecialchars($selectedBatch['name']) ?>
                    <span class="badge badge-<?= $selectedBatch['status'] ?>"><?= ucfirst($selectedBatch['status']) ?></span>
                </h3>
                <a href="logs.php" class="text-muted small">← Semua Log</a>
            </div>
        <?php else: ?>
            <h3 class="section-title mb-3">20 Log Terbaru</h3>
        <?php endif; ?>

        <?php if (empty($logs)): ?>
            <div class="card text-center p-4">
                <p class="text-muted small">Belum ada catatan. Mulai tambahkan log pertama.</p>
            </div>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <div class="card log-card">
                    <div class="log-header-row">
                        <div>
                            <?php if (!$selectedBatch && isset($log['batch_name'])): ?>
                                <span class="log-batch-pill"><?= htmlspecialchars($log['batch_name']) ?></span>
                            <?php endif; ?>
                            <div class="log-meta-text">
                                <?= date('d M Y, H:i', strtotime($log['log_date'])) ?>
                                oleh <?= htmlspecialchars($log['creator_name']) ?>
                            </div>
                        </div>
                        <form method="POST" action="logs.php" class="inline-form">
                            <input type="hidden" name="action" value="hapus_log">
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                            <input type="hidden" name="batch_id" value="<?= $log['batch_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" style="font-size: 14px; padding: 5px 10px;" onclick="return confirm('Hapus catatan ini?')">🗑</button>
                        </form>
                    </div>

                    <p class="log-note-text"><?= nl2br(htmlspecialchars($log['note'])) ?></p>

                    <div class="log-indikator">
                        <?php if ($log['ph_level'] !== null): ?>
                            <span class="<?= $log['ph_level'] < 4 ? 'ph-good' : ($log['ph_level'] < 7 ? 'ph-warn' : 'ph-bad') ?>">
                                pH <?= $log['ph_level'] ?>
                            </span>
                        <?php endif; ?>

                        <?php
                        $smellLabel = [
                            'normal'   => 'Normal',
                            'sour'     => 'Asam',
                            'fragrant' => 'Wangi',
                            'rotten'   => 'Busuk'
                        ];
                        $smellClass = [
                            'normal'   => 'smell-normal',
                            'sour'     => 'smell-sour',
                            'fragrant' => 'smell-fragrant',
                            'rotten'   => 'smell-rotten'
                        ];
                        ?>
                        <span class="<?= $smellClass[$log['smell']] ?? 'smell-normal' ?>">
                            <?= $smellLabel[$log['smell']] ?? $log['smell'] ?>
                        </span>

                        <?php if ($log['has_bubbles']): ?>
                            <span class="log-bubble">Ada gelembung</span>
                        <?php endif; ?>
                        <?php if ($log['has_mold']): ?>
                            <span class="log-mold">Ada jamur</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>