<?php
/**
 * Glorifix Media Concept Limited — Contact & Consultation Booking API
 * High-Deliverability Dual Notification System:
 * 1. Logs lead to admin/data/inquiries.json (Instant Admin Dashboard visibility)
 * 2. Sends Admin Notification to glorifixmediaconcept@gmail.com with anti-spam RFC headers
 * 3. Sends Personalized Branded Confirmation Email directly to the Client's email address
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

require_once __DIR__ . '/Mailer.php';

// Support JSON payload or standard Form POST
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);
$data = is_array($json_data) ? $json_data : $_POST;

// Extract fields cleanly
$name     = trim($data['Full_Name'] ?? $data['Sender_Name'] ?? $data['name'] ?? '');
$email    = trim($data['Email_Address'] ?? $data['Sender_Email'] ?? $data['email'] ?? '');
$phone    = trim($data['Phone_Number'] ?? $data['Sender_Phone'] ?? $data['phone'] ?? '');
$service  = trim($data['Service_Requested'] ?? $data['Subject'] ?? $data['service'] ?? 'General Strategy Consultation');
$notes    = trim($data['Project_Notes'] ?? $data['Inquiry_Message'] ?? $data['Message'] ?? $data['notes'] ?? $data['message'] ?? '');
$app_date = trim($data['Appointment_Date'] ?? $data['date'] ?? '');
$app_time = trim($data['Appointment_Time'] ?? $data['time'] ?? '');

$is_booking = !empty($app_date) || isset($data['Appointment_Date']) || stripos($service, 'booking') !== false;
$inq_type   = $is_booking ? 'Consultation Booking' : 'Direct Inquiry';

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide your full name.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide a valid email address so we can send your confirmation.']);
    exit;
}

// 1. Save record to admin/data/inquiries.json
$inquiries_file = __DIR__ . '/../admin/data/inquiries.json';
$inquiries = [];
if (file_exists($inquiries_file)) {
    $inquiries = json_decode(file_get_contents($inquiries_file), true) ?: [];
}

$inquiry_id = 'INQ-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
$new_entry = [
    'id'               => $inquiry_id,
    'timestamp'        => date('Y-m-d H:i:s'),
    'type'             => $inq_type,
    'name'             => $name,
    'email'            => $email,
    'phone'            => $phone,
    'service'          => $service,
    'appointment_date' => $app_date,
    'appointment_time' => $app_time,
    'notes'            => $notes,
    'status'           => 'new',
    'read'             => false,
    'ip'               => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];

array_unshift($inquiries, $new_entry);
@file_put_contents($inquiries_file, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// 2. Dispatch Dual Emails via GlorifixMailer
// A. Admin Lead Alert to Ezekiel (glorifixmediaconcept@gmail.com)
$admin_delivery = GlorifixMailer::sendAdminNotification($new_entry);

// B. Branded Personalized Confirmation to Customer's Email
$client_delivery = GlorifixMailer::sendCustomerConfirmation($new_entry);

// 3. Secondary relay to FormSubmit ONLY if native server mail failed
if (empty($admin_delivery['success']) && function_exists('curl_init')) {
    $fs_payload = json_encode([
        '_subject'         => ($is_booking ? "📅 [Booking Alert] " : "📩 [Inquiry Alert] ") . $name . " (" . $service . ")",
        '_replyto'         => $email,
        '_template'        => 'table',
        'Customer Name'    => $name,
        'Email Address'    => $email,
        'Phone Number'     => $phone ?: 'Not provided',
        'Service'          => $service,
        'Appointment Date' => $app_date ?: 'N/A',
        'Appointment Time' => $app_time ?: 'N/A',
        'Message'          => $notes ?: 'None',
        'Reference ID'     => $inquiry_id
    ]);

    $ch = curl_init('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fs_payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    @curl_exec($ch);
    curl_close($ch);
}

// 4. Return Comprehensive Success Response
http_response_code(200);
echo json_encode([
    'success'               => true,
    'id'                    => $inquiry_id,
    'type'                  => $inq_type,
    'client_name'           => $name,
    'client_email'          => $email,
    'admin_notified'        => $admin_delivery['success'] ?? false,
    'client_confirmed'      => $client_delivery['success'] ?? false,
    'message'               => $is_booking
        ? "Your consultation has been booked successfully! A customized confirmation email has been sent to {$email}."
        : "Thank you! Your inquiry has been submitted. A customized confirmation email has been sent to {$email}."
]);
