<?php
include_once 'konak.php';
$config = include('../../../config.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Yangon');

function escapeMarkdownV2($text) {
    $escapeChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($escapeChars as $char) {
        $text = str_replace($char, '\\' . $char, $text);
    }
    return $text;
}

function decryptAES256GCM($ciphertext_b64, $key_b64, $nonce_b64) {
    $ciphertext = base64_decode($ciphertext_b64);
    $key = base64_decode($key_b64);
    $nonce = base64_decode($nonce_b64);

    if ($ciphertext === false || $key === false || $nonce === false) {
        return false;
    }

    $tag = substr($ciphertext, -16);
    $ciphertext = substr($ciphertext, 0, -16);

    return openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
}

function encryptAES256GCM($plaintext, $key_b64, $nonce_b64) {
    $key = base64_decode($key_b64);
    $nonce = base64_decode($nonce_b64);

    if ($key === false || $nonce === false) {
        return false;
    }

    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($ciphertext === false) return false;

    return base64_encode($ciphertext . $tag);
}

function getRegion($koneksi) {
    $stmt = $koneksi->prepare("SELECT region FROM actived_server WHERE id = 4");
    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($sid);
        if ($stmt->fetch()) {
            $stmt->close();
            return $sid;
        }
        $stmt->close();
        return "❌ No server found with ID 3";
    }
    return "❌ Database error: Unable to prepare statement.";
}

$getRegion = getRegion($koneksi);
switch ($getRegion) {
    case "India": $targetUrl = 'http://93.127.140.18/sign.php'; break;
    case "Other": $targetUrl = 'http://157.20.105.91/sign.php'; break;
    case "Europe": $targetUrl = 'http://51.210.132.145/sign.php'; break;
    default: die("Unknown region: $getRegion");
}

$baseUrl = "https://pub.sgsmpro.com/dist/pages/";
$getTicketUrl = $baseUrl . "get_ticket.php?ticket_type=Realme";
$useTicketUrl = $baseUrl . "use_ticket.php";

// --------------------------
// 1. Call get_ticket.php
// --------------------------

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $getTicketUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    die("Failed to connect to get_ticket.php");
}

// Parse JSON response
$data = json_decode($response, true);

if ($data['status'] !== 'success') {
     echo json_encode(['code' => '050204', 'data' => null, 'msg' => 'No available tickets found!']);
    exit;
}

// Grab ticket code
$ticketCode = $data['ticket']['ticket_code'];

// Parse headers and token
$headers = [];
$deviceId = "";
foreach (getallheaders() as $name => $value) {
    $lower = strtolower($name);
    if ($lower === 'host') {
        $headers[] = "Host: " . parse_url($targetUrl, PHP_URL_HOST);
    } elseif ($lower === 'deviceid') {
        $headers[] = "$name: $value";
        $deviceId = $value;
    } elseif ($lower === 'token') {
        $generatedToken = $value;
        $sql = "SELECT original_token FROM tokens WHERE generated_token = ? AND status = 'unused' LIMIT 1";
        $stmt = $koneksi->prepare($sql);
        if (!$stmt) die("Error preparing SQL: " . $koneksi->error);

        $stmt->bind_param("s", $generatedToken);
        $stmt->execute();
        $stmt->bind_result($originalToken);

        if ($stmt->fetch()) {
            $stmt->close();
            $updateSql = "UPDATE tokens SET status = 'unused' WHERE generated_token = ?";
            $updateStmt = $koneksi->prepare($updateSql);
            if (!$updateStmt) die("Error preparing update SQL: " . $koneksi->error);
            $updateStmt->bind_param("s", $generatedToken);
            if ($updateStmt->execute()) {
                $headers[] = "token: $originalToken";
            } else {
                $updateStmt->close();
                echo json_encode(['code' => '050204', 'data' => null, 'msg' => 'Updating token error!']);
                return;
            }
            $updateStmt->close();
        } else {
            $stmt->close();
            echo json_encode(['code' => '050204', 'data' => null, 'msg' => 'Your OTP code not found or already used!']);
            return;
        }
    } else {
        $headers[] = "$name: $value";
    }
}

// Decrypt input data
$rawInput = file_get_contents('php://input');
$main = json_decode($rawInput, true);
$data_inner = json_decode($main['data'], true);
$iv_b64 = $data_inner['iv'];
$ciphertext_b64 = $data_inner['cipher'];

$plaintext = decryptAES256GCM($ciphertext_b64, $deviceId, $iv_b64);
if ($plaintext === false) die("Decryption failed.\n");

$data_decoded = json_decode($plaintext, true);
$data_decoded["toolHash"] = "9195708ffc61c1c16d462980b06d0ee99b567468fce29324703eff22f23c6b7c";
$data_decoded['workerOrder'] = $ticketCode;
// $data_decoded["diskId"] = "0100_0000_0000_0000_8CE3_8E04_0332_2724.           X1JLT7P3T";
// $data_decoded["extIp"] = "169.254.145.10";
// $data_decoded["mac"] = "32-03-C8-3D-8D-31";
$new_plaintext = json_encode($data_decoded, JSON_UNESCAPED_SLASHES);
$ciphertext_new_b64 = encryptAES256GCM($new_plaintext, $deviceId, $iv_b64);

