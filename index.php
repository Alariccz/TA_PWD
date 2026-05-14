<?php
// index.php - dashboard user, dengan status siap panen

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

// kalau admin masuk ke sini, lempar ke admin_dashboard
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$pageTitle  = 'Dasbor Produksi';
$activePage = 'overview';
$db         = getDB();
$userId     = currentUserId();

// ---- statistik ----
$stmtActive = $db->prepare("SELECT COUNT(*) FROM batches WHERE user_id = ? AND status = 'active'");
$stmtActive->execute([$userId]);
$totalActive = $stmtActive->fetchColumn();

$stmtDays = $db->prepare("SELECT COALESCE(SUM(DATEDIFF(CURDATE(), start_date)), 0) FROM batches WHERE user_id = ? AND status = 'active'");
$stmtDays->execute([$userId]);
$totalDays = $stmtDays->fetchColumn();

$stmtVol = $db->prepare("SELECT COALESCE(SUM(volume_liters), 0) FROM batches WHERE user_id = ? AND status = 'active'");
$stmtVol->execute([$userId]);
$totalVolume = round($stmtVol->fetchColumn(), 1);

$stmtWaste = $db->prepare("
    SELECT COALESCE(SUM(bi.weight_grams) / 1000, 0)
    FROM batch_ingredients bi
    JOIN batches b ON bi.batch_id = b.id
    WHERE b.user_id = ? AND bi.type = 'Limbah organik'
");
$stmtWaste->execute([$userId]);
$totalWaste = round($stmtWaste->fetchColumn(), 1);

$stmtDone = $db->prepare("SELECT COUNT(*) FROM batches WHERE user_id = ? AND status = 'completed'");
$stmtDone->execute([$userId]);
$totalDone = $stmtDone->fetchColumn();

// 3 batch aktif terbaru + hitung hari panen
$stmtBatches = $db->prepare("
    SELECT *,
        DATEDIFF(target_date, CURDATE())  AS days_left,
        DATEDIFF(CURDATE(), start_date)   AS days_elapsed,
        DATEDIFF(target_date, start_date) AS total_days,
        DATEDIFF(
            DATE_ADD(COALESCE(tanggal_buat, start_date), INTERVAL 90 DAY),
            CURDATE()
        ) AS hari_panen
    FROM batches
    WHERE user_id = ? AND status = 'active'
    ORDER BY start_date DESC
    LIMIT 3
");
$stmtBatches->execute([$userId]);
$recentBatches = $stmtBatches->fetchAll();

$stmtTips = $db->query("SELECT * FROM troubleshooting WHERE severity = 'red' LIMIT 2");
$urgentTips = $stmtTips->fetchAll();

require_once 'includes/header.php';
?>

<!-- kartu statistik -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-value"><?= $totalActive ?></div>
        <div class="metric-label">Batch Aktif</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalDays ?></div>
        <div class="metric-label">Total Hari Fermentasi</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalVolume ?>L</div>
        <div class="metric-label">Volume Produksi</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalWaste ?>kg</div>
        <div class="metric-label">Limbah Diproses</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalDone ?></div>
        <div class="metric-label">Batch Selesai</div>
    </div>
</div>

<!-- batch aktif terbaru -->
<div class="section-header mt-3">
    <h2 class="section-title">Batch Aktif Terbaru</h2>
    <a href="batch.php" class="btn btn-sm">Lihat Semua →</a>
</div>

<?php if (empty($recentBatches)): ?>
    <p class="text-muted small">
        Belum ada batch aktif. <a href="batch.php">Tambah batch baru?</a>
    </p>
<?php else: ?>
    <div class="batch-grid">
        <?php foreach ($recentBatches as $batch): ?>
            <?php
            $pct      = $batch['total_days'] > 0 ? min(100, round(($batch['days_elapsed'] / $batch['total_days']) * 100)) : 0;
            $daysLeft = (int)$batch['days_left'];
            $hariPanen = (int)$batch['hari_panen'];
            ?>
            <div class="batch-card-item">
                <div class="batch-card-top">
                    <div>
                        <div class="batch-nama"><?= htmlspecialchars($batch['name']) ?></div>
                        <div class="batch-flavor-text"><?= htmlspecialchars($batch['flavor_label']) ?></div>
                    </div>
                    <div class="batch-vol-text"><?= $batch['volume_liters'] ?>L</div>
                </div>

                <div class="batch-progress-label-row">
                    <span class="batch-progress-txt">Progress</span>
                    <span class="batch-progress-txt"><?= $pct ?>%</span>
                </div>
                <div class="batch-prog-bar">
                    <div class="batch-prog-fill" style="width: <?= $pct ?>%"></div>
                </div>

                <!-- STATUS SIAP PANEN (90 hari dari tanggal_buat) -->
                <div style="margin: 6px 0; font-size: 11px;">
                    <?php if ($hariPanen > 0): ?>
                        <span style="color: var(--green-light);">🍃 Siap panen dalam <strong><?= $hariPanen ?> hari</strong></span>
                    <?php elseif ($hariPanen === 0): ?>
                        <span style="color: var(--amber-light);">⏰ Hari panen hari ini! Segera saring.</span>
                    <?php else: ?>
                        <span style="color: var(--red);">⚠️ Lewat <?= abs($hariPanen) ?> hari, segera panen!</span>
                    <?php endif; ?>
                </div>

                <div class="batch-meta-row">
                    <span>Mulai: <?= date('d M Y', strtotime($batch['start_date'])) ?></span>
                    <span class="<?= $daysLeft < 0 ? 'batch-terlambat' : '' ?>">
                        <?= $daysLeft >= 0 ? $daysLeft . ' hari lagi' : abs($daysLeft) . ' hari lewat' ?>
                    </span>
                </div>

                <div class="batch-aksi-row">
                    <a href="log_harian.php" class="btn-info" style="font-size: 11px; padding: 4px 10px;">🌿 Log Harian</a>
                    <a href="batch.php?detail=<?= $batch['id'] ?>" class="btn-purple">🔍 Detail</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- shortcut kalkulator -->
<div class="section-header mt-3">
    <h2 class="section-title">🧮 Kalkulator Takaran</h2>
    <a href="kalkulator.php" class="btn btn-sm">Buka →</a>
</div>
<div class="card" style="max-width: 500px;">
    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
        Hitung otomatis berapa gula dan air yang dibutuhkan dari berat limbah buahmu.
    </p>
    <a href="kalkulator.php" class="btn btn-primary" style="display:inline-block">Buka Kalkulator →</a>
</div>

<!-- tips darurat -->
<?php if (!empty($urgentTips)): ?>
    <div class="section-header mt-4">
        <h2 class="section-title">⚠️ Perhatian Penting</h2>
        <a href="trouble.php" class="btn btn-sm">Semua Tips →</a>
    </div>
    <div class="tip-grid">
        <?php foreach ($urgentTips as $tip): ?>
            <div class="tip-card red">
                <div class="tip-title"><?= htmlspecialchars($tip['title']) ?></div>
                <div class="tip-fix"><?= htmlspecialchars($tip['fix']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>