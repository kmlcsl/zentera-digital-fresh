<?php

// api/index.php - Updated untuk handle webhook dengan struktur yang sudah ada

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Set environment untuk Vercel
$app->useEnvironmentPath(__DIR__ . '/..');

// Handle specific webhook requests sebelum Laravel routing
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Log untuk debugging
error_log("Vercel Request: {$requestMethod} {$requestUri}");

// Handle webhook WhatsApp secara langsung
if (strpos($requestUri, '/api/webhook/whatsapp') !== false) {
    handleWhatsAppWebhook();
    exit;
}

// Handle simple FONNTE test
if (strpos($requestUri, '/simple-fonnte') !== false) {
    simpleFonnteTest();
    exit;
}

// Default Laravel request handling
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);

/**
 * Simple FONNTE test using exec (if available)
 */
function simpleFonnteTest()
{
    header('Content-Type: application/json');

    $phone = $_GET['phone'] ?? '6281383894808';
    $message = $_GET['message'] ?? '🧪 Test simple dari Vercel!';

    // Try using exec curl (if available in Vercel)
    $cmd = "curl -X POST https://api.fonnte.com/send " .
        "-H 'Authorization: ejiQakcm45Vs2rZuWwPL' " .
        "-d 'target=" . escapeshellarg($phone) . "' " .
        "-d 'message=" . escapeshellarg($message) . "'";

    error_log("EXEC CMD: " . $cmd);

    $output = [];
    $return_code = 0;

    if (function_exists('exec')) {
        exec($cmd, $output, $return_code);
        $result = implode("\n", $output);

        error_log("EXEC Result: " . $result);
        error_log("EXEC Return Code: " . $return_code);

        echo json_encode([
            'status' => $return_code === 0 ? 'success' : 'failed',
            'method' => 'exec_curl',
            'phone' => $phone,
            'message' => $message,
            'output' => $result,
            'return_code' => $return_code
        ]);
    } else {
        error_log("EXEC not available, trying alternative");

        // Fallback to our socket method
        $result = sendWhatsAppMessage($phone, $message);

        echo json_encode([
            'status' => $result ? 'success' : 'failed',
            'method' => 'socket',
            'phone' => $phone,
            'message' => $message,
            'result' => $result
        ]);
    }
}

/**
 * Test FONNTE API directly
 */
function testFonnteAPI()
{
    header('Content-Type: application/json');

    $phone = $_GET['phone'] ?? '6281383894808';
    $message = $_GET['message'] ?? '🧪 Test pesan dari Zentera Digital Bot! 🤖';

    error_log("Testing FONNTE API with phone: {$phone}");

    $result = sendWhatsAppMessage($phone, $message);

    echo json_encode([
        'status' => $result ? 'success' : 'failed',
        'phone' => $phone,
        'message' => $message,
        'result' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Handle webhook status dari FONNTE
 */
function handleWebhookStatus()
{
    header('Content-Type: application/json');

    try {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        error_log('Webhook Status: ' . json_encode($data));

        // Respond dengan status OK untuk webhook status
        echo json_encode([
            'status' => 'ok',
            'message' => 'Status received'
        ]);
    } catch (\Exception $e) {
        error_log('Webhook Status Error: ' . $e->getMessage());
        echo json_encode(['status' => 'error']);
    }
}

/**
 * Handle WhatsApp webhook secara langsung
 */
function handleWhatsAppWebhook()
{
    // Set headers
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    try {
        // Handle OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            echo json_encode(['status' => 'ok']);
            return;
        }

        // Handle GET request (webhook verification)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode([
                'status' => 'webhook_active',
                'message' => 'WhatsApp webhook is ready',
                'timestamp' => date('Y-m-d H:i:s'),
                'server' => 'Zentera Digital - Vercel',
                'url' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? ''
            ]);
            return;
        }

        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            // ENHANCED LOGGING untuk real webhook
            error_log("=== REAL WEBHOOK RECEIVED ===");
            error_log("Raw Input: " . $rawInput);
            error_log("Parsed Data: " . json_encode($data, JSON_PRETTY_PRINT));
            error_log("Headers: " . json_encode(getallheaders() ?: [], JSON_PRETTY_PRINT));
            error_log("Server Info: " . json_encode([
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
                'REQUEST_URI' => $_SERVER['REQUEST_URI'],
                'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ], JSON_PRETTY_PRINT));
            error_log("==============================");

            // Validate JSON data
            if (!$data) {
                error_log('Invalid JSON data received: ' . $rawInput);
                http_response_code(400);
                echo json_encode([
                    'status' => 'invalid_json',
                    'raw_input' => substr($rawInput, 0, 200)
                ]);
                return;
            }

            // Handle webhook status messages (different from chat messages)
            if (isset($data['state'])) {
                error_log('Webhook status message received: ' . json_encode($data));
                echo json_encode(['status' => 'ok', 'message' => 'Status received']);
                return;
            }

            // Validate webhook data
            $message = null;
            $sender = null;

            // Try different FONNTE formats
            if (isset($data['pesan']) && isset($data['pengirim'])) {
                $message = $data['pesan'];
                $sender = $data['pengirim'];
                error_log('Using FONNTE format: pesan/pengirim');
            } elseif (isset($data['message']) && isset($data['sender'])) {
                $message = $data['message'];
                $sender = $data['sender'];
                error_log('Using standard format: message/sender');
            } elseif (isset($data['text']) && isset($data['sender'])) {
                $message = $data['text'];
                $sender = $data['sender'];
                error_log('Using alternative format: text/sender');
            }

            if (!$message || !$sender) {
                error_log('Invalid webhook data - missing message or sender');
                http_response_code(400);
                echo json_encode([
                    'status' => 'invalid_data',
                    'available_keys' => array_keys($data),
                    'data' => $data
                ]);
                return;
            }

            // Clean phone number
            $phone = str_replace(['@s.whatsapp.net', '@c.us'], '', $sender);
            $phone = preg_replace('/[^0-9]/', '', $phone);

            error_log("Processed phone: {$phone} from sender: {$sender}");

            // Skip if from bot or group
            if ((isset($data['fromMe']) && $data['fromMe']) ||
                (isset($data['isgroup']) && $data['isgroup'])
            ) {
                error_log('Skipping message: fromMe or isGroup');
                echo json_encode(['status' => 'ignored_message']);
                return;
            }

            // Extract order number
            $orderNumber = extractOrderNumber($message);
            error_log("PROCESSING: phone={$phone}, order={$orderNumber}");

            if ($orderNumber) {
                error_log("CALLING: handleOrderMessage");
                handleOrderMessage($phone, $orderNumber, $message);
            } else {
                error_log("CALLING: handleGeneralMessage");
                handleGeneralMessage($phone, $message);
            }
        }
    } catch (\Exception $e) {
        error_log('Webhook Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]);
    }
}

/**
 * Extract order number from message
 */
function extractOrderNumber($message)
{
    $patterns = [
        '/🔢\s*Order:\s*(DOC\d{8}\d{3})/i',  // "🔢 Order: DOC20250612004"
        '/Order:\s*(DOC\d{8}\d{3})/i',        // "Order: DOC20250612004"
        '/(DOC\d{8}\d{3})/',                  // Direct match
        '/No\.?\s*Order:\s*(DOC\d{8}\d{3})/i' // "No. Order:"
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $message, $matches)) {
            error_log("Order found with pattern: {$pattern}, match: {$matches[1]}");
            return $matches[1];
        }
    }

    return null;
}

