<?php

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

$pageTitle  = 'Pemecahan Masalah';
$activePage = 'trouble';
$db         = getDB();
$error      = '';

// ============================================================
// FUNGSI CHATBOT PINTAR
// ============================================================

$knowledgeBase = [
    'bau busuk|bau bangkai|baunya aneh|baunya menyengat|stnk|basi|anyir|bau tidak enak' => [
        'answer' => '🔴 **Waduh, bau busuk?** Tenang, saya bantu! Bau busuk biasanya terjadi karena:
1. Kelebihan air (terlalu encer)
2. Wadah tidak steril
3. Kontaminasi bakteri pembusuk

📌 **Solusi yang bisa kamu coba:**
• Tambah gula merah 100 gram per 10 liter
• Aduk rata dan tutup RAPAT
• Jangan dibuka-buka selama 3-5 hari

⚠️ Kalau baunya sudah kayak bangkai dan warna jadi hitam, terpaksa harus dibuang dan mulai batch baru ya.',
        'severity' => 'red'
    ],
    
    'bau asam|asam banget|baunya masam|tajam|perih di hidung' => [
        'answer' => '🟡 **Bau asam** itu sebenarnya WAJAR selama fermentasi. Tapi kalau terlalu kuat:
• Tambah air bersih sedikit (jangan lebih dari 5% volume)
• Atau tambah 50 gram gula per 10 liter
• Pastikan wadah tertutup rapat',
        'severity' => 'amber'
    ],
    
    'bau wangi|harum|fermentasi sehat|baunya enak|kayak tape|kayak buah' => [
        'answer' => '✅ **YES! Bau wangi = FERMENTASI SUKSES!** Pertahankan dengan:
• Jangan sering buka tutup
• Pastikan wadah kedap udara
• Teruskan sampai 3 bulan minimal',
        'severity' => 'green'
    ],
    
    'jamur|ada jamur|tumbuh jamur|kapur|bulu putih|bercak putih|jamur hitam|jamur hijau' => [
        'answer' => '🔴 **Jamur?** Langkah penyelamatan:
1. Buang lapisan jamur pakai sendok bersih
2. Tambah gula 50 gram per liter
3. Tutup RAPAT dan jangan buka selama seminggu
⚠️ Kalau jamur HITAM/HIJAU, batch HARUS DIBUANG!',
        'severity' => 'red'
    ],
    
    'ph rendah|ph 3|asam banget|ph terlalu rendah|kecut banget' => [
        'answer' => '🟡 **pH di bawah 3.5?** Solusi:
• Tambah air kapur (1 sendok teh per 10 liter)
• Atau encerkan dengan air bersih 5-10%',
        'severity' => 'amber'
    ],
    
    'ph tinggi|ph 7|nggak asam|tawar|fermentasi lambat' => [
        'answer' => '🟡 **pH di atas 4.5?** Solusi:
• Tambah gula 50 gram per liter
• Pastikan wadah KEDAP UDARA
• Suhu ideal 25-35°C',
        'severity' => 'amber'
    ],
    
    'gelembung|berbuih|busa|ada gelembung|fermentasi aktif' => [
        'answer' => '✅ **Gelembung = FERMENTASI HIDUP!** Pertahankan dengan jangan sering buka tutup dan suhu stabil 25-35°C.',
        'severity' => 'green'
    ],
    
    'nggak ada gelembung|diam aja|fermentasi mati|berhenti|sepiii' => [
        'answer' => '🟡 **Tidak ada gelembung?** Coba:
1. Tambah gula 100 gram per 10 liter
2. Aduk perlahan
3. Pastikan suhu 25-35°C
4. Tunggu 2-3 hari',
        'severity' => 'amber'
    ],
    
    'rasio|perbandingan|takaran|3 1 10|resep' => [
        'answer' => '📐 **Rasio ideal: 3 : 1 : 10**
• 3 bagian LIMBAH ORGANIK
• 1 bagian GULA
• 10 bagian AIR
Contoh: 300g limbah + 100g gula + 1L air',
        'severity' => 'green'
    ],
    
    'lama fermentasi|berapa hari|minimal|3 bulan|kapan selesai|kapan panen' => [
        'answer' => '⏱️ **Lama fermentasi:**
• 3 bulan → sudah bisa dipakai
• 6 bulan → lebih bagus
• 12 bulan → super konsentrat
Jangan sering buka tutup!',
        'severity' => 'green'
    ],
    
    'suhu|panas|dingin|temperatur|ideal berapa' => [
        'answer' => '🌡️ **Suhu ideal: 25-35°C**
• <20°C → fermentasi lambat
• >40°C → mikroba mati
Letakkan di tempat teduh tapi hangat.',
        'severity' => 'green'
    ],
    
    'cara pakai|penggunaan|aplikasi|digunakan buat apa' => [
        'answer' => '🧴 **Cara pakai eco-enzyme:**
• Pembersih lantai: 50ml/L air
• Pupuk tanaman: 10ml/L air
• Pengusir serangga: 20ml/L air
• Saluran mampet: 100ml murni',
        'severity' => 'green'
    ],
    
    'manfaat|kegunaan|untuk apa|keuntungan' => [
        'answer' => '🌿 **Manfaat eco-enzyme:**
✅ Pembersih alami
✅ Pupuk organik
✅ Mengurai limbah
✅ Mengusir serangga
✅ Menetralisir bau',
        'severity' => 'green'
    ],
    
    'tambah batch|cara buat batch|batch baru|bikin batch' => [
        'answer' => '📦 **Cara tambah batch:**
1. Klik menu "Kelola Batch"
2. Isi nama batch, rasa, tanggal, volume
3. Klik "Simpan Batch"',
        'severity' => 'green'
    ],
    
    'ganti password|ubah password|lupa password' => [
        'answer' => '🔐 **Cara ganti password:**
1. Klik menu "Profil Saya"
2. Scroll ke "Ganti Password"
3. Masukkan password lama dan baru
4. Klik "Ganti Password"',
        'severity' => 'amber'
    ],
    
    'halo|hai|hey|permisi|pagi|siang|malam' => [
        'answer' => '🌿 **Halo!** Ada yang bisa saya bantu? Tanyakan tentang fermentasi, cara pakai eco-enzyme, atau fitur website ya!',
        'severity' => 'green'
    ],
    
    'makasih|terima kasih|thanks|thank you' => [
        'answer' => '😊 **Sama-sama!** Senang bisa membantu. Semangat terus bikin eco-enzyme-nya! 🌿💪',
        'severity' => 'green'
    ],
];

