<?php
// === URL dasar ===
$loginUrl     = "https://captchacoin.site/login/";
$dashboardUrl = "https://captchacoin.site/dashboard/";
$earnUrl      = "https://captchacoin.site/captcha-type-and-earn/";
$ajaxUrl      = "https://captchacoin.site/wp-admin/admin-ajax.php";

// Ambil login + proxy dari environment variable
$login = getenv('LOGIN') ?: '';
$proxyPort = getenv('SSH_PROXY_PORT') ?: '';
$proxy = $proxyPort ? "127.0.0.1:$proxyPort" : null;

if(!$login) die("\033[31m⚠️ LOGIN (user:pass) belum diisi.\033[0m\n");

list($username, $password) = explode(':', $login, 2);
if(!$username || !$password) die("\033[31m⚠️ Format LOGIN salah, harus user:pass.\033[0m\n");

// Hapus cookie lama
$cookieJar = __DIR__ . "/cookies.txt";
if(file_exists($cookieJar)) unlink($cookieJar);

// === Helper warna terminal ===
function color($text,$color){return "\033[{$color}m{$text}\033[0m";}

// --- CURL helpers ---
function curl_get($url,$cookieJar,$proxy=null){
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_USERAGENT => "Mozilla/5.0",
    ];
    if($proxy){
        $opts[CURLOPT_PROXY] = $proxy;
        $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
    }
    curl_setopt_array($ch,$opts);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function curl_post($url,$postFields,$cookieJar,$proxy=null){
    $ch = curl_init();
    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_USERAGENT => "Mozilla/5.0",
    ];
    if($proxy){
        $opts[CURLOPT_PROXY] = $proxy;
        $opts[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5;
    }
    curl_setopt_array($ch,$opts);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// --- LOGIN ---
echo color("🌐 Mengambil halaman login...\n","36");
$loginPage = curl_get($loginUrl,$cookieJar,$proxy);

preg_match('/name="_wpnonce" value="([^"]+)"/',$loginPage,$m); $wpnonce = $m[1] ?? '';
preg_match('/name="form_id" value="([^"]+)"/',$loginPage,$m); $formId = $m[1] ?? '21';
preg_match('/name="redirect_to" value="([^"]+)"/',$loginPage,$m); $redirectTo = $m[1] ?? '';

echo color("🔑 Mengirim data login...\n","36");
$postFields = [
    "username-21" => $username,
    "user_password-21" => $password,
    "form_id" => $formId,
    "redirect_to" => $redirectTo,
    "_wpnonce" => $wpnonce,
    "_wp_http_referer" => "/login/",
    "rememberme" => "1"
];

$response = curl_post($loginUrl,$postFields,$cookieJar,$proxy);
echo color("✅ Login berhasil!\n","32");

// --- Ambil balance ---
$dashboardPage = curl_get($dashboardUrl,$cookieJar,$proxy);
if(preg_match('/<div class="balance">\s*Balance:\s*<span>([^<]+)<\/span>/i',$dashboardPage,$match)) 
    $balance = trim($match[1]);
else 
    $balance = "Tidak ditemukan";
echo color("💰 Balance: $balance\n","33");

// === LOOP UTAMA ===
while(true){
    echo color("🎯 Mengambil captcha...\n","36");
    $earnPage = curl_get($earnUrl,$cookieJar,$proxy);

    // Ambil captcha
    $captcha='';
    if(preg_match('/<div id="cte-captcha-box".*?>(.*?)<\/div>\s*<\/div>/is',$earnPage,$matchBox)){
        $boxHtml = $matchBox[1];
        if(preg_match('/<div[^>]*>\s*([A-Za-z0-9]{5,6})\s*<\/div>/is',$boxHtml,$matchCaptcha)){
            $captcha = trim($matchCaptcha[1]);
            echo color("🟢 Captcha: $captcha\n","32");
        } else echo color("⚠️ Captcha tidak ditemukan.\n","31");
    } else echo color("⚠️ Captcha box tidak ditemukan.\n","31");

    // Kirim captcha
    $ajaxData = ['cte_input'=>$captcha,'action'=>'cte_submit_captcha'];
    $ajaxResponse = curl_post($ajaxUrl,$ajaxData,$cookieJar,$proxy);

    if(preg_match("/Correct! (\d+) BONK added\./i",$ajaxResponse,$m)){
        $bonk = $m[1];
        echo color("💥 +$bonk BONK!\n","32");
    } else {
        echo color("⚠️ BONK tidak terdeteksi.\n","31");
    }

    sleep(5);
}