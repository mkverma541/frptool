<?php
require_once 'konak.php';
$config = include('../../../config.php');
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$input_data = file_get_contents('php://input');
$deviceid = $headers['deviceid'] ?? 'Not Found';
$key_b64 = $deviceid; // Make sure this is your actual base64-encoded AES key

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

$jsonData = $input_data;
$data = json_decode($jsonData, true);
$expectedAccount = "fixprootp";
$expectedPassword = "123";
$accountData = json_decode($data['account'], true);
$passwordData = json_decode($data['password'], true);

$decryptedAccount = decryptAES256GCM($accountData['cipher'], $key_b64, $accountData['iv']);
$decryptedPassword = decryptAES256GCM($passwordData['cipher'], $key_b64, $passwordData['iv']);

$message = "Your account is matched"; // ✅ Fixed semicolon

$url = $config['verify_url'] . "/otp_eu.php";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: application/x-www-form-urlencoded",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = "otp=$decryptedAccount&disk_id=$decryptedAccount&otp_type=RealmeNew";

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
    }
    else
    {
             header('Content-Type: application/json');
    echo json_encode([
        'code' => '050208',
        'data' => null,
        'msg' => $message
    ]);
    return;
    }

// if ($decryptedAccount === $expectedAccount && $decryptedPassword === $expectedPassword) {
//     // header('Content-Type: application/json');
//     // echo json_encode([
//     //     'code' => '050204',
//     //     'data' => null,
//     //     'msg' => $message
//     // ]);
//     // return;
// }
// else
// {
//      header('Content-Type: application/json');
//     echo json_encode([
//         'code' => '050204',
//         'data' => null,
//         'msg' => "It's not FIX PRO Admin account."
//     ]);
//     return;
// }


