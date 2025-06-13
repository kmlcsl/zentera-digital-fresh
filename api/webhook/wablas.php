<?php

/**
 * Wablas WhatsApp Webhook API for Zentera Digital
 * COPY YOUR ACTUAL TOKEN AND BASE URL HERE
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// Main webhook handler
if (strpos($requestUri, '/webhook/wablas') !== false || strpos($requestUri, '/api/webhook/wablas') !== false) {
    handleWablasWebhook();
    exit;
}

// Handle GET request for verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'webhook_active',
        'message' => 'Wablas webhook is ready',
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => 'Zentera Digital - Vercel',
        'provider' => 'Wablas'
    ]);
    exit;
}

/**
 * Handle incoming Wablas webhook
 */
function handleWablasWebhook()
{
    header('Content-Type: application/json');

    try {
        // Handle POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get raw input
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            // Enhanced logging
            error_log("=== WABLAS WEBHOOK RECEIVED ===");
            error_log("Raw Input: " . $rawInput);
            error_log("Parsed Data: " . json_encode($data, JSON_PRETTY_PRINT));
            error_log("===============================");

            // Validate JSON data
            if (!$data) {
                error_log('Invalid JSON data received: ' . $rawInput);
                http_response_code(400);
                echo json_encode(['status' => 'invalid_json']);
                return;
            }

            // Extract message data from Wablas format
            $phone = $data['phone'] ?? $data['from'] ?? '';
            $message = $data['message'] ?? $data['body'] ?? '';
            $messageType = $data['type'] ?? 'text';

            // Clean phone number
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (!str_starts_with($phone, '62')) {
                if (str_starts_with($phone, '0')) {
                    $phone = '62' . substr($phone, 1);
                } else if (str_starts_with($phone, '8')) {
                    $phone = '62' . $phone;
                }
            }

            error_log("PROCESSING: phone={$phone}, message=" . substr($message, 0, 100));

            // Only process text messages
            if ($messageType !== 'text' || empty($message)) {
                echo json_encode(['status' => 'ignored', 'reason' => 'non_text_message']);
                return;
            }

            // Extract order number if any
            $orderNumber = extractOrderNumber($message);

            if ($orderNumber) {
                error_log("ORDER DETECTED: {$orderNumber}");
                handleOrderMessage($phone, $orderNumber, $message);
            } else {
                error_log("GENERAL MESSAGE");
                handleGeneralMessage($phone, $message);
            }
        } else {
            // Handle GET request
            echo json_encode([
                'status' => 'wablas_webhook_ready',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Exception $e) {
        error_log('Webhook error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Extract order number from message
 */
function extractOrderNumber($message)
{
    // Pattern untuk format: Order: DOC20250612004 atau #DOC20250612004
    if (preg_match('/(?:order[:\s#]*|#)\s*([A-Z]{2,}\d{8,})\b/i', $message, $matches)) {
        return strtoupper($matches[1]);
    }
    return null;
}

/**
 * Check if message is payment confirmation
 */
function isPaymentConfirmation($message)
{
    $paymentKeywords = [
        'pembayaran berhasil',
        'payment success',
        'telah melakukan pembayaran',
        'bukti transfer',
        'sudah transfer',
        'sudah bayar',
        'pembayaran selesai',
        'transfer berhasil'
    ];

    $messageText = strtolower($message);
    foreach ($paymentKeywords as $keyword) {
        if (strpos($messageText, $keyword) !== false) {
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
        $replyMessage = "✅ *PEMBAYARAN DITERIMA*\n\n" .
            "📋 Detail Order:\n" .
            "🔢 No. Order: #{$orderNumber}\n" .
            "📊 Status: Sedang diproses\n\n" .
            "⏰ Estimasi: 1-2 hari kerja\n\n" .
            "Kami akan mengirimkan hasilnya via WhatsApp setelah selesai.\n\n" .
            "Terima kasih atas kepercayaan Anda! 🙏\n\n" .
            "*Zentera Digital - Solusi Dokumen Terpercaya* ✨";

        error_log("SENDING PAYMENT CONFIRMATION to {$phone}");
        $sendResult = sendWablasMessage($phone, $replyMessage);
        error_log("SEND RESULT: " . json_encode($sendResult));

        echo json_encode([
            'status' => 'success',
            'action' => 'payment_confirmed',
            'order_number' => $orderNumber,
            'phone' => $phone,
            'send_result' => $sendResult
        ]);
    } else {
        $replyMessage = "Terima kasih atas pesan Anda terkait order *#{$orderNumber}*.\n\n" .
            "📋 Pesanan Anda sedang dalam proses.\n\n" .
            "Kami akan segera memberikan update terbaru. " .
            "Jika ada pertanyaan lebih lanjut, silakan hubungi customer service kami.\n\n" .
            "Terima kasih! 🙏";

        error_log("SENDING ORDER INQUIRY to {$phone}");
        $sendResult = sendWablasMessage($phone, $replyMessage);
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
 * Handle general message (non-order related)
 */
function handleGeneralMessage($phone, $message)
{
    error_log("=== HANDLING GENERAL MESSAGE ===");
    error_log("Phone: {$phone}");
    error_log("Message: " . substr($message, 0, 100));

    $replyMessage = "Halo! 👋 Terima kasih telah menghubungi *Zentera Digital*.\n\n" .
        "Kami adalah layanan profesional untuk:\n" .
        "📄 Cek Plagiarisme Turnitin\n" .
        "📊 Analisis Dokumen\n" .
        "✍️ Editing & Proofreading\n\n" .
        "Silakan kirimkan detail kebutuhan Anda atau kunjungi website kami untuk pemesanan langsung.\n\n" .
        "🌐 Website: www.zenteradigital.my.id\n\n" .
        "Terima kasih! 🙏\n\n" .
        "*Zentera Digital - Solusi Dokumen Terpercaya* ✨";

    error_log("SENDING GENERAL RESPONSE to {$phone}");
    $sendResult = sendWablasMessage($phone, $replyMessage);
    error_log("SEND RESULT: " . json_encode($sendResult));

    echo json_encode([
        'status' => 'success',
        'action' => 'general_message',
        'phone' => $phone,
        'send_result' => $sendResult
    ]);

    error_log("=== GENERAL MESSAGE HANDLED ===");
}

/**
 * Send WhatsApp message using Wablas API
 */
function sendWablasMessage($phone, $message)
{
    // =============================================================
    // WABLAS CONFIGURATION - WITH SECRET KEY
    // =============================================================
    $wablasToken = '7GOkB1jALee81YZIsTtbSFBSWSa8llEL1W4OpiuPQMGkme2ppibzVMT';
    $wablasBaseUrl = 'https://sby.wablas.com';
    $wablasSecretKey = 'FFxhpBHa';
    // =============================================================

    error_log("WABLAS SEND - Phone: {$phone}");
    error_log("WABLAS SEND - Message length: " . strlen($message));
    error_log("WABLAS SEND - Token: " . substr($wablasToken, 0, 10) . "...");
    error_log("WABLAS SEND - Base URL: {$wablasBaseUrl}");

    // Prepare data for Wablas API - with secret key
    $postData = json_encode([
        'phone' => $phone,
        'message' => $message,
        'secret' => $wablasSecretKey
    ]);

    // Headers for Wablas
    $headers = [
        'Content-Type: application/json',
        'Authorization: ' . $wablasToken
    ];

    // Create context for HTTP request
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $postData,
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    try {
        error_log("WABLAS SEND - Making API call to: {$wablasBaseUrl}/api/send-message");

        $response = file_get_contents($wablasBaseUrl . '/api/send-message', false, $context);

        if ($response === false) {
            error_log("WABLAS ERROR: Request failed");
            $error = error_get_last();
            if ($error) {
                error_log("WABLAS ERROR Details: " . json_encode($error));
            }
            return false;
        }

        error_log("WABLAS RAW RESPONSE: " . $response);

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("WABLAS JSON ERROR: " . json_last_error_msg());
            return false;
        }

        // Check Wablas response format
        if (isset($result['status']) && $result['status'] === true) {
            error_log("WABLAS SUCCESS: " . json_encode($result));
            return $result;
        } else {
            error_log("WABLAS FAILED: " . json_encode($result));
            return false;
        }
    } catch (Exception $e) {
        error_log("WABLAS EXCEPTION: " . $e->getMessage());
        return false;
    }
}
