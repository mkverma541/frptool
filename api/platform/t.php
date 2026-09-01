<?php

require_once 'konak.php'; 

// Sample server ID (Replace with dynamic value if needed)
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
$newOtp = "abcedfsfaljsdf"; // Replace this with a generated or user-provided OTP

// Function to update server OTP
function updateServerOTP($serverId, $newOtp, $koneksi) {
    $stmt = $koneksi->prepare("UPDATE cotp SET otp = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $newOtp, $serverId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            return "✅ OTP updated successfully!";
        } else {
            return "⚠️ No changes made (OTP might be the same or ID not found).";
        }
        
        $stmt->close();
    } else {
        return "❌ Database error: Unable to prepare statement.";
    }
}

// Update OTP and show result
$result = updateServerOTP($serverId, $newOtp, $koneksi);
echo $result;
$otpCode = getServerCredentials($serverId,$koneksi);
echo $otpCode['otp'];
?>

