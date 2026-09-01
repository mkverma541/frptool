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


function main($did,$ipad,$bid,$cpuid) {
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
    'board_id' => $bid,
    'cpu_id' => $cpuid,
    'disk_id' => $did,
    'ip' => $ipad,
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
function decryptByPrivateKey($privateKeyContent, $strEncryptedData)
{
    // Get the private key from the content
    $privateKey = openssl_pkey_get_private($privateKeyContent);
    
    if (!$privateKey) {
        throw new Exception('Invalid private key');
    }

    // Check if the encrypted data is empty
    if (empty($strEncryptedData)) {
       // throw new InvalidArgumentException('Encrypted data cannot be empty');
       ?>
       
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Blocked</title>
    <!-- Responsive Meta Tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS for Additional Styling -->
    <style>
        body, html {
            height: 100%;
            background-color: #f8f9fa;
        }
        .block-container {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .block-card {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
            text-align: center;
        }
        .block-card .icon {
            font-size: 60px;
            color: #dc3545; /* Bootstrap's danger color */
            margin-bottom: 20px;
        }
        .block-card h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: #343a40; /* Bootstrap's dark color */
        }
        .block-card p {
            font-size: 1.1rem;
            color: #6c757d; /* Bootstrap's secondary color */
            margin-bottom: 25px;
        }
        .block-card .btn-custom {
            background-color: #0d6efd; /* Bootstrap's primary color */
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            transition: background-color 0.3s ease;
        }
        .block-card .btn-custom:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="block-container">
        <div class="block-card">
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
            <h1>Sorry, You Have Been Blocked</h1>
            <p>You are unable to access <strong>rcsm-in.sgsmpro.com</strong>.</p>
            <a href="https://t.me/+QieKpaYy_sxmYzFl" class="btn btn-custom">Contact Support</a>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Optional: JavaScript for Additional Interactivity -->
</body>
</html>
<?php
    }

    // Base64 decode the encrypted data
    $encryptedData = base64_decode($strEncryptedData);
    
    if ($encryptedData === false) {
        throw new Exception('Base64 decoding failed');
    }

    // Get the key details and calculate the size of each chunk
    $keyDetails = openssl_pkey_get_details($privateKey);
    $keySize = $keyDetails['bits'] / 8;
    $bufferSize = $keySize - 11;  // This should match the buffer size used in encryption
    
    $decryptedContent = '';
    $len = strlen($encryptedData);
    $offset = 0;

    // Decrypt in chunks
    while ($offset < $len) {
        $chunk = substr($encryptedData, $offset, $keySize);  // Size of each encrypted chunk
        if (!openssl_private_decrypt($chunk, $decryptedChunk, $privateKey)) {
            throw new Exception('Decryption failed');
        }
        $decryptedContent .= $decryptedChunk;
        $offset += $keySize;
    }

    return $decryptedContent;
}
$privateKeyContent = <<<EOD
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQC8pAMQWgq0WQQTUhMEATR9pVMSPNVYxIfVUPc/OlEQHGvAlJDA0BfNB8Bxhdr/Q2sh/ctZY+FDrc/qzYf9oUcchxmwnH0BKfhFk4pEUZKG6NRsXqJI6aMzq13YWnPJOg2j6DE3kgn08ALmgJb22W6WLrr692QZO0rwzR9mkeGxMk2BY62o44Iaq0eyTnhq2vrOhVM0pvj3YYcsG6aKtlsw8yAyEhUk/T6mHfmUMMHmfpDVXKGA2O/VCsh0+EoWbObOAPrFJnSk9OH0fBhl1jSxGkoPiTNanP1LWUV1NTs6apQu66tlQ91QaeIbWGOET692ZlAzZDKcLhUktYIVchzDAgMBAAECggEAOM7LfWkgQB9ucsMMpvAV9qCK27smJI1KupqfWpMdRlTIvj3/OZFxGXV0JrvAr46F/L6JNmo1tEPrkTJD+cVPvO+SdPUrT+Zrtcgwo1JNZgNHtTrqdDqnwy+gGz9iDv9EjE+oQqvgA7sjpHak/8GK4/8+n8VQh6ircMjU4/aamNmgukbOISbJu7KypasmwBY8flVVUPmP6nCwYLc7JsBMRczj0Ra0x3ItxOFDSADjNvVUkNg46X/EXAAmidonFipg+LicdIiNmT4MMLUg1tEEDI4RdqD24Gz3Rn7f/B5j5ZNy25SHe/OaX08Nta4mwf1HkLC6SJcsCJFYYjiOMXnKAQKBgQDkKdeGlfWoV/QRlDkIt20+RB/asGXt3mwQXoS5phcADbysOQgirDrU7egGYE1UkeeDfNjvPr6DW5bZdTAIVUGqj5PiBcO81sfS3Ry9DxvS8pjV74+wV8FW8UpmX5Gmvx/OjMIQXk8EdWFGlx2lG51qA7XUEMXlg7XO9kComNtKIwKBgQDTp8R+jWrbE85fnuP8DzNBM+61+GMRerO9w2zZM/YsDP2rhA6/i3OVhcqL1OKOH6kP9rOC6rhjmul4x4ezK7ivSMhYz4I8gOyMJmbpc8JwTa40Pt3WbIyvVblH9Pj7eTmk9WTRgAQE64O3k6QdxYSXqPUJw4vfucMUbfOMDE984QKBgHd1dNORZkpaqn4dtfLbXsYQEwGEBAoTv06evi0ZSceMabFeNuU4eaEMYsQb3cEelzFfx5ETr9nEtWlrktd6E+SCQfJABGi1p2++txJBe9bpj53LTNcOSzsDIGoTNYYxYSzaw6ygRAzYjDLSYgIVQEjGYogCtCpj2GfgxJ/BUGczAoGAKgeIl3DRpUtbkdVlhGooTWxYnL4EPjZVdvtVpBQTcE/sF6ETpKm2fByjSf0uN/bFhawBnZ+qmezrK9bDdara88PKNQiP3h/j2TjO+tDH7bEfRLSvLKNFlJO7RTS6NIWwErfAG3IGWkvCTjP9RQQx/kPI1PWF0xl6SZZD5K2VI0ECgYADv1SyqFemhaScVxMu01biZDZiy235u3fHknvo9hx7IlqaQbYO0VWG0eJhRFNiUyigApuvMVqKmn+FEvwsgPPt1/utnrAtd1/VwkVIaOJXZeQeow1jv5yx3j+LjwfGttOZpTxC5erl8iXjYT5D/Az9+z5XcNcs5l67rd+njZ/zHw==
-----END PRIVATE KEY-----
EOD;

$encryptedData = $_POST['s_msg'];
try {
    $decryptedData = decryptByPrivateKey($privateKeyContent, $encryptedData);
    $dataArray = json_decode($decryptedData, true);  // The second parameter `true` returns an associative array

if ($dataArray === null) {
    echo "Error decoding JSON.";
} else {
    // Check if 'user_id' and 'password' exist and display them
    $user_id = isset($dataArray['user_id']) ? $dataArray['user_id'] : 'Not available';
    $password = isset($dataArray['password']) ? $dataArray['password'] : 'Not available';
     $ipad = isset($dataArray['ip']) ? $dataArray['ip'] : 'Not available';
     $did = isset($dataArray['disk_id']) ? $dataArray['disk_id'] : 'Not available';
     $cpuid = isset($dataArray['cpu_id']) ? $dataArray['cpu_id'] : 'Not available';
     $bid = isset($dataArray['board_id']) ? $dataArray['board_id'] : 'Not available';
    


$url = "https://otp.sgsmpro.com/otp_api.php";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: application/x-www-form-urlencoded",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = "action=valid_otp&otp=$user_id";

curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

//for debug only!
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($curl);
curl_close($curl);
$respArray = json_decode($resp, true);
$status = isset($respArray['status']) ? $respArray['status'] : 'Not available';
$message= isset($respArray['message']) ? $respArray['message'] : 'Not available';
    if ($status === "success")
    {
        main($did,$ipad,$bid,$cpuid);
    }
    else
    {
        $jsonData = '{
   "Data" : {
      "response" : {
         "countryname" : "",
         "message" : "0006",
         "signData" : "hKo4aaK2Woh5FJu5aeZjUjGPhL2hfhMhW2iWfBteLGowAxrWA3wRXaiSEfna2emVTk5pyOP/2GXcRnhR3wn9NqOWAksqPiXRpiUk+7zwvv6cD6ZeBnwQwjDUw2MQqXWpSjnHRVImUAHEZC1U6qchnO3tvh33yWKPoamOvmCx6uAWLjvJpi8yX/pIvbzDc8uVj0lsiBawNEP0uTrR1rSk4mRZEpPDEqxF+yMXslIjCFpNFrsH1w0szrfXKM3nAKWxnFILQF1SJoA1e1V+ucX515hBPVbBIG/F96L2yHpaPxUGAuj5UxuNlDh1/RwtEWPz6aMuggKnIYN88hiVCnwLpg==",
         "status" : "1",
         "token" : "",
         "usertype" : ""
      }
   },
   "ErrorCode" : 0,
   "Message" : null
}';

// Echo the JSON data directly
$data = json_decode($jsonData, true);

// Check if the JSON was decoded properly
if ($data === null) {
    echo "Error decoding JSON.";
} else {
    // Encode it back to JSON with pretty print
    $prettyJson = json_encode($data, JSON_PRETTY_PRINT);
    // Echo the pretty printed JSON
    header('Content-Type: application/json');
    echo $prettyJson;
}
    }

}
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

//main();



?>
