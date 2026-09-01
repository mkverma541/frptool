<?php
// Include the database connection file (replace with your actual connection file)
include_once 'konak.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

$generatedToken = 'xptloginv3-b6dc6119df30c189e3a9a55ccdf71349';  // Replace with actual generated token

$sql = "SELECT original_token FROM tokens WHERE generated_token = ? AND status = 'unused' LIMIT 1";
$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Error preparing SQL statement: " . $koneksi->error);
}

$stmt->bind_param("s", $generatedToken);
$stmt->execute();
$stmt->bind_result($originalToken);
if ($stmt->fetch()) {
    $stmt->close();
    $updateSql = "UPDATE tokens SET status = 'used' WHERE generated_token = ?";
    $updateStmt = $koneksi->prepare($updateSql);
    if (!$updateStmt) {
        die("Error preparing update SQL statement: " . $koneksi->error);
    }

    $updateStmt->bind_param("s", $generatedToken);
    if ($updateStmt->execute()) {
        echo $originalToken;
    } else {
        echo "Error in updating token status.";
    }
    $updateStmt->close();
} else {
    echo "Error: Generated token not found or already used."; 
}

?>
