<?php
// === Konfigurasi dasar ===
$loginUrl     = "https://captchacoin.site/login/";
$dashboardUrl = "https://captchacoin.site/dashboard/";
$earnUrl      = "https://captchacoin.site/captcha-type-and-earn/";
$ajaxUrl      = "https://captchacoin.site/wp-admin/admin-ajax.php";

// Hapus cookie lama biar fresh tiap run
$cookieJar = __DIR__ . "/cookies.txt";
if(file_exists($cookieJar)) unlink($cookieJar);

// === Helper warna terminal ===
function color($text, $colorCode) { return "\033[{$colorCode}m{$text}\033[0m"; }

// === Ambil username & password dari environment variable ===
$username = getenv('USERNAME') ?: '';
$password = getenv('PASSWORD') ?: '';

if(!$username || !$password) die(color("⚠️ Username/password belum diisi.\n","31"));

// --- LOGIN ---
echo color("🌐 Mengambil halaman login...\n","36");
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $loginUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_USERAGENT => "Mozilla/5.0"
]);
$loginPage = curl_exec($ch);
curl_close($ch);

preg_match('/name="_wpnonce" value="([^"]+)"/', $loginPage, $m); $wpnonce = $m[1] ?? '';
preg_match('/name="form_id" value="([^"]+)"/', $loginPage, $m); $formId = $m[1] ?? '21';
preg_match('/name="redirect_to" value="([^"]+)"/', $loginPage, $m); $redirectTo = $m[1] ?? '';

echo color("🔑 Mengirim data login...\n","36");
$postFields = [
    "username-21" => $username,
    "user_password-21" => $password,
    "form_id" => $formId,
    "redirect_to" => $redirectTo,
    "_wpnonce" => $wpnonce,
    "_wp_http_referer" => "/login/",
    "rememberme" => "1" // hardcoded rememberme
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $loginUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postFields),
    CURLOPT_USERAGENT => "Mozilla/5.0"
]);
$response = curl_exec($ch);
curl_close($ch);
echo color("✅ Login berhasil!\n","32");

// --- Ambil balance ---
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $dashboardUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_USERAGENT => "Mozilla/5.0"
]);
$dashboardPage = curl_exec($ch);
curl_close($ch);

if(preg_match('/<div class="balance">\s*Balance:\s*<span>([^<]+)<\/span>/i', $dashboardPage, $match)) $balance = trim($match[1]);
else $balance = "Tidak ditemukan";
echo color("💰 Balance: $balance\n","33");

// === LOOP UTAMA ===
while(true){
    echo color("🎯 Mengambil captcha...\n","36");

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $earnUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);
    $earnPage = curl_exec($ch);
    curl_close($ch);

    // Ambil captcha
    $captcha = '';
    if(preg_match('/<div id="cte-captcha-box".*?>(.*?)<\/div>\s*<\/div>/is', $earnPage, $matchBox)){
        $boxHtml = $matchBox[1];
        if(preg_match('/<div[^>]*>\s*([A-Za-z0-9]{5,6})\s*<\/div>/is', $boxHtml, $matchCaptcha)){
            $captcha = trim($matchCaptcha[1]);
            echo color("🟢 Captcha: $captcha\n","32");
        } else echo color("⚠️ Captcha tidak ditemukan.\n","31");
    } else echo color("⚠️ Captcha box tidak ditemukan.\n","31");

    // Kirim captcha
    $ajaxData = ['cte_input' => $captcha, 'action' => 'cte_submit_captcha'];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $ajaxUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($ajaxData),
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);
    $ajaxResponse = curl_exec($ch);
    curl_close($ch);

    if(preg_match("/Correct! (\d+) BONK added\./i", $ajaxResponse, $m)){
        $bonk = $m[1];
        echo color("💥 +$bonk BONK!\n","32");
    } else {
        echo color("⚠️ BONK tidak terdeteksi.\n","31");
    }

    sleep(5); // delay antar loop
}
