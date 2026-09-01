<?php

require_once 'konak.php'; 
$serverId = 1; // 2 is Europe , ID 4 is SG id.

// Function to fetch server username and password
function getServerCredentials($serverId, $koneksi) {
    $stmt = $koneksi->prepare("SELECT otp FROM cotp WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $serverId);
        $stmt->execute();
        $stmt->bind_result($otp);
        
        if ($stmt->fetch()) {
            return [
                'otp' => $otp
            ];
        } else {
            return "❌ No server found with ID: $serverId";
        }
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}

// Read the raw POST body
$rawInput = file_get_contents('php://input');

// Decode the JSON payload
$data = json_decode($rawInput, true); // true converts JSON object to associative array

// Check if 'toolCode' is present and validate it
if (isset($data['toolCode'])) {
    $toolCode = $data['toolCode'];
    $otpCode = getServerCredentials(1,$koneksi);
   $newOTP = $otpCode['otp'];
    // Validate or process the toolCode
        $url = "https://otp.sgsmpro.com/otp_eu.php";

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$headers = array(
   "Content-Type: application/x-www-form-urlencoded",
);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

$data = "disk_id=$newOTP";

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
            'code' => '050204',
            'data' => null,
            'msg' => $message
        ]);
        return;
    }
    // } else {
    //     header('Content-Type: application/json');
    //     echo json_encode([
    //         'code' => '050203',
    //         'data' => null,
    //         'msg' => 'OTP code not found in XPOWER ONEPLUS|OPPO Server'
    //     ]);
    //     return;
    // }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'No otp code provided in the request.'
    ]);
    return;
}

// $url = "http://eunovpnxpt.com/";

// // Use cURL to fetch the output
// $ch = curl_init();
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification if needed

// // Execute cURL request
// $output = curl_exec($ch);
// curl_close($ch);

// // Set the content type to JSON
// header('Content-Type: application/json');

// echo $output;
// This is android 15 url. add toekn also here.



// $curl = curl_init();

// curl_setopt_array($curl, array(
//   CURLOPT_URL => 'http://eunovpnxpt.com/login.php',
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_ENCODING => '',
//   CURLOPT_MAXREDIRS => 10,
//   CURLOPT_TIMEOUT => 0,
//   CURLOPT_FOLLOWLOCATION => true,
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//   CURLOPT_CUSTOMREQUEST => 'POST',
//   CURLOPT_POSTFIELDS =>'{"account":"{\\"cipher\\":\\"TrTxN0X4P2OX9u/hnaa8aJ8y8h7GRDY2\\",\\"iv\\":\\"n7sAjjOliiBFMuwD\\"}","password":"{\\"cipher\\":\\"TpHWbE2jZ2KqCglxlzEADG+JglKAq91eo9yA\\",\\"iv\\":\\"n7sAjjOliiBFMuwD\\"}","mac":"{\\"cipher\\":\\"MMiFN0X4Jyb1xJ5XqRlh5MVY2J4mGg==\\",\\"iv\\":\\"n7sAjjOliiBFMuwD\\"}","toolCode":"TOOLSHUB"}',
//   CURLOPT_HTTPHEADER => array(
//     'Accept:  */*',
//     'Content-Type:  application/json',
//     'use_for:  default',
//     'deviceId:  aDurqt/xodCaxtAJ/amxBLUDY/3F3IArCB7yB4660i4=',
//     'cipherInfo:  {"dfs-ms":{"protectedKey":"D4bvZFQxbn/N5NqJiz+f5MHfox+neP3ATRs5rs+9ftC73LFh73C67Emf8os7Wbpke7JIHto3EV0qQNqiPoR+u2CTefIxx342zOaEbiIWsN1otVK4DNc/kKiengHSIiQLMZAeRL3N4qe6HT/FI3An5WtmgxwoF0K7IRYUOckNKQN4J4fHUK/SVB2fMpL/lXyIROHiqCSuUvV4qNBmDkXZ2GsQ2tnnL6D27uKo63INVF4QvYRY27RTTaaL498d/Z+QEwJHm1ObhfwWRFznFOgcERbLWEsfNHTUj/q5GdnqUFNl/G/3PR21+NDbBLnAZQWcYYU/gXFKYEDO5+1XtyF6gw==","certVersion":"1661242029662","version":"1740390057"}}'
//   ),
// ));