function getBestMatch($userMessage, $knowledgeBase) {
    $userMessage = strtolower($userMessage);
    $bestMatch = null;
    $bestScore = 0;
    
    foreach ($knowledgeBase as $keywords => $data) {
        $keywordList = explode('|', $keywords);
        $maxScore = 0;
        
        foreach ($keywordList as $keyword) {
            $keyword = trim($keyword);
            if (strpos($userMessage, $keyword) !== false) {
                $score = strlen($keyword);
                if ($score > $maxScore) $maxScore = $score;
            }
            similar_text($keyword, $userMessage, $similarity);
            if ($similarity > 60 && $similarity > $maxScore) $maxScore = $similarity;
        }
        
        if ($maxScore > $bestScore) {
            $bestScore = $maxScore;
            $bestMatch = $data;
        }
    }
    
    if ($bestScore < 10) {
        return [
            'answer' => '🤖 **Maaf, saya kurang paham.** Coba tanyakan: bau (busuk/asam/wangi), jamur, pH, gelembung, rasio 3:1:10, lama fermentasi, suhu ideal, cara pakai, atau manfaat eco-enzyme.',
            'severity' => 'info'
        ];
    }
    return $bestMatch;
}

function getAIResponse($userMessage) {
    global $knowledgeBase;
    return getBestMatch($userMessage, $knowledgeBase);
}

