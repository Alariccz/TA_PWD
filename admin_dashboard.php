<?php
// admin_dashboard.php - dasbor khusus admin
// menampilkan statistik global seluruh sistem

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAdmin(); // tendang balik ke index.php kalau bukan admin

$pageTitle  = 'Dasbor Admin';
$activePage = 'admin_overview';
$db         = getDB();

// ---- statistik global ----

// total user terdaftar
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// total batch aktif di seluruh sistem
$totalBatchAktif = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'active'")->fetchColumn();

// total batch selesai
$totalBatchSelesai = $db->query("SELECT COUNT(*) FROM batches WHERE status = 'completed'")->fetchColumn();

// total volume produksi seluruh sistem
$totalVolume = round($db->query("SELECT COALESCE(SUM(volume_liters), 0) FROM batches")->fetchColumn(), 1);

// total log harian yang tercatat
$totalLogs = $db->query("SELECT COUNT(*) FROM batch_logs")->fetchColumn();

// total limbah diproses (kg)
$totalLimbah = round($db->query("
    SELECT COALESCE(SUM(bi.weight_grams) / 1000, 0)
    FROM batch_ingredients bi
    WHERE bi.type = 'Limbah organik'
")->fetchColumn(), 1);

// daftar semua user + statistik batch mereka
$stmtUsers = $db->query("
    SELECT
        u.id,
        u.name,
        u.email,
        u.role,
        u.created_at,
        COUNT(b.id)                                                  AS total_batch,
        SUM(CASE WHEN b.status = 'active'    THEN 1 ELSE 0 END)     AS batch_aktif,
        SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END)     AS batch_selesai,
        COALESCE(SUM(b.volume_liters), 0)                            AS total_volume
    FROM users u
    LEFT JOIN batches b ON b.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$semuaUser = $stmtUsers->fetchAll();

// 5 batch terbaru di seluruh sistem
$stmtBatch = $db->query("
    SELECT b.*, u.name AS owner_name,
        DATEDIFF(b.target_date, CURDATE())  AS days_left,
        DATEDIFF(CURDATE(), b.start_date)   AS days_elapsed,
        DATEDIFF(b.target_date, b.start_date) AS total_days,
        DATEDIFF(DATE_ADD(COALESCE(b.tanggal_buat, b.start_date), INTERVAL 90 DAY), CURDATE()) AS hari_panen
    FROM batches b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.start_date DESC
    LIMIT 5
");
$batchTerbaru = $stmtBatch->fetchAll();

require_once 'includes/header.php';
?>

<!-- statistik global -->
<div class="metric-grid" style="grid-template-columns: repeat(6, 1fr);">
    <div class="metric-card">
        <div class="metric-value"><?= $totalUsers ?></div>
        <div class="metric-label">Total User</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalBatchAktif ?></div>
        <div class="metric-label">Batch Aktif</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalBatchSelesai ?></div>
        <div class="metric-label">Batch Selesai</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalVolume ?>L</div>
        <div class="metric-label">Total Volume</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalLimbah ?>kg</div>
        <div class="metric-label">Limbah Diproses</div>
    </div>
    <div class="metric-card">
        <div class="metric-value"><?= $totalLogs ?></div>
        <div class="metric-label">Total Log</div>
    </div>
</div>

<div class="row g-3 mt-1">

    <!-- tabel user -->
    <div class="col-md-7">
        <div class="section-header mb-2">
            <h2 class="section-title">👥 Semua User</h2>
            <a href="admin_users.php" class="btn btn-sm">Kelola User →</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Batch</th>
                        <th>Volume</th>
                        <th>Bergabung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semuaUser as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="role-badge <?= $u['role'] === 'admin' ? 'role-badge--admin' : 'role-badge--user' ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td><?= $u['total_batch'] ?> (<?= $u['batch_aktif'] ?> aktif)</td>
                            <td><?= round($u['total_volume'], 1) ?>L</td>
                            <td style="font-size: 11px;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 5 batch terbaru -->
    <div class="col-md-5">
        <h2 class="section-title mb-2">🌿 5 Batch Terbaru</h2>
        <?php foreach ($batchTerbaru as $b): ?>
            <?php
            $pct = $b['total_days'] > 0
                ? min(100, round(($b['days_elapsed'] / $b['total_days']) * 100))
                : 0;
            $hariPanen = (int)$b['hari_panen'];
            ?>
            <div class="card mb-2" style="padding: 12px;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <div style="font-size: 13px; font-weight: 600; color: var(--text-main);">
                            <?= htmlspecialchars($b['name']) ?>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            oleh <?= htmlspecialchars($b['owner_name']) ?>
                        </div>
                    </div>
                    <span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span>
                </div>

                <div class="batch-progress-label-row">
                    <span class="batch-progress-txt">Progress</span>
                    <span class="batch-progress-txt"><?= $pct ?>%</span>
                </div>
                <div class="batch-prog-bar">
                    <div class="batch-prog-fill" style="width: <?= $pct ?>%"></div>
                </div>

                <!-- status siap panen -->
                <div style="margin-top: 6px; font-size: 11px;">
                    <?php if ($b['status'] === 'active'): ?>
                        <?php if ($hariPanen > 0): ?>
                            <span style="color: var(--green-light);">🍃 Siap panen dalam <strong><?= $hariPanen ?> hari</strong></span>
                        <?php elseif ($hariPanen === 0): ?>
                            <span style="color: var(--amber-light);">⏰ Hari panen hari ini!</span>
                        <?php else: ?>
                            <span style="color: var(--red);">⚠️ Lewat <?= abs($hariPanen) ?> hari dari jadwal panen</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: var(--text-muted);">✅ Batch selesai</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>