/**
 * Check if message is payment confirmation
 */
function isPaymentConfirmation($message)
{
    $keywords = [
        'pembayaran berhasil',
        'payment berhasil',
        'sudah bayar',
        'transfer berhasil',
        'bukti transfer',
        'sudah transfer',
        'telah melakukan pembayaran',
        'mohon segera diproses',
        'sudah diupload',
        '✅ saya telah melakukan pembayaran'
    ];

    $messageLower = strtolower($message);
    foreach ($keywords as $keyword) {
        if (strpos($messageLower, $keyword) !== false) {
            error_log("Payment confirmation keyword found: {$keyword}");
            return true;
        }
    }

    return false;
}

/**
 * Handle order-related message
 */
function handleOrderMessage($phone, $orderNumber, $message)
{
    error_log("=== HANDLING ORDER MESSAGE ===");
    error_log("Phone: {$phone}");
    error_log("Order: {$orderNumber}");
    error_log("Is Payment: " . (isPaymentConfirmation($message) ? 'YES' : 'NO'));

    if (isPaymentConfirmation($message)) {
        $replyMessage = "✅ **PEMBAYARAN DITERIMA**\n\n" .
            "📋 Detail Order:\n" .
            "🔢 No. Order: #{$orderNumber}\n" .
            "📊 Status: Sedang diproses\n\n" .
            "⏰ Estimasi: 1-2 hari kerja\n\n" .
            "Kami akan mengirimkan hasilnya via WhatsApp setelah selesai.\n\n" .
            "Terima kasih atas kepercayaan Anda! 🙏\n\n" .
            "Zentera Digital - Solusi Dokumen Terpercaya ✨";

        error_log("SENDING PAYMENT CONFIRMATION to {$phone}");
        $sendResult = sendWhatsAppMessage($phone, $replyMessage);
        error_log("SEND RESULT: " . json_encode($sendResult));

        echo json_encode([
            'status' => 'success',
            'action' => 'payment_confirmed',
            'order_number' => $orderNumber,
            'phone' => $phone,
            'send_result' => $sendResult
        ]);
    } else {
        $replyMessage = "Terima kasih atas pesan Anda terkait order #{$orderNumber}.\n\n" .
            "📋 Pesanan Anda sedang dalam proses.\n\n" .
            "Kami akan segera memberikan update terbaru. " .
            "Jika ada pertanyaan lebih lanjut, silakan hubungi customer service kami.\n\n" .
            "Terima kasih! 🙏";

        error_log("SENDING ORDER INQUIRY to {$phone}");
        $sendResult = sendWhatsAppMessage($phone, $replyMessage);
        error_log("SEND RESULT: " . json_encode($sendResult));

        echo json_encode([
            'status' => 'success',
            'action' => 'order_inquiry',
            'order_number' => $orderNumber,
            'phone' => $phone,
            'send_result' => $sendResult
        ]);
    }

    error_log("=== ORDER MESSAGE HANDLED ===");
}

