<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

requireLogin();

if (isAdmin()) {
    header('Location: admin_trouble.php');
    exit;
}

$pageTitle  = 'Pemecahan Masalah';
$activePage = 'trouble';
$db         = getDB();

<<<<<<< HEAD
$knowledgeBase = [
    'bau busuk|bau bangkai|baunya aneh|baunya menyengat|stnk|basi|anyir|bau tidak enak' => [
        'answer' => '🔴 **Waduh, bau busuk?** Tenang, saya bantu! Bau busuk biasanya terjadi karena:
1. Kelebihan air (terlalu encer)
2. Wadah tidak steril
3. Kontaminasi bakteri pembusuk
=======
// ============================================================
// FUNGSI CHATBOT PINTAR (Powered by Gemini API)
// ============================================================

function getGeminiResponse($userMessage) {
    // API Key kamu (Pastikan tidak ada spasi di awal/akhir)
    $apiKey = trim('AIzaSyBO-8nolwTXPNg-ejv7WEVUNUsu8N-H6Ec'); 
    
    // URL sudah BERSIH, tidak pakai ?key= lagi. Pakai v1 yang paling stabil.
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    $systemInstruction = "Kamu adalah asisten pakar Eco-Enzyme yang ramah, asyik, dan suka pakai emoji. 
    Aturan menjawab:
    1. Jawab ringkas, padat, maksimal 2-3 paragraf.
    2. Rasio standar: 3 (bahan organik) : 1 (gula) : 10 (air). Fermentasi 3 bulan.
    3. Bau bangkai/jamur hitam = gagal/buang.
    4. Bau asam/wangi/jamur putih/gelembung = sehat/normal.
    5. Jawab dalam bahasa Indonesia kasual.";
>>>>>>> a1c81894ceb1dc2909a44af6976b1194e5d2876b

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $systemInstruction . "\n\nPertanyaan User: " . $userMessage]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // PINDAHKAN API KEY KE HTTP HEADERS DI SINI
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey
    ]);
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    // Bypass SSL khusus untuk local server (Laragon/XAMPP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Mode Debug
    if ($curlError) {
        return ['answer' => '🚨 **Error Koneksi (cURL):** ' . $curlError, 'severity' => 'red'];
    }

    if ($httpCode != 200) {
        return ['answer' => '🚨 **Error API Google (Kode ' . $httpCode . '):** ' . $response, 'severity' => 'red'];
    }

    // Olah jawaban jika sukses
    if ($httpCode == 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $aiText = $data['candidates'][0]['content']['parts'][0]['text'];
            
            $severity = 'info'; 
            if (stripos($aiText, 'buang') !== false || stripos($aiText, 'bahaya') !== false || stripos($aiText, 'gagal') !== false) {
                $severity = 'red';
            } elseif (stripos($aiText, 'normal') !== false || stripos($aiText, 'sehat') !== false || stripos($aiText, 'berhasil') !== false || stripos($aiText, 'wajar') !== false) {
                $severity = 'green';
            } elseif (stripos($aiText, 'tambah') !== false || stripos($aiText, 'tunggu') !== false) {
                $severity = 'amber';
            }

            return ['answer' => $aiText, 'severity' => $severity];
        }
    }
    
    return ['answer' => 'Aduh, otak AI-nya lagi nge-lag nih. Coba tanya lagi sebentar ya! 😅', 'severity' => 'amber'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'chat') {
        $userMessage = trim($_POST['message'] ?? '');
        if (!empty($userMessage)) {
            
            // 👇 INI YANG BERUBAH: Memanggil fungsi Gemini 👇
            $aiData = getGeminiResponse($userMessage);

            if (!isset($_SESSION['chat_history'])) $_SESSION['chat_history'] = [];

            $_SESSION['chat_history'][] = ['role' => 'user', 'message' => $userMessage, 'time' => date('H:i')];
            $_SESSION['chat_history'][] = ['role' => 'ai', 'message' => $aiData['answer'], 'severity' => $aiData['severity'], 'time' => date('H:i')];

            if (count($_SESSION['chat_history']) > 50) {
                $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -50);
            }
        }
        header('Location: trouble.php');
        exit;
    }

    if ($action === 'clear_chat') {
        $_SESSION['chat_history'] = [];
        header('Location: trouble.php');
        exit;
    }
}

$filterSeverity = $_GET['severity'] ?? 'all';
$validSeverity  = ['all', 'red', 'amber', 'green'];
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
.chatbot-section { margin-bottom: 24px; }

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

