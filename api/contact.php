<?php
/**
 * Glorifix Media Concept Limited — Contact & Booking API
 * Handles customer consultation bookings and direct inquiries.
 * - Saves records to admin/data/inquiries.json
 * - Sends email notification to Ezekiel / Admin
 * - Sends automated branded confirmation email to the Customer
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Support JSON payload or Form POST
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);
$data = is_array($json_data) ? $json_data : $_POST;

// Extract Fields
$name = trim($data['Full_Name'] ?? $data['Sender_Name'] ?? $data['name'] ?? '');
$email = trim($data['Email_Address'] ?? $data['email'] ?? '');
$phone = trim($data['Phone_Number'] ?? $data['phone'] ?? '');
$service = trim($data['Service_Requested'] ?? $data['Subject'] ?? $data['service'] ?? 'General Consultation');
$notes = trim($data['Project_Notes'] ?? $data['Message'] ?? $data['notes'] ?? $data['message'] ?? '');
$app_date = trim($data['Appointment_Date'] ?? $data['date'] ?? '');
$app_time = trim($data['Appointment_Time'] ?? $data['time'] ?? '');

$is_booking = !empty($app_date) || isset($data['Appointment_Date']) || stripos($service, 'booking') !== false;
$type = $is_booking ? 'Consultation Booking' : 'Direct Inquiry';

// Validation
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide your full name.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Please provide a valid email address.']);
    exit;
}

// Load config for admin notification email
$config_file = __DIR__ . '/../admin/data/config.json';
$admin_email = 'glorifixmediaconcept@gmail.com';
if (file_exists($config_file)) {
    $conf = json_decode(file_get_contents($config_file), true);
    if (!empty($conf['notification_email'])) {
        $admin_email = $conf['notification_email'];
    }
}

// 1. Save to inquiries.json
$inquiries_file = __DIR__ . '/../admin/data/inquiries.json';
$inquiries = [];
if (file_exists($inquiries_file)) {
    $inquiries = json_decode(file_get_contents($inquiries_file), true) ?: [];
}

$inquiry_id = 'INQ-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
$new_entry = [
    'id' => $inquiry_id,
    'timestamp' => date('Y-m-d H:i:s'),
    'type' => $type,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'service' => $service,
    'appointment_date' => $app_date,
    'appointment_time' => $app_time,
    'notes' => $notes,
    'status' => 'new',
    'read' => false
];

array_unshift($inquiries, $new_entry);
file_put_contents($inquiries_file, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

// 2. Prepare HTML Email for Ezekiel (Admin Notification)
$admin_subject = ($is_booking ? "📅 New Consultation Booking: " : "📩 New Website Inquiry: ") . $name;
$admin_body = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #0F0204; color: #F8EDD8; margin: 0; padding: 24px; }
    .card { background: #1F0508; border: 1px solid #7B1324; border-radius: 12px; max-width: 600px; margin: 0 auto; overflow: hidden; }
    .header { background: linear-gradient(135deg, #7B1324 0%, #D4A017 100%); padding: 20px 24px; color: #FFFFFF; }
    .header h2 { margin: 0 0 6px 0; font-size: 22px; }
    .header p { margin: 0; opacity: 0.9; font-size: 14px; }
    .body { padding: 24px; }
    .row { margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 10px; }
    .label { color: #E8B923; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .val { color: #FFFFFF; font-size: 15px; font-weight: 500; }
    .notes-box { background: rgba(0,0,0,0.3); border-left: 3px solid #E8B923; padding: 12px 16px; border-radius: 4px; font-style: italic; color: #F0D5BE; }
    .footer { padding: 16px 24px; background: #140305; text-align: center; font-size: 13px; color: #A0727B; }
    .btn { display: inline-block; background: #25D366; color: #FFFFFF; text-decoration: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; font-size: 14px; margin-top: 10px; }
  </style>
</head>
<body>
  <div class='card'>
    <div class='header'>
      <h2>" . htmlspecialchars($type) . "</h2>
      <p>Reference: #" . htmlspecialchars($inquiry_id) . " &bull; " . date('M j, Y - g:i A') . "</p>
    </div>
    <div class='body'>
      <div class='row'>
        <div class='label'>Customer Name</div>
        <div class='val'>" . htmlspecialchars($name) . "</div>
      </div>
      <div class='row'>
        <div class='label'>Email Address</div>
        <div class='val'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #E8B923;'>" . htmlspecialchars($email) . "</a></div>
      </div>
      <div class='row'>
        <div class='label'>Phone / WhatsApp Number</div>
        <div class='val'>" . htmlspecialchars($phone ?: 'Not provided') . "</div>
      </div>
      <div class='row'>
        <div class='label'>Service Requested</div>
        <div class='val'>" . htmlspecialchars($service) . "</div>
      </div>";

if (!empty($app_date)) {
    $admin_body .= "
      <div class='row'>
        <div class='label'>Appointment Schedule</div>
        <div class='val'>" . htmlspecialchars($app_date) . " at " . htmlspecialchars($app_time ?: '10:00 AM') . "</div>
      </div>";
}

if (!empty($notes)) {
    $admin_body .= "
      <div class='row' style='border-bottom: none;'>
        <div class='label'>Project Goals / Message</div>
        <div class='notes-box'>" . nl2br(htmlspecialchars($notes)) . "</div>
      </div>";
}

$clean_phone = preg_replace('/[^0-9]/', '', $phone);
if (!empty($clean_phone)) {
    $admin_body .= "
      <div style='text-align: center; margin-top: 18px;'>
        <a href='https://wa.me/" . htmlspecialchars($clean_phone) . "' class='btn' target='_blank'>Chat with " . htmlspecialchars($name) . " on WhatsApp</a>
      </div>";
}

$admin_body .= "
    </div>
    <div class='footer'>
      Logged in your Glorifix Admin Dashboard &bull; Opp. General Hospital, Ikare-Akoko
    </div>
  </div>
</body>
</html>";

// 3. Prepare Branded Confirmation Email for Customer
$customer_subject = $is_booking 
    ? "Booking Confirmed: Your Consultation with Glorifix Media Concept Limited"
    : "Thank You for Contacting Glorifix Media Concept Limited";

$customer_body = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #F8F4EE; color: #2E1015; margin: 0; padding: 24px; }
    .card { background: #FFFFFF; border: 1px solid #EAD8C7; border-radius: 12px; max-width: 600px; margin: 0 auto; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
    .header { background: linear-gradient(135deg, #7B1324 0%, #1F0508 100%); padding: 28px 24px; text-align: center; color: #FFFFFF; }
    .header h1 { margin: 0 0 6px 0; font-size: 24px; letter-spacing: -0.5px; }
    .header p { margin: 0; color: #E8B923; font-size: 14px; font-weight: 500; }
    .body { padding: 28px 24px; line-height: 1.6; }
    .greeting { font-size: 17px; font-weight: 600; color: #1F0508; margin-bottom: 12px; }
    .summary-box { background: #FFF9F2; border: 1px solid #F0DBC4; border-radius: 8px; padding: 18px; margin: 20px 0; }
    .summary-item { margin-bottom: 10px; font-size: 14px; }
    .summary-item:last-child { margin-bottom: 0; }
    .summary-item strong { color: #7B1324; }
    .cta-box { text-align: center; margin: 26px 0 10px 0; }
    .btn { display: inline-block; background: #7B1324; color: #FFFFFF; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 15px; }
    .wa-btn { display: inline-block; background: #25D366; color: #FFFFFF; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; font-size: 15px; margin-top: 10px; }
    .footer { padding: 20px 24px; background: #F3EAE1; text-align: center; font-size: 12px; color: #78525A; line-height: 1.5; }
  </style>
</head>
<body>
  <div class='card'>
    <div class='header'>
      <h1>Glorifix Media Concept Limited</h1>
      <p>Premium Digital Agency &bull; CAC Legal Formation &bull; Web &amp; App Engineering</p>
    </div>
    <div class='body'>
      <div class='greeting'>Dear " . htmlspecialchars($name) . ",</div>
      <p>Thank you for reaching out to Glorifix Media Concept Limited! We have received your " . ($is_booking ? "consultation booking request" : "message") . " (Reference: <strong>#" . htmlspecialchars($inquiry_id) . "</strong>).</p>
      
      <div class='summary-box'>
        <div class='summary-item'><strong>Service Requested:</strong> " . htmlspecialchars($service) . "</div>";

if (!empty($app_date)) {
    $customer_body .= "
        <div class='summary-item'><strong>Scheduled Date & Time:</strong> " . htmlspecialchars($app_date) . " at " . htmlspecialchars($app_time ?: '10:00 AM') . "</div>";
}

$customer_body .= "
        <div class='summary-item'><strong>Your Contact:</strong> " . htmlspecialchars($phone ?: $email) . "</div>
        <div class='summary-item'><strong>Assigned Specialist:</strong> Ezekiel &amp; Strategic Advisory Team</div>
      </div>

      <p><strong>What happens next?</strong><br>
      Our lead consultant will review your project requirements and connect with you directly via phone or WhatsApp within <strong>24 business hours</strong> to finalize your project roadmap.</p>

      <p>If you have an urgent inquiry or require immediate assistance, feel free to chat with us directly on WhatsApp:</p>

      <div class='cta-box'>
        <a href='https://wa.me/2349050278999?text=Hello%20Ezekiel!%20I%20just%20submitted%20a%20request%20(Ref:%20" . urlencode($inquiry_id) . ")' class='wa-btn' target='_blank'>💬 Chat Directly on WhatsApp (09050278999)</a>
      </div>
    </div>
    <div class='footer'>
      <strong>Glorifix Media Concept Limited</strong><br>
      Opposite General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State<br>
      Abuja Client Operations &bull; RC Certified<br>
      Email: glorifixmediaconcept@gmail.com &bull; Phone: +234 905 027 8999
    </div>
  </div>
</body>
</html>";

// Headers
$headers_common = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
$server_host = $_SERVER['HTTP_HOST'] ?? 'glorifixmediaconcept.com';
$from_email = 'noreply@' . preg_replace('/^www\./', '', $server_host);

// Send Admin Email
$headers_admin = $headers_common . "From: Glorifix Website <{$from_email}>\r\nReply-To: {$name} <{$email}>\r\n";
@mail($admin_email, $admin_subject, $admin_body, $headers_admin);

// Send Customer Email
$headers_customer = $headers_common . "From: Glorifix Media Concept <{$from_email}>\r\nReply-To: Glorifix Support <{$admin_email}>\r\n";
@mail($email, $customer_subject, $customer_body, $headers_customer);

// Redundant Direct Gmail Delivery via FormSubmit
if (function_exists('curl_init')) {
    $fs_payload = json_encode([
        '_subject' => $admin_subject,
        '_replyto' => $email,
        '_template' => 'table',
        'Customer Name' => $name,
        'Email Address' => $email,
        'Phone / WhatsApp' => $phone ?: 'Not provided',
        'Service Requested' => $service,
        'Appointment Date' => $app_date ?: 'N/A',
        'Appointment Time' => $app_time ?: 'N/A',
        'Message / Notes' => $notes ?: 'None',
        'Reference ID' => $inquiry_id
    ]);

    $ch = curl_init('https://formsubmit.co/ajax/glorifixmediaconcept@gmail.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fs_payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: https://excellentgoldtech2021-art.github.io',
        'Referer: https://excellentgoldtech2021-art.github.io/glorifix-media-concept/contact.html'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    @curl_exec($ch);
    curl_close($ch);
}

// Successful JSON response
echo json_encode([
    'success' => true,
    'message' => $is_booking 
        ? 'Your consultation has been booked successfully! A confirmation email has been sent to your inbox.' 
        : 'Thank you! Your message has been sent successfully. A confirmation email has been sent to your inbox.',
    'id' => $inquiry_id,
    'email_sent_to' => $email
]);