/**
 * Handle general message
 */
function handleGeneralMessage($phone, $message)
{
    error_log("Handling general message");

    $messageLower = strtolower($message);

    if (
        strpos($messageLower, 'halo') !== false ||
        strpos($messageLower, 'hello') !== false ||
        strpos($messageLower, 'hai') !== false ||
        strpos($messageLower, 'hi') !== false
    ) {

        $replyMessage = "Halo! Selamat datang di Zentera Digital 👋\n\n" .
            "Kami adalah layanan profesional untuk:\n" .
            "🔧 Perbaikan Dokumen\n" .
            "📝 Format & Daftar Isi\n" .
            "🔍 Cek Plagiarisme Turnitin\n\n" .
            "Jika Anda memiliki pertanyaan tentang order, silakan sertakan nomor order Anda.\n\n" .
            "Terima kasih! 😊";
    } else {
        $replyMessage = "Terima kasih atas pesan Anda! 🙏\n\n" .
            "Untuk mendapatkan bantuan yang lebih baik, mohon sertakan:\n" .
            "• Nomor order (jika ada)\n" .
            "• Detail pertanyaan Anda\n\n" .
            "Tim customer service kami akan segera membantu Anda.\n\n" .
            "Zentera Digital - Solusi Dokumen Terpercaya ✨";
    }

    sendWhatsAppMessage($phone, $replyMessage);

    echo json_encode([
        'status' => 'success',
        'action' => 'general_message',
        'phone' => $phone
    ]);
}

/**
 * Send WhatsApp message using FONNTE API (HTTPS Socket untuk Vercel)
 */
function sendWhatsAppMessage($phone, $message)
{
    $token = 'ejiQakcm45Vs2rZuWwPL';

    // Clean phone number
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (!str_starts_with($cleanPhone, '62')) {
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } else if (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '62' . $cleanPhone;
        }
    }

    error_log("FONNTE SEND - Phone: {$phone} -> {$cleanPhone}");

    // Use HTTPS socket with SSL context
    $postData = 'target=' . urlencode($cleanPhone) . '&message=' . urlencode($message);
    $postLength = strlen($postData);

    $request = "POST /send HTTP/1.1\r\n";
    $request .= "Host: api.fonnte.com\r\n";
    $request .= "Authorization: {$token}\r\n";
    $request .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $request .= "Content-Length: {$postLength}\r\n";
    $request .= "Connection: close\r\n\r\n";
    $request .= $postData;

    error_log("FONNTE SEND - HTTPS request");

    // Create SSL context
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    // Open HTTPS socket connection
    $socket = @stream_socket_client('ssl://api.fonnte.com:443', $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        error_log("FONNTE ERROR: HTTPS Socket connection failed - {$errno}: {$errstr}");

        // Fallback: Try file_get_contents with better error handling
        return fallbackHttpRequest($cleanPhone, $message, $token);
    }

    // Send request
    fwrite($socket, $request);

    // Get response
    $response = '';
    while (!feof($socket)) {
        $response .= fgets($socket, 1024);
    }
    fclose($socket);

    error_log("FONNTE SEND - HTTPS response received");

    // Parse response
    $parts = explode("\r\n\r\n", $response, 2);
    if (count($parts) < 2) {
        error_log("FONNTE ERROR: Invalid HTTPS response format");
        return false;
    }

    $body = $parts[1];
    $result = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("FONNTE ERROR: JSON decode failed - " . json_last_error_msg());
        return false;
    }

    error_log("FONNTE HTTPS result: " . json_encode($result));

    return isset($result['status']) && $result['status'] === true ? $result : false;
}

/**
 * Fallback HTTP request method
 */
function fallbackHttpRequest($phone, $message, $token)
{
    error_log("FONNTE FALLBACK - Using file_get_contents");

    $postData = http_build_query([
        'target' => $phone,
        'message' => $message
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n" .
                "Authorization: {$token}\r\n",
            'content' => $postData,
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $response = @file_get_contents('https://api.fonnte.com/send', false, $context);

    if ($response === false) {
        error_log("FONNTE FALLBACK: file_get_contents failed");
        $error = error_get_last();
        if ($error) {
            error_log("FONNTE FALLBACK ERROR: " . $error['message']);
        }
        return false;
    }

    $result = json_decode($response, true);
    error_log("FONNTE FALLBACK result: " . json_encode($result));

    return isset($result['status']) && $result['status'] === true ? $result : false;
}
