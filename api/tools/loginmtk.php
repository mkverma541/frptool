<?php

function encryptByPublicKey($publicKeyContent, $strEncryptString)
{
    
    $publicKey = openssl_pkey_get_public($publicKeyContent);
    
    if (!$publicKey) {
        throw new Exception('Invalid public key');
    }

    
    if (empty($strEncryptString)) {
        throw new InvalidArgumentException('Data to encrypt cannot be empty');
    }

    
    $originalData = $strEncryptString;

    
    $keyDetails = openssl_pkey_get_details($publicKey);
    $keySize = $keyDetails['bits'] / 8;
    $bufferSize = $keySize - 11;  

    $encryContent = '';
    $len = strlen($originalData);
    $offset = 0;

   
    while ($offset < $len) {
        $chunk = substr($originalData, $offset, $bufferSize);
        if (!openssl_public_encrypt($chunk, $encryptedData, $publicKey)) {
            throw new Exception('Encryption failed');
        }
        $encryContent .= $encryptedData;
        $offset += $bufferSize;
    }

    
    return base64_encode($encryContent);
}

class RcsmRegion {
    const China = 'China';
    const India = 'India';
    const Europe = 'Europe';
    const Singapore = 'Singapore';
}


function getURL($region) {
    if ($region == RcsmRegion::China) {
        return "https://rcsm-cn.realmeservice.com/api/tools/login";
    } elseif ($region == RcsmRegion::India) {
        return "https://rcsm-in.realmeservice.com/api/tools/login";
    } elseif ($region == RcsmRegion::Europe) {
        return "https://rcsm-eu.realmeservice.com/api/tools/login";
    } elseif ($region == RcsmRegion::Singapore) {
        return "https://rcsm-cn.realmeservice.com/api/tools/login";
    } else {
        return ''; 
    }
}


function getSecret($region) {
    if ($region == RcsmRegion::China) {
        return "1557a67f-24c9-4bd4-845d-716e86723064";
    } elseif ($region == RcsmRegion::India) {
        return "1a8fc48b-a114-4dbc-9592-7171273af020";
    } elseif ($region == RcsmRegion::Europe) {
        return "36b655ac-f2b9-4d7b-9068-77573f09e932";
    } elseif ($region == RcsmRegion::Singapore) {
        return "dad18bcb-1aee-45c6-bf0d-994fd28d7534";
    } else {
        return ''; 
    }
}


function login($url, $userId, $password, $macAddr, $region) {

    $srt = getSecret($region) ;
$dataToEncrypt = 'Hello, this is a test message!';

$encryptedString = encryptByPublicKey($publicKeyContent, $dataToEncrypt);

    // echo "URL: $url\n";
    // echo "User ID: $userId\n";
    // echo "Password: $password\n";
    // echo "MAC Address: $macAddr\n";
    // echo "Region: $region\n";
    // echo "Secret: $srt \n";
}


function main() {
    $publicKeyContent = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvKQDEFoKtFkEE1ITBAE0
faVTEjzVWMSH1VD3PzpREBxrwJSQwNAXzQfAcYXa/0NrIf3LWWPhQ63P6s2H/aFH
HIcZsJx9ASn4RZOKRFGShujUbF6iSOmjM6td2FpzyToNo+gxN5IJ9PAC5oCW9tlu
li66+vdkGTtK8M0fZpHhsTJNgWOtqOOCGqtHsk54atr6zoVTNKb492GHLBumirZb
MPMgMhIVJP0+ph35lDDB5n6Q1VyhgNjv1QrIdPhKFmzmzgD6xSZ0pPTh9HwYZdY0
sRpKD4kzWpz9S1lFdTU7OmqULuurZUPdUGniG1hjhE+vdmZQM2QynC4VJLWCFXIc
wwIDAQAB
-----END PUBLIC KEY-----
"; 

    $region = RcsmRegion::India; 
    $url = getURL($region);
    
    
    $data = [
    'board_id' => '',
    'cpu_id' => '',
    'disk_id' => '50026B738073E5E4',
    'ip' => '192.168.1.103',
    'login_type' => '1',
    'mac' => '30-F9-ED-D3-79-01',
    'user_id' => 'IND00150',
    'password' => 'Atul@123',
    'version' => '',
    'verification_code' => '0'
    
];
$jsonString = json_encode($data);

$temp = encryptByPublicKey($publicKeyContent, $jsonString);

$request = new Request();

$request->s_msg = $temp;
$request->s_msg_md_5 = getMd5($temp,false,false);
$methodName = "/api/tools/login";
$secret = getSecret($region);  
$request->sign = signData($methodName, $secret, $request);



$formData = [
    "app_id" => "realme_tool",
    "timestamp" => time(), 
    "sign" => $request->sign,
    "s_msg" => $request->s_msg,
    "s_msg_md_5" => $request->s_msg_md_5
];

// $url = "https://rcsm-in.realmeservice.com/api/tools/login"; 


$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true); 
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData)); 

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: MsmDownloadTool-V2.0.71-rcsm",
    "Host: rcsm-in.realmeservice.com",
    "Cache-Control: no-cache"
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    throw new Exception("cURL error: " . curl_error($ch));
}

curl_close($ch);

if (!$response) {
    throw new Exception("HTTP request failed with no response.");
}
header('Content-Type: application/json');
echo $response;

}
class Request {
    public $app_id;
    public $timestamp;
    public $sign;
    public $s_msg_md_5;
    public $s_msg;

    public function __construct() {
        $this->app_id = "realme_tool";
        $this->timestamp = $this->getGmtTimestamp(); 
    }

    public function getGmtTimestamp() {
        return time();
    }
}
function signData($methodName, $secret, $request) {
    $dic = [];

    $properties = get_object_vars($request);

    foreach ($properties as $key => $value) {
        if ($key !== "sign") {
            
            $dic[$key] = $value ?? "";
            
        }
    }

    return getSign($methodName, $secret, $dic);
}
function getSign($methodName, $secret, $dic)
{
    
    $sign = $methodName . "\n";

    ksort($dic);

    foreach ($dic as $key => $value) {
        $sign .= $key . "=" . $value . "\n";
    }

    $sign .= $secret;
    return getMd5($sign);
}
function getMd5($content, $isUpper = false, $is16 = false) {
    $md5Hash = md5($content);  
    if ($isUpper) {
        $md5Hash = strtoupper($md5Hash);
    } else {
        $md5Hash = strtolower($md5Hash);
    }

    if ($is16) {
        return substr($md5Hash, 8, 16);  
    }

    return $md5Hash;
}
if (isset($_GET['pwd'])) {
    $passT = $_GET['pwd'];
if ($passT == "kknn2244")
{
   main(); 
}
else
{
 echo "Fuck you.";
}

}
else
{
     echo "Fuck you.";
}



?>
