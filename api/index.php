<?php
// api/index.php - Simple API Handler for Zentera Digital

// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api', '', $uri); // Remove /api prefix

// Simple routing
switch ($uri) {
    case '/':
    case '/index.php':
        handleApiInfo();
        break;

    case '/webhook/whatsapp':
        handleWhatsAppWebhook();
        break;

    case '/test':
        handleTest();
        break;

    default:
        handleNotFound();
        break;
}

// API Info endpoint
function handleApiInfo()
{
    $response = [
        'status' => 'success',
        'message' => 'Zentera Digital API is running',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => 'Zentera Digital - Vercel',
        'endpoints' => [
            'GET /api/' => 'API Information',
            'GET /api/test' => 'Test endpoint',
            'POST /api/webhook/whatsapp' => 'WhatsApp Webhook',
            'GET /api/webhook/whatsapp' => 'WhatsApp Webhook Verification'
        ],
        'documentation' => 'https://www.zenteradigital.my.id/docs'
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
}

// WhatsApp Webhook handler
function handleWhatsAppWebhook()
{
    global $method;

    if ($method === 'GET') {
        // Webhook verification (for WhatsApp Business API)
        $hub_challenge = $_GET['hub_challenge'] ?? null;
        $hub_verify_token = $_GET['hub_verify_token'] ?? null;

        // Replace with your actual verify token
        $verify_token = 'your_webhook_verify_token';

        if ($hub_verify_token === $verify_token && $hub_challenge) {
            echo $hub_challenge;
            exit();
        }

        // Default webhook status
        $response = [
            'status' => 'webhook_active',
            'message' => 'Wablas webhook is ready',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => 'Zentera Digital - Vercel',
            'provider' => 'Wablas'
        ];

        echo json_encode($response);
    } elseif ($method === 'POST') {
        // Handle incoming webhook data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Log webhook data (you can save to database or file)
        error_log('WhatsApp Webhook: ' . $input);

        // Process webhook data here
        processWhatsAppWebhook($data);

        // Respond to webhook
        $response = [
            'status' => 'received',
            'message' => 'Webhook processed successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }
}

// Process WhatsApp webhook data
function processWhatsAppWebhook($data)
{
    // Add your webhook processing logic here
    // Example: save to database, send notifications, etc.

    if (isset($data['messages'])) {
        foreach ($data['messages'] as $message) {
            // Process each message
            $phone = $message['phone'] ?? '';
            $text = $message['message'] ?? '';

            // Your processing logic here
            // saveMessageToDatabase($phone, $text);
            // sendAutoReply($phone);
        }
    }
}

// Test endpoint
function handleTest()
{
    $response = [
        'status' => 'success',
        'message' => 'API test successful',
        'timestamp' => date('Y-m-d H:i:s'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI']
        ],
        'headers' => getallheaders()
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
}

// 404 handler
function handleNotFound()
{
    http_response_code(404);

    $response = [
        'status' => 'error',
        'message' => 'Endpoint not found',
        'requested_uri' => $_SERVER['REQUEST_URI'],
        'timestamp' => date('Y-m-d H:i:s'),
        'available_endpoints' => [
            'GET /api/' => 'API Information',
            'GET /api/test' => 'Test endpoint',
            'POST /api/webhook/whatsapp' => 'WhatsApp Webhook',
            'GET /api/webhook/whatsapp' => 'WhatsApp Webhook Verification'
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
}

// Error handler
function handleError($message, $code = 500)
{
    http_response_code($code);

    $response = [
        'status' => 'error',
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'code' => $code
    ];

    echo json_encode($response);
}

// Set error handler
set_error_handler(function ($severity, $message, $file, $line) {
    handleError("Internal server error: $message", 500);
});

set_exception_handler(function ($exception) {
    handleError("Exception: " . $exception->getMessage(), 500);
});