// Prepare the new "data" JSON string (with cipher and iv)
// $newInput = preg_replace(
//     '/("cipher"s*:s*")[^"]*(")/',
//     '$1' . $ciphertext_new_b64 . '$2',
//     $rawInput
// );
$data_inner['cipher'] = $ciphertext_new_b64;

// re-encode inner JSON string
$main['data'] = json_encode($data_inner, JSON_UNESCAPED_SLASHES);

// re-encode outer JSON string
$newInput = json_encode($main, JSON_UNESCAPED_SLASHES);

$newContentLength = strlen($newInput);

$found = false;
foreach ($headers as &$h) {
    if (stripos($h, 'Content-Length:') === 0) {
        $h = 'Content-Length: ' . $newContentLength;
        $found = true;
        break;
    }
}
unset($h);

if (!$found) {
    $headers[] = 'Content-Length: ' . $newContentLength;
}


//echo $newInput;




// Decode again for Telegram reporting
$data_for_telegram = json_decode($main['data'], true);
$authdata = decryptAES256GCM($data_for_telegram['cipher'], $deviceId, $data_for_telegram['iv']);
$data = json_decode($authdata, true);

$platform = $data['mainPlatform'] ?? 'Unknown';
$chipset = $data['subPlatform'] ?? 'Unknown';
$serialNumber = $data['chipSn'] ?? 'Unknown';
$account = $data['account'] ?? 'Unknown';
$data['toolVersion'] = '2.9.13';
$newJson = json_encode($data, JSON_UNESCAPED_SLASHES);

// Send to target server
$ch = curl_init($targetUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $newInput);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Log request
$headersStr = implode("\n", $headers);
file_put_contents("request.log", "=== HEADERS ===\n$headersStr\n=== BODY ===\n$newInput\n\n", FILE_APPEND);

// Telegram Notify
$botToken = $config['bot_token'];
$chatId = $config['chat_id'];

$telegramMessage = "*🔔 REALME SG SIGN INFO 🔔*\n\n" .
    "*📱 Platform:* `" . escapeMarkdownV2($platform) . "`\n" .
    "*🔧 Chipset:* `" . escapeMarkdownV2($chipset) . "`\n" .
    "*🔢 Serial Number:* `" . escapeMarkdownV2($serialNumber) . "`\n" .
    "*👤 Account:* `" . escapeMarkdownV2($account) . "`\n" .
    "*👤 Ticket Code:* `" . escapeMarkdownV2($ticketCode) . "`\n" .
    "*📅 Time:* `" . date('Y-m-d H:i:s') . "`\n\n" .
    // "*📨 API Input:*\n```\n$newJson\n```" .
    // "*📨 API Decrypted:*\n```\n$authdata\n```" .
    "*📨 API Response:*\n```\n" . escapeMarkdownV2(substr($response, 0, 1000)) . "\n```";

$ch2 = curl_init("https://api.telegram.org/bot$botToken/sendMessage");
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query([
    'chat_id' => $chatId,
    'text' => $telegramMessage,
    'parse_mode' => 'MarkdownV2'
]));
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch2);
curl_close($ch2);

// Handle final response
header('Content-Type: application/json');
$responseData = json_decode($response, true);

if (isset($responseData['code'])) {
    if ($responseData['code'] === '000000') {
        $url = $config['verify_url'] . "/otp_eu.php";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "disk_id=$account");
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($curl);
        curl_close($curl);

        echo $response;

        $user = "REALME";
        $time = date('Y-m-d h:i:s A');
        $stmt = $koneksi->prepare("INSERT INTO devdata (chipSn, mainPlatform, subPlatform, account, user, time) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $serialNumber, $platform, $chipset, $account, $user, $time);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("DB Prepare failed: " . $koneksi->error);
        }
        
        $user = "admin"; // replace with your user or dynamic value

// Build full URL for using ticket
$useUrl = $useTicketUrl . "?ticket_code=" . urlencode($ticketCode) . "&user=" . urlencode($account);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $useUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$response2 = curl_exec($ch2);
curl_close($ch2);

if ($response2 === false) {
    die("Failed to connect to use_ticket.php");
}

// Parse JSON response
$data2 = json_decode($response2, true);

if ($data2['status'] === 'success') {
    // echo "Ticket successfully used: " . $data2['ticket_code'] . "\n";
    // echo "Used by: " . $data2['used_user'] . "\n";
} else {
    // echo "Failed to use ticket: " . $data2['message'] . "\n";
}


    } else {
        echo $response;
    }
}

curl_close($ch);
?>
