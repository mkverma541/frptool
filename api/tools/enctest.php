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
$publicKeyContent = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0TifZv3DEc0Ix+e8H/QJ
zMmN0pJEk8JsYSEjsnm34dk81rPmD+qmLz5cHlvr1fQJ8f5qL3nHUVcgLjBA0AfB
5tNINg9voxaK42Wrbnw84g7NzFMtgbDIY6oygkWNpG94XFiLMt1mfE1lLNTR6nWt
xI1+e2ba0nT2OXzQozao5/birBhbob8b6ZANga49XbTf5nAW5yeoua6ifL2Me04+
Ub6Y/MJ2+D60Mkyb+Of6PKQszdfo6ubLw3VXV/3W7UbEA+V3cLO5L6wcbPFSdxva
1QsUtgTP+KZzq3/PwROihyODR3+u9+6KSsdvdsALPmm3/SSAwz74od0gd4VmJKyL
XwIDAQAB
-----END PUBLIC KEY-----
"; 

    //$region = RcsmRegion::India; 
    //$url = getURL($region);
   $vcode= $_GET['vcode'];
    
    $data = [
    'board_id' => '',
    'cpu_id' => '',
    'disk_id' => '',
    'ip' => '',
    'login_type' => '1',
    'mac' => '30-F9-ED-D3-79-01',
    'user_id' => 'XPOWER',
    'password' => '12345',
    'version' => '',
    'verification_code' => $vcode
    
];
$jsonString = json_encode($data);

$temp = encryptByPublicKey($publicKeyContent, $jsonString);

echo $temp;

?>