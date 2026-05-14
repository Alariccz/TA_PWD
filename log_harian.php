<?php
// log_harian.php - form input kondisi batch harian (user)
// lebih fokus ke kondisi visual/sensorik dibanding logs.php

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Log Harian';
$activePage = 'log_harian';
$db         = getDB();
$userId     = currentUserId();
$error      = '';

// batch aktif milik user ini
$stmtBatch = $db->prepare("SELECT id, name, flavor_label FROM batches WHERE user_id = ? AND status = 'active' ORDER BY start_date DESC");
$stmtBatch->execute([$userId]);
$batchAktif = $stmtBatch->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';

    if ($action === 'tambah_log_harian') {
        $batchId = (int)($_POST['batch_id'] ?? 0);
        $note    = trim($_POST['note'] ?? '');
        $ph      = !empty($_POST['ph_level']) ? (float)$_POST['ph_level'] : null;
        $bubbles = isset($_POST['has_bubbles']) ? 1 : 0;
        $mold    = isset($_POST['has_mold'])    ? 1 : 0;
        $smell   = $_POST['smell']  ?? 'normal';
        $warna   = trim($_POST['warna'] ?? '');

        $validSmell = ['normal', 'sour', 'rotten', 'fragrant'];
        if (!in_array($smell, $validSmell)) $smell = 'normal';

        if (empty($note) || $batchId <= 0) {
            $error = 'Pilih batch dan isi catatan.';
        } else {
            // verifikasi batch milik user
            $cek = $db->prepare("SELECT id FROM batches WHERE id = ? AND user_id = ?");
            $cek->execute([$batchId, $userId]);
            if (!$cek->fetch()) {
                $error = 'Batch tidak valid.';
            } else {
                $db->prepare("
                    INSERT INTO batch_logs (batch_id, user_id, note, ph_level, has_bubbles, has_mold, smell, warna)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$batchId, $userId, $note, $ph, $bubbles, $mold, $smell, $warna]);

                setFlash('success', 'Log harian berhasil disimpan! 🌿');
                header('Location: log_harian.php');
                exit;
            }
        }
    }

    if ($action === 'hapus_log') {
        $logId = (int)($_POST['log_id'] ?? 0);
        $db->prepare("DELETE FROM batch_logs WHERE id = ? AND user_id = ?")->execute([$logId, $userId]);
        setFlash('success', 'Log dihapus.');
        header('Location: log_harian.php');
        exit;
    }
}

