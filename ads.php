<?php
error_reporting(0);

function Run($url, $head = 0, $post = 0, $method = "POST") {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    if ($method == "PUT") {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($post) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    } elseif ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }

    if ($head && is_array($head)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $head);
    }

    curl_setopt($ch, CURLOPT_HEADER, true);
    $r = curl_exec($ch);
    if (!$r) return "Curl error: " . curl_error($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($r, $header_size);
    curl_close($ch);
    return $body;
}

function headers($req){
    return [
        'Host: faucetwebservice.mobilecloudmining.ru',
        'User-Agent:UnityPlayer/2022.3.17f1 (UnityWebRequest/1.0, libcurl/8.4.0-DEV)',
        'Accept:*/*',
        'Content-Type:application/x-www-form-urlencoded',
        'X-Unity-Version:2022.3.17f1',
        "Content-Length: ".strlen($req)
    ];
}

// Ambil ID dari environment variable workflow input
$id = getenv('GPGSID');

function claim($id){
    $host = "https://faucetwebservice.mobilecloudmining.ru/";

    while(true){
        $req = "gpgsId=$id";
        $r = Run("$host/R%7DR4i+jYzc89z-Q/api/v0.1.3/login.php", headers($req), $req);
        $parts = explode('|', $r);
        $id = $parts[0];
        $user = $parts[1];
        $curn = $parts[2];
        $bal = $parts[3];
        $code = $parts[14];

        echo "[INFO] USER: $user\n";
        echo "[INFO] ID: $id\n";
        echo "[INFO] COUNTRY: $curn\n";
        echo "[INFO] BALANCE: $bal COINS\n";
        echo str_repeat("━", 40) . "\n";

        $req = "id=$id";    
        Run("$host/R%7DR4i+jYzc89z-Q/api/v0.1.3/getConfig.php", headers($req), $req);    

        $ad_watched = 0;
        $adprice = [
            "0.000942","0.000103","6.4E-05","0.000942","0.000921",
            "0.000935","0.000487","7.8E-05","7.1E-05","6.6E-05",
            "5.7E-05","4.8E-05","0.001206","0.001305","0.00063",
            "0.001296","0.000642"
        ];

        foreach ($adprice as $price) {
            $last_bal = $bal;    
            $ad_watched++;
            $req = "id=$id&adPriceType=Bid&adPrice=$price&adNumber=$ad_watched&key=$code";    
            $r = Run("$host/R%7DR4i+jYzc89z-Q/api/v0.1.3/plusCoinsForVideo.php", headers($req), $req);
            $bal = explode('|',$r)[0];

            if($bal == "" || $bal == "error" || $bal == "error code: 1015"){
                $ad_watched--;
                continue;
            }

            $reward = $bal - $last_bal;
            echo "[SUCCESS] REWARD: $reward COINS\n";
            echo "[SUCCESS] BALANCE: $bal COINS\n";        
            echo "[SUCCESS] AD_PRICE: $price\n";
            echo "[SUCCESS] AD_WATCHED: $ad_watched\n";
            echo str_repeat("━", 40) . "\n";

            sleep(4); // delay 4 detik per ad
        }

        echo "[INFO] ALL ADS CYCLE COMPLETE. REFRESHING...\n";
        echo str_repeat("━", 40) . "\n";
    }
}

claim($id);
