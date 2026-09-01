<?php
// API endpoint
$url = "https://koninedev.com/api/event/trace/getBizCfgByBusinessId";

// Initialize cURL session
$curl = curl_init();

// Set cURL options
curl_setopt($curl, CURLOPT_URL, $url);           // Set the URL
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL host verification (for development purposes)
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL peer verification (for development purposes)

// Execute cURL request
$response = curl_exec($curl);

// Check for errors
if (curl_errno($curl)) {
    echo "cURL Error: " . curl_error($curl);
} else {
    // Decode and display the response
    $responseData = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo $response;
    } else {
        echo "Failed to decode JSON response: " . json_last_error_msg();
        echo "<br>Raw Response:<br>" . $response;
    }
}

// Close cURL session
curl_close($curl);
?>
