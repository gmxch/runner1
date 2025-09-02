<?php
// === Konfigurasi dasar ===
$loginUrl     = "https://captchacoin.site/login/";
$dashboardUrl = "https://captchacoin.site/dashboard/";
$earnUrl      = "https://captchacoin.site/captcha-type-and-earn/";
$ajaxUrl      = "https://captchacoin.site/wp-admin/admin-ajax.php";
// Hapus cookie lama biar fresh tiap run
$cookieJar = __DIR__ . "/cookies.txt";
if(file_exists($cookieJar)) unlink($cookieJar);
$configFile   = __DIR__ . "/config.json";

// === Warna & efek terminal ===
function color($text, $colorCode) { return "\033[{$colorCode}m{$text}\033[0m"; }
function typeEffect($text, $speed = 20) { for ($i=0;$i<strlen($text);$i++){echo $text[$i];usleep($speed*1000);} echo "\n"; }
function flashBonus($text) { for ($i=0;$i<3;$i++){echo "\033[1;33m$text\033[0m\r";usleep(200000);echo str_repeat(" ", strlen($text))."\r";usleep(200000);} echo "\033[1;32m$text\033[0m\n"; }
function printHeader() { echo color(str_repeat("═", 60), "34")."\n"; echo color("💻 Edit aja acak acak kalau perlu 💻", "36")."\n"; echo color(str_repeat("═", 60), "34")."\n"; }
function printBox($title,$text,$color="36"){ $line=str_repeat("─",60); echo color($line,"34")."\n"; echo color("│ ",$color).color($title,$color)."\n"; echo color("│ ",$color).$text."\n"; echo color($line,"34")."\n"; }
function progressBar($seconds=3){ $symbols=["⚡","🔥","🛡","💥","⭐"]; echo "⏳ Menunggu: "; for($i=0;$i<$seconds*10;$i++){echo $symbols[array_rand($symbols)];usleep(100000);} echo "\n"; }

// === Load config ===
if(!file_exists($configFile)) die(color("⚠️ File config.json tidak ditemukan.\n","31"));
$config=json_decode(file_get_contents($configFile),true);
$username=$config['username']??'';
$password=$config['password']??'';
$remember=$config['rememberme']??'';
if(!$username||!$password) die(color("⚠️ Username/password belum diisi di config.json\n","31"));

// === STEP AWAL ===
system('clear');
printHeader();

// --- LOGIN ---
printBox("🌐 Login","Mengambil halaman login...","36");
$ch=curl_init(); curl_setopt_array($ch,[
    CURLOPT_URL=>$loginUrl,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_COOKIEJAR=>$cookieJar,
    CURLOPT_COOKIEFILE=>$cookieJar,
    CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
]); $loginPage=curl_exec($ch); curl_close($ch);

preg_match('/name="_wpnonce" value="([^"]+)"/',$loginPage,$m); $wpnonce=$m[1]??'';
preg_match('/name="form_id" value="([^"]+)"/',$loginPage,$m); $formId=$m[1]??'21';
preg_match('/name="redirect_to" value="([^"]+)"/',$loginPage,$m); $redirectTo=$m[1]??'';

printBox("🔑 Login","Mengirim data login...","36");
$postFields=[
    "username-21"=>$username,
    "user_password-21"=>$password,
    "form_id"=>$formId,
    "redirect_to"=>$redirectTo,
    "_wpnonce"=>$wpnonce,
    "_wp_http_referer"=>"/login/"
]; if($remember==="1") $postFields["rememberme"]=$remember;
$ch=curl_init(); curl_setopt_array($ch,[
    CURLOPT_URL=>$loginUrl,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_COOKIEJAR=>$cookieJar,
    CURLOPT_COOKIEFILE=>$cookieJar,
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>http_build_query($postFields),
    CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
]); $response=curl_exec($ch); curl_close($ch);
typeEffect(color("✅ Login berhasil!","32"));

// --- DASHBOARD ---
printBox("📊 Dashboard","Mengambil dashboard...","36");
$ch=curl_init(); curl_setopt_array($ch,[
    CURLOPT_URL=>$dashboardUrl,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_COOKIEJAR=>$cookieJar,
    CURLOPT_COOKIEFILE=>$cookieJar,
    CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
]); $dashboardPage=curl_exec($ch); curl_close($ch);
if(preg_match('/<div class="balance">\s*Balance:\s*<span>([^<]+)<\/span>/i',$dashboardPage,$match)) $balance=trim($match[1]);
else $balance="Tidak ditemukan";
typeEffect(color("💰 Balance: $balance","33"));

while(true){
printBox("🎯 Earn","Mengambil halaman earn...","36");
$ch=curl_init(); curl_setopt_array($ch,[
    CURLOPT_URL=>$earnUrl,
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_FOLLOWLOCATION=>true,
    CURLOPT_COOKIEJAR=>$cookieJar,
    CURLOPT_COOKIEFILE=>$cookieJar,
    CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
]); $earnPage=curl_exec($ch); curl_close($ch);

// Ambil captcha
$captcha='';
if(preg_match('/<div id="cte-captcha-box".*?>(.*?)<\/div>\s*<\/div>/is',$earnPage,$matchBox)){
    $boxHtml=$matchBox[1];
    if(preg_match('/<div[^>]*>\s*([A-Za-z0-9]{5,6})\s*<\/div>/is',$boxHtml,$matchCaptcha)){
        $captcha=trim($matchCaptcha[1]);
        typeEffect(color("🟢 Captcha ditemukan: $captcha","32"));
    }else typeEffect(color("⚠️ Captcha tidak ditemukan di box.","31"));
}else typeEffect(color("⚠️ Captcha box tidak ditemukan.","31"));

    printBox("📤 Kirim Captcha","Mengirim captcha...","36");
    $ajaxData=['cte_input'=>$captcha,'action'=>'cte_submit_captcha'];
    $ch=curl_init(); curl_setopt_array($ch,[
        CURLOPT_URL=>$ajaxUrl,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_COOKIEJAR=>$cookieJar,
        CURLOPT_COOKIEFILE=>$cookieJar,
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>http_build_query($ajaxData),
        CURLOPT_USERAGENT=>"Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    ]); $ajaxResponse=curl_exec($ch); curl_close($ch);

    if(preg_match("/Correct! (\d+) BONK added\./i",$ajaxResponse,$m)){
        $bonk=$m[1];
        flashBonus("💥 +$bonk BONK !");
    }else typeEffect(color("⚠️ BONK tidak terdeteksi.","31"));

    // Progress bar animasi
    progressBar(5);
}