// ============================================================
// PROSES POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'chat') {
        $userMessage = trim($_POST['message'] ?? '');
        if (!empty($userMessage)) {
            $aiData = getAIResponse($userMessage);
            
            if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];
            
            $_SESSION['chat_history'][] = ['role' => 'user', 'message' => $userMessage, 'time' => date('H:i')];
            $_SESSION['chat_history'][] = ['role' => 'ai', 'message' => $aiData['answer'], 'severity' => $aiData['severity'], 'time' => date('H:i')];
            
            if (count($_SESSION['chat_history']) > 50) $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -50);
        }
        header('Location: trouble.php');
        exit;
    }
    
    if ($action === 'clear_chat') {
        $_SESSION['chat_history'] = [];
        header('Location: trouble.php');
        exit;
    }

    if ($action === 'tambah_tip') {
        $title = trim($_POST['title'] ?? '');
        $fix = trim($_POST['fix'] ?? '');
        $severity = $_POST['severity'] ?? 'amber';
        if (!in_array($severity, ['red', 'amber', 'green'])) $severity = 'amber';
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
$validSeverity = ['all', 'red', 'amber', 'green'];
if (!in_array($filterSeverity, $validSeverity)) $filterSeverity = 'all';

if ($filterSeverity === 'all') {
    $stmt = $db->query("SELECT * FROM troubleshooting ORDER BY FIELD(severity,'red','amber','green'), id ASC");
} else {
    $stmt = $db->prepare("SELECT * FROM troubleshooting WHERE severity = ? ORDER BY id ASC");
    $stmt->execute([$filterSeverity]);
}
$tips = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<style>
/* ============================================================ */
/* CSS CHATBOT - LANGSUNG DISATUKAN */
/* ============================================================ */
.chatbot-section {
    margin-bottom: 24px;
}

.chat-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 480px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: var(--bg-main);
}

.chat-message {
    display: flex;
    margin-bottom: 12px;
}

.chat-message.user { justify-content: flex-end; }
.chat-message.ai { justify-content: flex-start; }

.chat-bubble {
    max-width: 85%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13px;
    line-height: 1.5;
}

