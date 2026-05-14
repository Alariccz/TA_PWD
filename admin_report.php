<?php
// admin_report.php - laporan global produksi

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireAdmin();

$pageTitle  = 'Laporan Global';
$activePage = 'admin_report';
$db         = getDB();

// produksi per bulan (12 bulan terakhir)
$stmtBulan = $db->query("
    SELECT
        DATE_FORMAT(start_date, '%Y-%m')    AS bulan,
        COUNT(*)                             AS jumlah_batch,
        COALESCE(SUM(volume_liters), 0)      AS total_volume
    FROM batches
    WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(start_date, '%Y-%m')
    ORDER BY bulan ASC
");
$dataBulan = $stmtBulan->fetchAll();

// top 5 user paling produktif
$stmtTop = $db->query("
    SELECT u.name, u.avatar,
        COUNT(b.id)                       AS total_batch,
        COALESCE(SUM(b.volume_liters), 0) AS total_volume,
        COALESCE(SUM(bi.weight_grams)/1000, 0) AS total_limbah_kg
    FROM users u
    LEFT JOIN batches b        ON b.user_id   = u.id
    LEFT JOIN batch_ingredients bi ON bi.batch_id = b.id AND bi.type = 'Limbah organik'
    GROUP BY u.id
    ORDER BY total_volume DESC
    LIMIT 5
");
$topUsers = $stmtTop->fetchAll();

// distribusi status batch
$stmtStatus = $db->query("
    SELECT status, COUNT(*) AS jumlah
    FROM batches
    GROUP BY status
");
$statusData = [];
foreach ($stmtStatus->fetchAll() as $row) {
    $statusData[$row['status']] = $row['jumlah'];
}

// ringkasan bahan terbanyak dipakai
$stmtBahan = $db->query("
    SELECT name, COUNT(*) AS frekuensi, COALESCE(SUM(weight_grams)/1000, 0) AS total_kg
    FROM batch_ingredients
    GROUP BY name
    ORDER BY frekuensi DESC
    LIMIT 8
");
$topBahan = $stmtBahan->fetchAll();

require_once 'includes/header.php';
?>

<div class="row g-3">

    <!-- produksi per bulan -->
    <div class="col-md-8">
        <div class="card">
            <h3 class="card-judul">📈 Produksi per Bulan (12 Bulan Terakhir)</h3>
            <?php if (empty($dataBulan)): ?>
                <p class="text-muted small">Belum ada data.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Jumlah Batch</th>
                                <th>Total Volume</th>
                                <th>Grafik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxVol = max(array_column($dataBulan, 'total_volume')) ?: 1;
                            foreach ($dataBulan as $row):
                                $pct = round(($row['total_volume'] / $maxVol) * 100);
                            ?>
                                <tr>
                                    <td><?= date('M Y', strtotime($row['bulan'] . '-01')) ?></td>
                                    <td><?= $row['jumlah_batch'] ?> batch</td>
                                    <td><?= round($row['total_volume'], 1) ?>L</td>
                                    <td>
                                        <div class="mini-progress-wrap">
                                            <div class="mini-progress-track">
                                                <div class="mini-progress-fill" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <span class="mini-progress-pct"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- kolom kanan -->
    <div class="col-md-4">

        <!-- top user -->
        <div class="card mb-3">
            <h3 class="card-judul">🏆 Top Produsen</h3>
            <?php foreach ($topUsers as $i => $u): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border-color)">
                    <div style="font-size:16px;color:var(--text-muted);min-width:18px"><?= $i + 1 ?></div>
                    <div class="sb-avatar" style="width:28px;height:28px;font-size:11px;"><?= htmlspecialchars($u['avatar']) ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;color:var(--text-main);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($u['name']) ?></div>
                        <div style="font-size:11px;color:var(--text-muted)"><?= $u['total_batch'] ?> batch · <?= round($u['total_volume'],1) ?>L · <?= round($u['total_limbah_kg'],1) ?>kg limbah</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- status batch -->
        <div class="card mb-3">
            <h3 class="card-judul">📊 Status Batch</h3>
            <?php
            $labels = ['active' => '🟢 Aktif', 'completed' => '✅ Selesai', 'cancelled' => '❌ Dibatalkan'];
            $total  = array_sum($statusData) ?: 1;
            foreach ($labels as $key => $label):
                $n   = $statusData[$key] ?? 0;
                $pct = round(($n / $total) * 100);
            ?>
                <div style="margin-bottom:10px">
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                        <span><?= $label ?></span>
                        <span style="color:var(--text-muted)"><?= $n ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="batch-prog-bar">
                        <div class="batch-prog-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- bahan terbanyak -->
        <div class="card">
            <h3 class="card-judul">🧪 Bahan Paling Sering Dipakai</h3>
            <?php foreach ($topBahan as $b): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid var(--border-color);font-size:12px">
                    <span style="color:var(--text-dim)"><?= htmlspecialchars($b['name']) ?></span>
                    <span style="color:var(--text-muted)"><?= $b['frekuensi'] ?>× · <?= round($b['total_kg'],1) ?>kg</span>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>