function getSID($koneksi) {
    $stmt = $koneksi->prepare("SELECT server_id FROM actived_server WHERE id = 4");
    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($sid);
        
        if ($stmt->fetch()) {
            return $sid;
        } else {
            return "❌ No server found with ID 1";
        }
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}
function getRegion($koneksi) {
    $stmt = $koneksi->prepare("SELECT region FROM actived_server WHERE id = 4");
    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($sid);
        
        if ($stmt->fetch()) {
            return $sid;
        } else {
            return "❌ No server found with ID 1";
        }
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}
function getToken($koneksi) {
    $stmt = $koneksi->prepare("SELECT token FROM actived_server WHERE id = 4");
    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($sid);
        
        if ($stmt->fetch()) {
            return $sid;
        } else {
            return "❌ No server found with ID 1";
        }
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}
function getActBy($koneksi) {
    $stmt = $koneksi->prepare("SELECT activeBy FROM actived_server WHERE id = 4");
    if ($stmt) {
        $stmt->execute();
        $stmt->bind_result($sid);
        
        if ($stmt->fetch()) {
            return $sid;
        } else {
            return "❌ No server found with ID 1";
        }
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}

$serverId = getSID($koneksi); // ✅ Correct call, no $
$getrgn = getRegion($koneksi); // ✅ Correct call, no $
$getToken = getToken($koneksi);
$getAct = getActBy($koneksi);

// Function to fetch server username and password
function getServerCredentials($serverId, $koneksi) {
    $stmt = $koneksi->prepare("SELECT username, password, mac, region FROM servers WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $serverId);
        $stmt->execute();
        $stmt->bind_result($username, $password,$mac,$region);
        
        if ($stmt->fetch()) {
            return [
                'username' => $username,
                'password' => $password,
                'mac' => $mac,
                'region' => $region
            ];
        } else {
            return "❌ No server found with ID: $serverId";
        }
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}
$serverResponse = "";
if ($getAct === "ByID")
{
    $serverCredentials = getServerCredentials($serverId, $koneksi);
  $userId = $serverCredentials['username'];
  $pwd = $serverCredentials['password'];
  $mac = $serverCredentials['mac'];
  $rgn = $serverCredentials['region'];

$postData = http_build_query([
    'username' => $userId,
    'password' => $pwd,
    'region' => $rgn
]);

// URL where you want to post the data
$targetUrl = "https://pub.sgsmpro.com/dist/pages/realmenewapi.php"; // change to your URL

// Initialize cURL
$ch = curl_init($targetUrl);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

// Execute the request
$response = curl_exec($ch);

$serverResponse = $response;
}
else
{
    $token =  $getToken;
    $areaCode = "sg";
$rgncode = "sg";
    if ($getrgn === "Other")
    {
        $areaCode = "sg";
$rgncode = "sg";
    }
    else if ($getrgn === "India")
    {
        $areaCode = "in";
$rgncode = "in";
    }
    else if ($getrgn === "Europe")
    {
        $areaCode = "eu";
$rgncode = "fr";
    }


$response = [
    "code" => "000000",
    "msg" => "Success",
    "data" => [
        "token" => $token,
        "areaCode" => $areaCode,
        "regionCode" => $rgncode,
        "usrTypeCode" => "after_sale",
        "toolId" => 20,
        "toolCode" => "TOOLSHUB",
        "toolName" => "realme售后支持",
        "brand" => "realme",
        "businessList" => [
            [
                "businessId" => 15,
                "businessCode" => "ANTIFAKE_SRV",
                "businessName" => "防串货业务",
                "featureDTOs" => [
                    ["featureId" => 50, "featureCode" => "authorization_unlock", "featureName" => "窜货解锁"],
                    ["featureId" => 49, "featureCode" => "authorization_lock", "featureName" => "窜货加锁"]
                ]
            ],
            [
                "businessId" => 29,
                "businessCode" => "FLASH_SRV",
                "businessName" => "刷机插件",
                "featureDTOs" => [
                    ["featureId" => 74, "featureCode" => "flashtool_basic_flashing", "featureName" => "基础刷机功能"],
                    ["featureId" => 72, "featureCode" => "ftflash_pkg_manage", "featureName" => "固件包管理"]
                ]
            ],
            [
                "businessId" => 14,
                "businessCode" => "UNLOCK_SRV",
                "businessName" => "工模解密",
                "featureDTOs" => [
                    ["featureId" => 47, "featureCode" => "ONLINE_UNLOCK", "featureName" => "在线解密"],
                    ["featureId" => 46, "featureCode" => "OFFLINE_UNLOCK", "featureName" => "离线解密"]
                ]
            ],
            [
                "businessId" => 25,
                "businessCode" => "CLEARDATA_SRV",
                "businessName" => "数据双清",
                "featureDTOs" => [
                    ["featureId" => 68, "featureCode" => "CLEAR_DEVICE_DATA", "featureName" => "清除设备数据"]
                ]
            ],
            [
                "businessId" => 28,
                "businessCode" => "READBACK_SRV",
                "businessName" => "小工具回读业务",
                "featureDTOs" => [
                    ["featureId" => 70, "featureCode" => "readback_plugin", "featureName" => "回读功能"]
                ]
            ],
            [
                "businessId" => 23,
                "businessCode" => "DIAG_SRV",
                "businessName" => "诊断业务",
                "featureDTOs" => [
                    ["featureId" => 66, "featureCode" => "offline_scan", "featureName" => "脱机扫码"],
                    ["featureId" => 65, "featureCode" => "device_diagnosis", "featureName" => "修前&&修后"]
                ]
            ],
            [
                "businessId" => 26,
                "businessCode" => "POWEROFF_DIAG_SRV",
                "businessName" => "不开机诊断",
                "featureDTOs" => [
                    ["featureId" => 69, "featureCode" => "Poweroff_diagnosis", "featureName" => "不开机诊断权限开关"]
                ]
            ],
            [
                "businessId" => 24,
                "businessCode" => "CALIB_SRV",
                "businessName" => "器件校准",
                "featureDTOs" => [
                    ["featureId" => 67, "featureCode" => "calib_procedure", "featureName" => "器件校准"]
                ]
            ],
            [
                "businessId" => 27,
                "businessCode" => "WRITEIMEIANDFLAG_SRV",
                "businessName" => "写号及国家码工具",
                "featureDTOs" => [
                    ["featureId" => 71, "featureCode" => "WRITEIMEIANDFLAG", "featureName" => "写号及标志位"]
                ]
            ],
            [
                "businessId" => 31,
                "businessCode" => "ONEPLUSFLASH_SRV",
                "businessName" => "小工具一加OFP整包刷机业务",
                "featureDTOs" => [
                    ["featureId" => 76, "featureCode" => "oneplusflash_plugin", "featureName" => "一加OFP整包刷机功能"]
                ]
            ]
        ]
    ]
];
// $response = <<<JSON
// {
//   "code" : "000000",
//   "data" : {
//       "areaCode" : "in",
//       "brand" : "realme",
//       "businessList" : [
//          {
//             "businessCode" : "READBACK_SRV",
//             "businessId" : 39,
//             "businessName" : "小工具回读业务",
//             "featureDTOs" : [
//               {
//                   "featureCode" : "READBACK_SRV",
//                   "featureId" : 92,
//                   "featureName" : "回读工具"
//               }
//             ]
//          },
//          {
//             "businessCode" : "UNLOCK_SRV",
//             "businessId" : 41,
//             "businessName" : "工模解密",
//             "featureDTOs" : [
//               {
//                   "featureCode" : "server_wizards",
//                   "featureId" : 82,
//                   "featureName" : "服务向导"
//               },
//               {
//                   "featureCode" : "server_wizards",
//                   "featureId" : 82,
//                   "featureName" : "服务向导"
//               },
//               {
//                   "featureCode" : "ONLINE_UNLOCK",
//                   "featureId" : 85,
//                   "featureName" : "在线工模解密"
//               },
//               {
//                   "featureCode" : "OFFLINE_UNLOCK",
//                   "featureId" : 86,
//                   "featureName" : "离线工模解密"
//               }
//             ]
//          },
//          {
//             "businessCode" : "FLASH_SRV",
//             "businessId" : 36,
//             "businessName" : "刷机工具",
//             "featureDTOs" : [
//               {
//                   "featureCode" : "flashtool_flashing",
//                   "featureId" : 83,
//                   "featureName" : "售后刷机"
//               },
//               {
//                   "featureCode" : "flashtool_flashing",
//                   "featureId" : 83,
//                   "featureName" : "售后刷机"
//               }
//             ]
//          },
//          {
//             "businessCode" : "DIAG_SRV",
//             "businessId" : 37,
//             "businessName" : "诊断插件",
//             "featureDTOs" : [
//               {
//                   "featureCode" : "device_diagnosis",
//                   "featureId" : 84,
//                   "featureName" : "修前修后诊断"
//               },
//               {
//                   "featureCode" : "device_diagnosis",
//                   "featureId" : 84,
//                   "featureName" : "修前修后诊断"
//               }
//             ]
//          }
//       ],
//       "regionCode" : "in",
//       "token" : "rcsm-e7f61ac6-13a7-41ee-aedf-dfcb307da4a0",
//       "toolCode" : "REALME_TOOLSHUB",
//       "toolId" : 20,
//       "toolName" : "realme售后支持",
//       "usrTypeCode" : "after_sale"
//   },
//   "msg" : "Success"
// }
// JSON;

$serverResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
//$serverResponse = $response;
}



// Decode the response to get the original token
$decodedResponse = json_decode($serverResponse, true);
$originalToken = $decodedResponse['data']['token'];

 $generatedToken =$config['site_sig'] ."V9". bin2hex(random_bytes(16));
//$generatedToken = generateRandomToken(); // This generates a random token
    $sql = "INSERT INTO tokens (generated_token, original_token, status) 
            VALUES (?, ?, 'unused')";
    
    // Prepare the SQL statement
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("ss", $generatedToken, $originalToken);

    if ($stmt->execute()) {
         // Token saved successfully
         $decodedResponse['data']['token'] = $generatedToken;

 header('Content-Type: application/json');
  echo json_encode($decodedResponse, JSON_PRETTY_PRINT); 
    } else {
         //   header('Content-Type: application/json');
//   echo json_encode($decodedResponse,true);

    $message = "Saving token error!";
  header(
      'Content-Type: application/json');
        echo json_encode([
            'code' => '050204',
            'data' => null,
            'msg' => $message
        ]);
        return;
}
    

// Save the generated token and original token to the database
// if (saveToken($generatedToken, $originalToken, $koneksi)) {
//   // echo "Tokens saved successfully! Generated Token: $generatedToken, Original Token: $originalToken";
//   header('Content-Type: application/json');
//   echo json_encode($decodedResponse,true);
// } else {
//     $message = "Saving token error!";
//   header(
//       'Content-Type: application/json');
//         echo json_encode([
//             'code' => '050204',
//             'data' => null,
//             'msg' => $message
//         ]);
//         return;
// }
?>