.chat-message { display: flex; margin-bottom: 12px; }
.chat-message.user { justify-content: flex-end; }
.chat-message.ai   { justify-content: flex-start; }

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

.chat-bubble.severity-red   { border-left: 3px solid #ef4444; }
.chat-bubble.severity-amber { border-left: 3px solid #f59e0b; }
.chat-bubble.severity-green { border-left: 3px solid #10b981; }
.chat-bubble.severity-info  { border-left: 3px solid #3b82f6; }

.chat-time { font-size: 9px; opacity: 0.6; margin-top: 4px; display: block; }

.chat-input-area {
    padding: 12px;
    background: var(--bg-card);
    border-top: 1px solid var(--border-color);
}

.chat-input-group { display: flex; gap: 10px; }

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

.chat-input-group input:focus { border-color: var(--green); }

.chat-input-group button {
    background: var(--green);
    border: none;
    border-radius: 40px;
    padding: 0 20px;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.chat-input-group button:hover { background: var(--green-light); }

.chat-welcome { text-align: center; padding: 20px; color: var(--text-muted); }
.chat-welcome h4 { margin: 10px 0 5px; font-size: 16px; }

.suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }

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

.suggestion-btn:hover { background: var(--green); color: white; }

.clear-chat-btn {
    background: none;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 11px;
    cursor: pointer;
    color: var(--text-muted);
}

.clear-chat-btn:hover { background: #ef4444; border-color: #ef4444; color: white; }

.chat-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
</style>

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
                    <!-- <div class="suggestions">
                        <button class="suggestion-btn" onclick="setQuestion('Batch saya baunya kayak bangkai, gimana dong?')">😷 Bau bangkai</button>
                        <button class="suggestion-btn" onclick="setQuestion('Di toples saya ada bulu putih, bahaya ga?')">🍄 Jamur putih</button>
                        <button class="suggestion-btn" onclick="setQuestion('Cara buat batch baru gimana?')">📦 Tambah batch</button>
                        <button class="suggestion-btn" onclick="setQuestion('Saya lupa password, bisa minta tolong?')">🔐 Lupa password</button>
                        <button class="suggestion-btn" onclick="setQuestion('Eco-enzyme buat apa aja?')">🌿 Manfaat</button>
                    </div> -->
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
                    <input type="text" name="message" id="messageInput"
                        placeholder="Tanyakan apapun...contoh: 'Batch saya nggak ada gelembung, kenapa ya?'"
                        autocomplete="off" required>
                    <button type="submit">Kirim →</button>
                </div>
            </form>
            <div class="suggestions" style="margin-top: 8px;">
                <button class="suggestion-btn" onclick="setQuestion('Ciri fermentasi sehat tuh kayak gimana?')">✅ Ciri sehat</button>
                <button class="suggestion-btn" onclick="setQuestion('Batch gue nggak ada gelembung')">🫧 No gelembung</button>
                <button class="suggestion-btn" onclick="setQuestion('Rasio 3 1 10 itu maksudnya?')">📐 Rasio</button>
                <button class="suggestion-btn" onclick="setQuestion('Makasih ya!')">😊 Makasih</button>
            </div>
        </div>
    </div>
</div>

<div class="section-header mb-2">
    <h3 class="section-title">📋 Tips &amp; Panduan</h3>
</div>

<div class="d-flex gap-2 mb-3">
    <a href="trouble.php?severity=all"   class="btn btn-sm <?= $filterSeverity === 'all'   ? 'btn-primary' : '' ?>">Semua</a>
    <a href="trouble.php?severity=red"   class="btn btn-sm <?= $filterSeverity === 'red'   ? 'btn-primary' : '' ?>">Serius</a>
    <a href="trouble.php?severity=amber" class="btn btn-sm <?= $filterSeverity === 'amber' ? 'btn-primary' : '' ?>">Sedang</a>
    <a href="trouble.php?severity=green" class="btn btn-sm <?= $filterSeverity === 'green' ? 'btn-primary' : '' ?>">Ringan</a>
</div>

<?php if (empty($tips)): ?>
    <p class="text-muted small">Belum ada tip tersimpan.</p>
<?php else: ?>
    <div class="tip-grid">
        <?php foreach ($tips as $tip): ?>
            <div class="tip-card <?= htmlspecialchars($tip['severity']) ?>">
                <div class="tip-title"><?= htmlspecialchars($tip['title']) ?></div>
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