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

// Default Laravel request handling
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);

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

            // Enhanced logging
            error_log('WhatsApp Webhook Data: ' . json_encode([
                'raw_input' => $rawInput,
                'parsed_data' => $data,
                'headers' => getallheaders(),
                'server' => $_SERVER
            ]));

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
            error_log("Extracted order number: " . ($orderNumber ?: 'none'));

            if ($orderNumber) {
                handleOrderMessage($phone, $orderNumber, $message);
            } else {
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
    error_log("Handling order message for: {$orderNumber}");

    // Since we can't easily access Laravel models here, send a generic response
    // You might need to implement database connection manually or use Laravel's container

    if (isPaymentConfirmation($message)) {
        $replyMessage = "✅ **PEMBAYARAN DITERIMA**\n\n" .
            "📋 Detail Order:\n" .
            "🔢 No. Order: #{$orderNumber}\n" .
            "📊 Status: Sedang diproses\n\n" .
            "⏰ Estimasi: 1-2 hari kerja\n\n" .
            "Kami akan mengirimkan hasilnya via WhatsApp setelah selesai.\n\n" .
            "Terima kasih atas kepercayaan Anda! 🙏\n\n" .
            "Zentera Digital - Solusi Dokumen Terpercaya ✨";

        sendWhatsAppMessage($phone, $replyMessage);

        echo json_encode([
            'status' => 'success',
            'action' => 'payment_confirmed',
            'order_number' => $orderNumber,
            'phone' => $phone
        ]);
    } else {
        $replyMessage = "Terima kasih atas pesan Anda terkait order #{$orderNumber}.\n\n" .
            "📋 Pesanan Anda sedang dalam proses.\n\n" .
            "Kami akan segera memberikan update terbaru. " .
            "Jika ada pertanyaan lebih lanjut, silakan hubungi customer service kami.\n\n" .
            "Terima kasih! 🙏";

        sendWhatsAppMessage($phone, $replyMessage);

        echo json_encode([
            'status' => 'success',
            'action' => 'order_inquiry',
            'order_number' => $orderNumber,
            'phone' => $phone
        ]);
    }
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
 * Send WhatsApp message using FONNTE API
 */
function sendWhatsAppMessage($phone, $message)
{
    $token = 'ejiQakcm45Vs2rZuWwPL'; // Token FONNTE Anda

    $data = [
        'target' => $phone,
        'message' => $message,
        'countryCode' => '62'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("WhatsApp send error: " . $err);
        return false;
    } else {
        error_log("WhatsApp send response: " . $response);
        return json_decode($response, true);
    }
}