.chat-message.user .chat-bubble {
    background: var(--green);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-message.ai .chat-bubble {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    color: var(--text-main);
    border-bottom-left-radius: 4px;
}

.chat-bubble.severity-red { border-left: 3px solid #ef4444; }
.chat-bubble.severity-amber { border-left: 3px solid #f59e0b; }
.chat-bubble.severity-green { border-left: 3px solid #10b981; }
.chat-bubble.severity-info { border-left: 3px solid #3b82f6; }

.chat-time {
    font-size: 9px;
    opacity: 0.6;
    margin-top: 4px;
    display: block;
}

.chat-input-area {
    padding: 12px;
    background: var(--bg-card);
    border-top: 1px solid var(--border-color);
}

.chat-input-group {
    display: flex;
    gap: 10px;
}

.chat-input-group input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 40px;
    font-size: 13px;
    outline: none;
    background: var(--bg-main);
    color: var(--text-main);
}

.chat-input-group input:focus {
    border-color: var(--green);
}

.chat-input-group button {
    background: var(--green);
    border: none;
    border-radius: 40px;
    padding: 0 20px;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.chat-input-group button:hover {
    background: var(--green-light);
}

.chat-welcome {
    text-align: center;
    padding: 20px;
    color: var(--text-muted);
}

.chat-welcome h4 {
    margin: 10px 0 5px;
    font-size: 16px;
}

.suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}

.suggestion-btn {
    background: var(--green-dark);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
    cursor: pointer;
    color: var(--text-dim);
    transition: all 0.2s;
}

.suggestion-btn:hover {
    background: var(--green);
    color: white;
}

.clear-chat-btn {
    background: none;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
    cursor: pointer;
    color: var(--text-muted);
}

.clear-chat-btn:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
}

.chat-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.flash-msg.error {
    background: rgba(239,68,68,0.1);
    border-left: 3px solid #ef4444;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 12px;
}
</style>

<!-- ============================================================ -->
<!-- CHATBOT SECTION -->
<!-- ============================================================ -->
<div class="chatbot-section">
    <div class="chat-header-actions">
        <h3 style="font-family: var(--font-judul); font-size: 18px; margin: 0;">🤖 Tanya AI EcoEnzyme</h3>
        <form method="POST" action="trouble.php" style="display: inline;">
            <input type="hidden" name="action" value="clear_chat">
            <button type="submit" class="clear-chat-btn" onclick="return confirm('Hapus semua riwayat chat?')">🗑 Hapus Riwayat</button>
        </form>
    </div>
    
    <div class="chat-container">
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($_SESSION['chat_history'])): ?>
                <div class="chat-welcome">
                    <div style="font-size: 40px;">🌿🧪</div>
                    <h4>Halo! Ada yang bisa saya bantu?</h4>
                    <p>Saya asisten AI eco-enzyme. Bisa jawab pertanyaan <strong>panjang lebar & bahasa gaul</strong> juga!<br>Coba tanyakan:</p>
                    <div class="suggestions">
                        <button class="suggestion-btn" onclick="setQuestion('Batch gue baunya kayak bangkai, gimana dong?')">😷 Bau bangkai</button>
                        <button class="suggestion-btn" onclick="setQuestion('Di toples gue ada bulu putih, bahaya ga?')">🍄 Jamur putih</button>
                        <button class="suggestion-btn" onclick="setQuestion('Cara buat batch baru gimana?')">📦 Tambah batch</button>
                        <button class="suggestion-btn" onclick="setQuestion('Gue lupa password, bisa minta tolong?')">🔐 Lupa password</button>
                        <button class="suggestion-btn" onclick="setQuestion('Eco-enzyme buat apa aja?')">🌿 Manfaat</button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($_SESSION['chat_history'] as $chat): ?>
                    <div class="chat-message <?= $chat['role'] ?>">
                        <div class="chat-bubble <?= isset($chat['severity']) ? 'severity-' . $chat['severity'] : '' ?>">
                            <?= nl2br(htmlspecialchars($chat['message'])) ?>
                            <span class="chat-time"><?= $chat['time'] ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="chat-input-area">
            <form method="POST" action="trouble.php" id="chatForm">
                <input type="hidden" name="action" value="chat">
                <div class="chat-input-group">
                    <input type="text" name="message" id="messageInput" placeholder="Tanyakan apapun... (bisa pake bahasa gaul)" autocomplete="off" required>
                    <button type="submit">Kirim →</button>
                </div>
            </form>
            <div class="suggestions">
                <button class="suggestion-btn" onclick="setQuestion('Ciri fermentasi sehat tuh kayak gimana?')">✅ Ciri sehat</button>
                <button class="suggestion-btn" onclick="setQuestion('Batch gue nggak ada gelembung')">🫧 No gelembung</button>
                <button class="suggestion-btn" onclick="setQuestion('Rasio 3 1 10 itu maksudnya?')">📐 Rasio</button>
                <button class="suggestion-btn" onclick="setQuestion('Makasih ya!')">😊 Makasih</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- FORM TAMBAH TIP -->
<!-- ============================================================ -->
<div class="card mb-3">
    <h3 class="card-judul">Tambah Tip Baru</h3>

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
                <option value="green">Ringan</option>
                <option value="amber" selected>Sedang</option>
                <option value="red">Serius</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Tip</button>
    </form>
</div>

<!-- ============================================================ -->
<!-- FILTER TIP -->
<!-- ============================================================ -->
<div class="d-flex gap-2 mb-3">
    <a href="trouble.php?severity=all"   class="btn btn-sm <?= $filterSeverity === 'all'   ? 'btn-primary' : '' ?>">Semua</a>
    <a href="trouble.php?severity=red"   class="btn btn-sm <?= $filterSeverity === 'red'   ? 'btn-primary' : '' ?>">Serius</a>
    <a href="trouble.php?severity=amber" class="btn btn-sm <?= $filterSeverity === 'amber' ? 'btn-primary' : '' ?>">Sedang</a>
    <a href="trouble.php?severity=green" class="btn btn-sm <?= $filterSeverity === 'green' ? 'btn-primary' : '' ?>">Ringan</a>
</div>

<!-- ============================================================ -->
<!-- DAFTAR TIP -->
<!-- ============================================================ -->
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
                <div class="tip-fix"><?= nl2br(htmlspecialchars($tip['fix'])) ?></div>
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

<script>
var chatMessages = document.getElementById('chatMessages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

function setQuestion(question) {
    document.getElementById('messageInput').value = question;
    document.getElementById('chatForm').submit();
}

var messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('chatForm').submit();
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>