// 15 log terbaru milik user
$stmtLogs = $db->prepare("
    SELECT l.*, b.name AS batch_name, b.flavor_label
    FROM batch_logs l
    JOIN batches b ON l.batch_id = b.id
    WHERE l.user_id = ?
    ORDER BY l.log_date DESC
    LIMIT 15
");
$stmtLogs->execute([$userId]);
$logs = $stmtLogs->fetchAll();

require_once 'includes/header.php';
?>

<div class="row g-3">

    <!-- FORM TAMBAH LOG -->
    <div class="col-md-4">
        <div class="card">
            <h3 class="card-judul">🌿 Catat Kondisi Hari Ini</h3>

            <?php if ($error): ?>
                <div class="flash-msg error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($batchAktif)): ?>
                <p class="text-muted small">Belum ada batch aktif. <a href="batch.php">Buat batch dulu</a>.</p>
            <?php else: ?>

            <form method="POST" action="log_harian.php">
                <input type="hidden" name="action" value="tambah_log_harian">

                <div class="form-group">
                    <label>Batch yang Dicatat</label>
                    <select name="batch_id" required class="form-select-plain">
                        <option value="">-- Pilih Batch --</option>
                        <?php foreach ($batchAktif as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- bau fermentasi - dengan preview visual -->
                <div class="form-group">
                    <label>Bau Fermentasi</label>
                    <div class="smell-picker" id="smellPicker">
                        <?php
                        $smellOpts = [
                            'normal'   => ['emoji' => '😐', 'label' => 'Normal',  'class' => 'smell-normal'],
                            'sour'     => ['emoji' => '😮', 'label' => 'Asam',    'class' => 'smell-sour'],
                            'fragrant' => ['emoji' => '😊', 'label' => 'Wangi',   'class' => 'smell-fragrant'],
                            'rotten'   => ['emoji' => '🤢', 'label' => 'Busuk',   'class' => 'smell-rotten'],
                        ];
                        foreach ($smellOpts as $val => $opt): ?>
                            <label class="smell-option">
                                <input type="radio" name="smell" value="<?= $val ?>" <?= $val === 'normal' ? 'checked' : '' ?>>
                                <div class="smell-btn <?= $opt['class'] ?>">
                                    <span class="smell-emoji"><?= $opt['emoji'] ?></span>
                                    <span><?= $opt['label'] ?></span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Warna Cairan (opsional)</label>
                    <input type="text" name="warna" placeholder="contoh: Cokelat kemerahan" value="<?= htmlspecialchars($_POST['warna'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Level pH (opsional)</label>
                    <input type="number" name="ph_level" step="0.01" min="0" max="14" placeholder="0.00 – 14.00">
                </div>

                <div class="form-group">
                    <div style="display:flex;gap:16px">
                        <label class="checkbox-label">
                            <input type="checkbox" name="has_bubbles" value="1">
                            🫧 Ada gelembung?
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="has_mold" value="1">
                            ⚠️ Ada jamur?
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan Tambahan</label>
                    <textarea name="note" rows="3" placeholder="Tulis observasi hari ini..." required><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Log Hari Ini →</button>
            </form>

            <?php endif; ?>
        </div>
    </div>

    <!-- DAFTAR LOG -->
    <div class="col-md-8">
        <h2 class="section-title mb-3">15 Log Terbaru</h2>

        <?php if (empty($logs)): ?>
            <div class="card text-center p-4">
                <p class="text-muted small">Belum ada log. Mulai catat kondisi batch kamu hari ini! 🌱</p>
            </div>
        <?php else: ?>
            <?php
            $smellLabel = ['normal' => '😐 Normal', 'sour' => '😮 Asam', 'fragrant' => '😊 Wangi', 'rotten' => '🤢 Busuk'];
            $smellClass = ['normal' => 'smell-normal', 'sour' => 'smell-sour', 'fragrant' => 'smell-fragrant', 'rotten' => 'smell-rotten'];
            foreach ($logs as $log):
            ?>
                <div class="card log-card">
                    <div class="log-header-row">
                        <div>
                            <span class="log-batch-pill"><?= htmlspecialchars($log['batch_name']) ?></span>
                            <div class="log-meta-text">
                                <?= date('d M Y, H:i', strtotime($log['log_date'])) ?>
                            </div>
                        </div>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="hapus_log">
                            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
                            <button type="submit" class="btn-danger" style="font-size:10px;padding:3px 8px"
                                onclick="return confirm('Hapus log ini?')">🗑</button>
                        </form>
                    </div>

                    <!-- warna cairan -->
                    <?php if (!empty($log['warna'])): ?>
                        <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px">
                            🎨 Warna: <span style="color:var(--text-dim)"><?= htmlspecialchars($log['warna']) ?></span>
                        </div>
                    <?php endif; ?>

                    <p class="log-note-text"><?= nl2br(htmlspecialchars($log['note'])) ?></p>

                    <div class="log-indikator">
                        <?php if ($log['ph_level'] !== null): ?>
                            <span class="<?= $log['ph_level'] < 4 ? 'ph-good' : ($log['ph_level'] < 7 ? 'ph-warn' : 'ph-bad') ?>">
                                pH <?= $log['ph_level'] ?>
                            </span>
                        <?php endif; ?>

                        <span class="<?= $smellClass[$log['smell']] ?? 'smell-normal' ?>">
                            <?= $smellLabel[$log['smell']] ?? $log['smell'] ?>
                        </span>

                        <?php if ($log['has_bubbles']): ?>
                            <span class="log-bubble">🫧 Ada gelembung</span>
                        <?php endif; ?>

                        <?php if ($log['has_mold']): ?>
                            <span class="log-mold">⚠️ Ada jamur — segera cek!</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>