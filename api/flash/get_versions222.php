<?php
// Target URL to forward the request
$targetUrl = "https://dfs-server-gl.allawntech.com/api/flash/get_versions";

// Proxy configuration
$proxyIp = "115.167.65.89";
$proxyPort = "50100"; // HTTP/HTTPS port
$proxyUser = "mondalrajib9832";
$proxyPassword = "knM5ZIfU4c";

// Capture all headers from the incoming request
$incomingHeaders = getallheaders();

// Prepare headers for forwarding
$forwardHeaders = [];
foreach ($incomingHeaders as $key => $value) {
    if (strtolower($key) === 'host') {
        // Override the Host header
        $forwardHeaders[] = "Host: dfs-server-gl.allawntech.com";
    } else {
        $forwardHeaders[] = "$key: $value";
    }
}

// Capture the raw POST data
$postData = file_get_contents('php://input');

// Initialize cURL for forwarding the request
$ch = curl_init($targetUrl);

// Set cURL options
curl_setopt($ch, CURLOPT_POST, true); // Forward as POST request
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders); // Forward all headers
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // Forward POST data
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Capture response
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification (useful for testing)

// Set proxy
curl_setopt($ch, CURLOPT_PROXY, "$proxyIp:$proxyPort"); // Set proxy IP and port
curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxyUser:$proxyPassword"); // Set proxy login credentials
curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Use HTTP/HTTPS proxy

// Follow redirects
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Allow cURL to follow redirects
curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Set a maximum number of redirects

// Execute the forwarded request
$response = curl_exec($ch);

// Handle errors
if (curl_errno($ch)) {
    http_response_code(500); // Internal Server Error
    echo "cURL Error: " . curl_error($ch);
} else {
    // Forward the response back to the original client
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
    http_response_code($httpCode); // Match the response status
    echo $response; // Return the API's response
}

// Close cURL
curl_close($ch);
?>
