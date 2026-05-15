<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Kalkulator Takaran';
$activePage = 'kalkulator';
$db         = getDB();
$userId     = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['berat_buah'])) {
    $beratBuah = (float)$_POST['berat_buah'];
    if ($beratBuah > 0) {
        $gula = $beratBuah / 3;
        $air  = $gula * 10;
        $db->prepare("INSERT INTO kalkulator_history (user_id, berat_buah_g, gula_g, air_ml) VALUES (?, ?, ?, ?)")
           ->execute([$userId, $beratBuah, $gula, $air]);
        setFlash('success', 'Hasil kalkulasi disimpan ke riwayat.');
        header('Location: kalkulator.php');
        exit;
    }
}

$riwayat = $db->prepare("SELECT * FROM kalkulator_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$riwayat->execute([$userId]);
$histori = $riwayat->fetchAll();

require_once 'includes/header.php';
?>

<div class="row g-3">

    <div class="col-md-5">
        <div class="card">
            <h3 class="card-judul">🧮 Kalkulator Takaran</h3>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px; line-height: 1.6;">
                Masukkan berat kulit/limbah buah, sistem akan otomatis hitung
                gula dan air sesuai rasio ideal <strong>3 : 1 : 10</strong>.
            </p>

            <div class="form-group">
                <label>Berat Kulit Buah (gram)</label>
                <input
                    type="number"
                    id="beratInput"
                    name="berat_buah"
                    placeholder="contoh: 1500"
                    min="1"
                    step="0.1"
                    oninput="hitungOtomatis()"
                    autofocus
                >
            </div>
            <div class="kalk-hasil" id="hasilBox" style="display:none">
                <div class="kalk-rasio-label">Rasio ideal  3 : 1 : 10</div>

                <div class="kalk-row">
                    <div class="kalk-item">
                        <div class="kalk-icon">🍊</div>
                        <div class="kalk-bahan">Limbah Buah</div>
                        <div class="kalk-nilai" id="nilaiLimbah">—</div>
                        <div class="kalk-satuan">gram</div>
                    </div>
                    <div class="kalk-separator">:</div>
                    <div class="kalk-item">
                        <div class="kalk-icon">🍯</div>
                        <div class="kalk-bahan">Gula</div>
                        <div class="kalk-nilai" id="nilaiGula">—</div>
                        <div class="kalk-satuan">gram</div>
                    </div>
                    <div class="kalk-separator">:</div>
                    <div class="kalk-item">
                        <div class="kalk-icon">💧</div>
                        <div class="kalk-bahan">Air</div>
                        <div class="kalk-nilai" id="nilaiAir">—</div>
                        <div class="kalk-satuan">ml</div>
                    </div>
                </div>

                <div class="kalk-estimasi">
                    📦 Estimasi hasil: <strong id="estimasiVolume">—</strong> liter eco-enzyme
                </div>

                <div class="kalk-rekomendasi" id="rekomendasiWadah"></div>
            </div>

            <form method="POST" action="kalkulator.php" id="formSimpan" style="display:none;margin-top:12px">
                <input type="hidden" name="berat_buah" id="hiddenBerat">
                <button type="submit" class="btn btn-primary">💾 Simpan ke Riwayat →</button>
            </form>
        </div>

        <div class="card mt-3">
            <h4 style="font-size: 14px; margin-bottom: 10px;">💡 Tips Rasio</h4>
            <ul style="font-size: 12px; color: var(--text-muted); padding-left: 18px; line-height: 2;">
                <li>Gunakan gula merah / molase untuk fermentasi lebih aktif</li>
                <li>Air sebaiknya air bersih non-klorin (air sumur / matang)</li>
                <li>Wadah plastik atau kaca, jangan logam</li>
                <li>Fermentasi minimal <strong>3 bulan</strong> (90 hari)</li>
                <li>Aduk / kocok setiap minggu selama 30 hari pertama</li>
            </ul>
        </div>
    </div>

    <div class="col-md-7">
        <h2 class="section-title mb-2">📋 Riwayat Kalkulasi</h2>

        <?php if (empty($histori)): ?>
            <div class="card text-center p-4">
                <p class="text-muted small">Belum ada riwayat. Coba hitung sekarang!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Berat Buah</th>
                            <th>Gula</th>
                            <th>Air</th>
                            <th>Est. Volume</th>
                            <th>Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($histori as $h): ?>
                            <tr>
                                <td><?= number_format($h['berat_buah_g'], 0) ?> g</td>
                                <td><?= number_format($h['gula_g'], 0) ?> g</td>
                                <td><?= number_format($h['air_ml'], 0) ?> ml</td>
                                <td><?= round(($h['air_ml'] + $h['berat_buah_g'] + $h['gula_g']) / 1000, 2) ?> L</td>
                                <td style="font-size: 11px; color: var(--text-muted);">
                                    <?= date('d M Y, H:i', strtotime($h['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function hitungOtomatis() {
    const berat = parseFloat(document.getElementById('beratInput').value) || 0;
    const hasilBox   = document.getElementById('hasilBox');
    const formSimpan = document.getElementById('formSimpan');

    if (berat <= 0) {
        hasilBox.style.display   = 'none';
        formSimpan.style.display = 'none';
        return;
    }

    const gula  = berat / 3;
    const air   = gula * 10;
    const total = (berat + gula + air) / 1000; // liter

    document.getElementById('nilaiLimbah').textContent    = berat.toFixed(0);
    document.getElementById('nilaiGula').textContent      = gula.toFixed(0);
    document.getElementById('nilaiAir').textContent       = air.toFixed(0);
    document.getElementById('estimasiVolume').textContent = total.toFixed(2);
    document.getElementById('hiddenBerat').value          = berat;

    const rekEl = document.getElementById('rekomendasiWadah');
    let rek = '';
    if (total <= 2)       rek = '🪣 Gunakan toples kaca 2L atau ember kecil.';
    else if (total <= 5)  rek = '🪣 Gunakan galon 5L atau ember plastik food grade.';
    else if (total <= 10) rek = '🛢 Gunakan drum plastik 10L atau jerigen besar.';
    else                  rek = '🛢 Gunakan drum plastik ukuran besar (≥20L).';
    rekEl.textContent = rek;

    hasilBox.style.display   = 'block';
    formSimpan.style.display = 'block';
}
</script>

<?php require_once 'includes/footer.php'; ?>