// $response = curl_exec($curl);

// curl_close($curl);
//echo $response; this line also hide we already on fowwing 2 lines code

$response = <<<JSON
{
   "code" : "000000",
   "data" : {
      "areaCode" : "Eu",
      "brand" : "oppo",
      "businessList" : [
         {
            "businessCode" : "DIAG_SRV",
            "businessId" : 23,
            "businessName" : "诊断业务",
            "featureDTOs" : [
               {
                  "featureCode" : "device_diagnosis",
                  "featureId" : 65,
                  "featureName" : "修前&&修后"
               },
               {
                  "featureCode" : "offline_scan",
                  "featureId" : 66,
                  "featureName" : "脱机扫码"
               }
            ]
         },
         {
            "businessCode" : "CLEARDATA_SRV",
            "businessId" : 25,
            "businessName" : "数据双清",
            "featureDTOs" : [
               {
                  "featureCode" : "CLEAR_DEVICE_DATA",
                  "featureId" : 68,
                  "featureName" : "清除设备数据"
               }
            ]
         },
         {
            "businessCode" : "ONEPLUSFLASH_SRV",
            "businessId" : 31,
            "businessName" : "小工具一加OFP整包刷机业务",
            "featureDTOs" : [
               {
                  "featureCode" : "oneplusflash_plugin",
                  "featureId" : 76,
                  "featureName" : "一加OFP整包刷机功能"
               }
            ]
         },
         {
            "businessCode" : "WRITEIMEIANDFLAG_SRV",
            "businessId" : 27,
            "businessName" : "写号及国家码工具",
            "featureDTOs" : [
               {
                  "featureCode" : "WRITEIMEIANDFLAG",
                  "featureId" : 71,
                  "featureName" : "写号及标志位"
               }
            ]
         },
         {
            "businessCode" : "POWEROFF_DIAG_SRV",
            "businessId" : 26,
            "businessName" : "不开机诊断",
            "featureDTOs" : [
               {
                  "featureCode" : "Poweroff_diagnosis",
                  "featureId" : 69,
                  "featureName" : "不开机诊断权限开关"
               }
            ]
         },
         {
            "businessCode" : "CALIB_SRV",
            "businessId" : 24,
            "businessName" : "器件校准",
            "featureDTOs" : [
               {
                  "featureCode" : "calib_procedure",
                  "featureId" : 67,
                  "featureName" : "器件校准"
               }
            ]
         },
         {
            "businessCode" : "FLASH_SRV",
            "businessId" : 29,
            "businessName" : "刷机插件",
            "featureDTOs" : [
               {
                  "featureCode" : "ftflash_pkg_manage",
                  "featureId" : 72,
                  "featureName" : "固件包管理"
               },
               {
                  "featureCode" : "flashtool_basic_flashing",
                  "featureId" : 74,
                  "featureName" : "基础刷机功能"
               }
            ]
         },
         {
            "businessCode" : "READBACK_SRV",
            "businessId" : 28,
            "businessName" : "小工具回读业务",
            "featureDTOs" : [
               {
                  "featureCode" : "readback_plugin",
                  "featureId" : 70,
                  "featureName" : "回读功能"
               },
               {
                  "featureCode" : "readback_plugin",
                  "featureId" : 70,
                  "featureName" : "回读功能"
               }
            ]
         },
         {
            "businessCode" : "UNLOCK_SRV",
            "businessId" : 14,
            "businessName" : "工模解密",
            "featureDTOs" : [
               {
                  "featureCode" : "OFFLINE_UNLOCK",
                  "featureId" : 46,
                  "featureName" : "离线解密"
               },
               {
                  "featureCode" : "ONLINE_UNLOCK",
                  "featureId" : 47,
                  "featureName" : "在线解密"
               }
            ]
         }
      ],
      "regionCode" : "De",
      "token" : "b5a04707-c59a-4c42-809f-b6a0b3e6936b",
      "toolCode" : "TOOLSHUB",
      "toolId" : 13,
      "toolName" : "O+支持",
      "usrTypeCode" : "after_sale"
   },
   "msg" : "Success"
}
JSON;


//Don;t replace this 2 lines
 header('Content-Type: application/json');
echo $response;

?>
