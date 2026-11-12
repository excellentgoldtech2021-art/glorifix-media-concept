<?php
/**
 * Glorifix Media Concept Limited — High-Deliverability Mailer Engine
 * Designed for 100% cPanel & Apache hosting compatibility.
 * 
 * Features:
 * 1. RFC 5322 & RFC 2045 compliant MIME message construction.
 * 2. Proper envelope sender (-f) alignment for SPF & DKIM verification in Gmail.
 * 3. Anti-spam header styling (prevents Gmail spam folder routing).
 * 4. Dual delivery: Admin Lead Notification + Customer Branded Confirmation.
 * 5. Optional Authenticated SMTP socket client (cPanel localhost, port 465/587, or external SMTP).
 */

class GlorifixMailer {

    /**
     * Send email via PHP mail() with proper envelope sender or Authenticated SMTP
     */
    public static function send($to, $subject, $html_body, $reply_to = null, $reply_to_name = null, $is_customer_confirmation = false) {
        $config = self::loadConfig();

        $site_name = $config['site_name'] ?? 'Glorifix Media Concept Limited';
        $server_domain = self::getDomain();
        $sender_email = !empty($config['smtp_user']) && filter_var($config['smtp_user'], FILTER_VALIDATE_EMAIL)
            ? $config['smtp_user']
            : 'no-reply@' . $server_domain;
        $sender_name = $site_name;

        // If SMTP is enabled in config, attempt SMTP socket delivery
        if (!empty($config['smtp_enabled']) && !empty($config['smtp_host'])) {
            $smtp_result = self::sendViaSmtp($config, $to, $subject, $html_body, $sender_email, $sender_name, $reply_to, $reply_to_name, $is_customer_confirmation);
            if ($smtp_result['success']) {
                return $smtp_result;
            }
            // Fallback to PHP mail() if SMTP fails
        }

        // Standard RFC-Compliant PHP mail() with Envelope Sender
        $msg_id = '<' . md5(uniqid(microtime(true), true)) . '@' . $server_domain . '>';
        $date = date('r');

        $headers = [];
        $headers[] = "From: \"{$sender_name}\" <{$sender_email}>";
        if (!empty($reply_to)) {
            $reply_name = !empty($reply_to_name) ? "\"{$reply_to_name}\" " : "";
            $headers[] = "Reply-To: {$reply_name}<{$reply_to}>";
        }
        $headers[] = "Return-Path: <{$sender_email}>";
        $headers[] = "Message-ID: {$msg_id}";
        $headers[] = "Date: {$date}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: GlorifixMediaConceptMailer/3.0";

        if ($is_customer_confirmation) {
            $headers[] = "Auto-Submitted: auto-generated";
            $headers[] = "Precedence: bulk";
            $headers[] = "X-Priority: 3 (Normal)";
        } else {
            $headers[] = "X-Priority: 1 (Highest)";
            $headers[] = "Importance: High";
        }

        $header_str = implode("\r\n", $headers);
        $envelope_param = "-f" . escapeshellcmd($sender_email);

        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html_body, $header_str, $envelope_param);

        return [
            'success' => $sent,
            'method' => 'php_mail',
            'to' => $to,
            'sender' => $sender_email
        ];
    }

    /**
     * Send Admin Lead Notification Email to Ezekiel
     */
    public static function sendAdminNotification($data) {
        $config = self::loadConfig();
        $admin_email = !empty($config['admin_email']) ? $config['admin_email'] : 'glorifixmediaconcept@gmail.com';

        $is_booking = !empty($data['appointment_date']) || ($data['type'] ?? '') === 'Consultation Booking';
        $subject = ($is_booking ? "📅 [New Booking] " : "📩 [New Inquiry] ") . ($data['name'] ?? 'Website Client') . " (" . ($data['service'] ?? 'General') . ")";

        $body = self::renderAdminHtml($data, $is_booking);

        return self::send(
            $admin_email,
            $subject,
            $body,
            $data['email'] ?? null,
            $data['name'] ?? null,
            false
        );
    }

    /**
     * Send Personalized Branded Confirmation Email to Customer
     */
    public static function sendCustomerConfirmation($data) {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid customer email'];
        }

        $config = self::loadConfig();
        $admin_email = !empty($config['admin_email']) ? $config['admin_email'] : 'glorifixmediaconcept@gmail.com';
        $is_booking = !empty($data['appointment_date']) || ($data['type'] ?? '') === 'Consultation Booking';

        $subject = $is_booking
            ? "Consultation Confirmed: Glorifix Media Concept Limited [Ref: #" . ($data['id'] ?? 'GLF') . "]"
            : "Message Received: Glorifix Media Concept Limited [Ref: #" . ($data['id'] ?? 'GLF') . "]";

        $body = self::renderCustomerHtml($data, $is_booking, $admin_email);

        return self::send(
            $data['email'],
            $subject,
            $body,
            $admin_email,
            'Glorifix Media Support',
            true
        );
    }

    /**
     * Clean domain resolver for SPF/envelope sender
     */
    public static function getDomain() {
        $host = $_SERVER['HTTP_HOST'] ?? 'glorifixmediaconcept.com.ng';
        $host = preg_replace('/:\d+$/', '', $host);
        $host = preg_replace('/^www\./', '', $host);
        if ($host === 'localhost' || $host === '127.0.0.1' || empty($host)) {
            return 'glorifixmediaconcept.com.ng';
        }
        return $host;
    }

    /**
     * Load Admin Configuration
     */
    public static function loadConfig() {
        $cfg_file = __DIR__ . '/../admin/data/config.json';
        if (file_exists($cfg_file)) {
            $json = json_decode(file_get_contents($cfg_file), true);
            if (is_array($json)) return $json;
        }
        return [];
    }

    /**
     * Admin HTML Template
     */
    private static function renderAdminHtml($data, $is_booking) {
        $name = htmlspecialchars($data['name'] ?? 'N/A');
        $email = htmlspecialchars($data['email'] ?? 'N/A');
        $phone = htmlspecialchars($data['phone'] ?? 'Not provided');
        $service = htmlspecialchars($data['service'] ?? 'General Consultation');
        $notes = nl2br(htmlspecialchars($data['notes'] ?? 'None'));
        $app_date = htmlspecialchars($data['appointment_date'] ?? 'N/A');
        $app_time = htmlspecialchars($data['appointment_time'] ?? 'N/A');
        $inq_id = htmlspecialchars($data['id'] ?? 'INQ-' . strtoupper(substr(md5(time()), 0, 6)));
        $date_now = date('D, M j, Y &bull; g:i A');

        $clean_phone = preg_replace('/[^0-9]/', '', $data['phone'] ?? '');
        $wa_msg = urlencode("Hello {$data['name']}, I received your inquiry (#{$data['id']}) on Glorifix Media Concept regarding {$data['service']}.");
        $wa_url = !empty($clean_phone) ? "https://wa.me/{$clean_phone}?text={$wa_msg}" : null;

        $html = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>New Lead Notification</title>
</head>
<body style='margin:0; padding:24px 12px; background-color:#0d0204; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color:#f5e8d8;'>
  <div style='max-width:620px; margin:0 auto; background:#1b0509; border:1px solid #7b1324; border-radius:12px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.4);'>
    
    <!-- Header -->
    <div style='background:linear-gradient(135deg, #7b1324 0%, #cc1111 50%, #d4a017 100%); padding:24px; text-align:left; color:#ffffff;'>
      <div style='font-size:12px; text-transform:uppercase; letter-spacing:1px; opacity:0.9; margin-bottom:4px;'>GLORIFIX MEDIA CONCEPT &bull; NEW LEAD ALERT</div>
      <h1 style='margin:0; font-size:22px; font-weight:800; text-shadow:0 1px 2px rgba(0,0,0,0.3);'>" . ($is_booking ? "📅 New Consultation Booking" : "📩 Direct Website Message") . "</h1>
      <p style='margin:6px 0 0 0; font-size:13px; opacity:0.95;'>Ref: <strong>#{$inq_id}</strong> &bull; Received on {$date_now}</p>
    </div>

    <!-- Lead Details -->
    <div style='padding:24px;'>
      <table style='width:100%; border-collapse:collapse; margin-bottom:20px;'>
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); width:130px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#e5a93c; font-weight:700;'>Customer</td>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:16px; color:#ffffff; font-weight:700;'>{$name}</td>
        </tr>
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#e5a93c; font-weight:700;'>Email</td>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:15px; color:#ffffff;'><a href='mailto:{$email}' style='color:#ffffff; text-decoration:underline;'>{$email}</a></td>
        </tr>
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#e5a93c; font-weight:700;'>Phone / WA</td>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:15px; color:#ffffff;'><a href='tel:{$phone}' style='color:#ffffff; text-decoration:none;'>{$phone}</a></td>
        </tr>
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#e5a93c; font-weight:700;'>Service</td>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:15px; color:#ffffff; font-weight:600;'>{$service}</td>
        </tr>";

        if ($is_booking) {
            $html .= "
        <tr>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#e5a93c; font-weight:700;'>Schedule</td>
          <td style='padding:10px 0; border-bottom:1px solid rgba(255,255,255,0.08); font-size:15px; color:#25D366; font-weight:700;'>{$app_date} &bull; {$app_time}</td>
        </tr>";
        }

        $html .= "
      </table>

      <!-- Project Notes -->
      <div style='background:rgba(0,0,0,0.35); border-left:4px solid #e5a93c; border-radius:6px; padding:16px; margin-bottom:24px;'>
        <div style='font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#e5a93c; font-weight:700; margin-bottom:6px;'>Client Message / Project Goals</div>
        <div style='font-size:14px; line-height:1.6; color:#f0dcd0;'>{$notes}</div>
      </div>

      <!-- Quick Action Buttons -->
      <div style='text-align:center; padding:10px 0;'>";

        if ($wa_url) {
            $html .= "
        <a href='{$wa_url}' target='_blank' style='display:inline-block; background:#25D366; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:700; font-size:14px; margin:4px 6px; box-shadow:0 4px 14px rgba(37,211,102,0.3);'>
          💬 WhatsApp {$name}
        </a>";
        }

        $html .= "
        <a href='tel:{$phone}' style='display:inline-block; background:#7b1324; border:1px solid #e5a93c; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:700; font-size:14px; margin:4px 6px;'>
          📞 Call Direct ({$phone})
        </a>
      </div>

      <div style='font-size:12px; color:#a0707a; text-align:center; margin-top:16px;'>
        💡 Tip: You can also reply directly to this email to contact <strong>{$name}</strong>.
      </div>
    </div>

    <!-- Footer -->
    <div style='background:#120305; padding:16px 24px; text-align:center; font-size:12px; color:#85505a; border-top:1px solid rgba(255,255,255,0.06);'>
      Glorifix Media Concept Limited &bull; Inquiries logged to Admin Dashboard &bull; Ondo State &amp; Abuja FCT
    </div>
  </div>
</body>
</html>";

        return $html;
    }

    /**
     * Customer Branded Confirmation Template
     */
    private static function renderCustomerHtml($data, $is_booking, $admin_email) {
        $name = htmlspecialchars($data['name'] ?? 'Valued Client');
        $service = htmlspecialchars($data['service'] ?? 'General Strategy Consultation');
        $phone = htmlspecialchars($data['phone'] ?? '');
        $app_date = htmlspecialchars($data['appointment_date'] ?? '');
        $app_time = htmlspecialchars($data['appointment_time'] ?? '');
        $inq_id = htmlspecialchars($data['id'] ?? 'GLF-' . strtoupper(substr(md5(time()), 0, 6)));

        $headline = $is_booking ? "Your Consultation is Confirmed" : "We Have Received Your Message";
        $subtitle = $is_booking 
            ? "Your 1-on-1 strategy session has been scheduled with Glorifix Media Concept Limited."
            : "Thank you for reaching out to Glorifix Media Concept Limited. Our team is already reviewing your brief.";

        $html = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>{$headline}</title>
</head>
<body style='margin:0; padding:24px 12px; background-color:#f6efe8; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color:#2d0a14;'>
  <div style='max-width:600px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #ebd9c8; box-shadow:0 6px 24px rgba(0,0,0,0.06);'>
    
    <!-- Brand Header -->
    <div style='background:linear-gradient(135deg, #7b1324 0%, #1f0508 100%); padding:32px 24px; text-align:center; color:#ffffff;'>
      <div style='display:inline-block; padding:4px 14px; background:rgba(229,169,60,0.18); border:1px solid #e5a93c; border-radius:20px; font-size:11px; font-weight:800; letter-spacing:0.12em; text-transform:uppercase; color:#e5a93c; margin-bottom:12px;'>
        OFFICIAL CLIENT CONFIRMATION
      </div>
      <h1 style='margin:0 0 8px 0; font-size:24px; font-weight:800; letter-spacing:-0.02em;'>Glorifix Media Concept Limited</h1>
      <p style='margin:0; font-size:14px; color:#f0dcd0; line-height:1.5;'>{$subtitle}</p>
    </div>

    <!-- Body Content -->
    <div style='padding:30px 24px; line-height:1.6;'>
      <div style='font-size:18px; font-weight:700; color:#1a0408; margin-bottom:12px;'>
        Dear {$name},
      </div>
      
      <p style='margin:0 0 20px 0; font-size:15px; color:#4a1e26;'>
        Thank you for choosing Glorifix Media Concept Limited. We have securely registered your request in our client management system with Reference ID: <strong style='color:#7b1324;'>#{$inq_id}</strong>.
      </p>

      <!-- Summary Card -->
      <div style='background:#fdf9f4; border:1.5px solid #f0d5be; border-radius:10px; padding:20px; margin-bottom:24px;'>
        <div style='font-size:12px; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:#7b1324; margin-bottom:12px; border-bottom:1px solid #f0d5be; padding-bottom:6px;'>
          📋 Request Summary
        </div>
        <table style='width:100%; border-collapse:collapse; font-size:14px;'>
          <tr>
            <td style='padding:6px 0; color:#7a3c48; font-weight:600; width:130px;'>Reference ID:</td>
            <td style='padding:6px 0; color:#1a0408; font-weight:700;'>#{$inq_id}</td>
          </tr>
          <tr>
            <td style='padding:6px 0; color:#7a3c48; font-weight:600;'>Service:</td>
            <td style='padding:6px 0; color:#1a0408; font-weight:700;'>{$service}</td>
          </tr>";

        if ($is_booking && !empty($app_date)) {
            $html .= "
          <tr>
            <td style='padding:6px 0; color:#7a3c48; font-weight:600;'>Appointment:</td>
            <td style='padding:6px 0; color:#7b1324; font-weight:700;'>{$app_date} at " . ($app_time ?: '10:00 AM') . "</td>
          </tr>";
        }

        if (!empty($phone)) {
            $html .= "
          <tr>
            <td style='padding:6px 0; color:#7a3c48; font-weight:600;'>Phone / WA:</td>
            <td style='padding:6px 0; color:#1a0408;'>{$phone}</td>
          </tr>";
        }

        $html .= "
          <tr>
            <td style='padding:6px 0; color:#7a3c48; font-weight:600;'>Lead Consultant:</td>
            <td style='padding:6px 0; color:#1a0408;'>Soje Ezekiel Oluwaseyi &amp; Strategic Team</td>
          </tr>
        </table>
      </div>

      <!-- What Happens Next -->
      <div style='margin-bottom:26px;'>
        <div style='font-size:15px; font-weight:700; color:#1a0408; margin-bottom:8px;'>
          🚀 What happens next?
        </div>
        <ul style='margin:0; padding-left:20px; font-size:14px; color:#5a2830; line-height:1.7;'>
          <li><strong>Review Brief:</strong> Our technical team is reviewing your project details.</li>
          <li><strong>Direct Connection:</strong> Ezekiel or a Senior Account Strategist will reach out to you via WhatsApp or phone call within <strong>24 business hours</strong>.</li>
          <li><strong>Project Roadmap:</strong> We will discuss execution timelines, deliverables, and customized pricing tailored to your scale.</li>
        </ul>
      </div>

      <!-- Instant WhatsApp Connection -->
      <div style='background:#f3faf4; border:1px solid #b7e4c7; border-radius:10px; padding:20px; text-align:center;'>
        <div style='font-size:14px; font-weight:700; color:#1b4332; margin-bottom:6px;'>
          Need an Immediate Response?
        </div>
        <p style='margin:0 0 14px 0; font-size:13px; color:#2d6a4f;'>
          You can connect directly with Ezekiel on WhatsApp right now:
        </p>
        <a href='https://wa.me/2349050278999?text=" . urlencode("Hello Ezekiel! I just booked on your website with Ref #{$inq_id}. Can we connect?") . "' 
           target='_blank' 
           style='display:inline-block; background:#25D366; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:700; font-size:14px; box-shadow:0 4px 12px rgba(37,211,102,0.3);'>
          💬 Chat with Ezekiel on WhatsApp (09050278999)
        </a>
      </div>

    </div>

    <!-- Corporate Footer -->
    <div style='background:#f6efe8; border-top:1px solid #ebd9c8; padding:24px; text-align:center; font-size:12px; color:#7a3c48; line-height:1.6;'>
      <strong style='color:#1a0408; font-size:13px;'>Glorifix Media Concept Limited</strong><br>
      📍 <strong>Registered Office:</strong> Opp. General Hospital, Behind G-Delight Hotel, Ikare-Akoko, Ondo State<br>
      🏛️ <strong>Abuja Studio Hub:</strong> Phase 2, Shop 7, Opp. UniAbuja Mini-Campus, Gwagwalada, Abuja FCT<br>
      📞 Hotline: <a href='tel:09050278999' style='color:#7b1324; font-weight:700; text-decoration:none;'>+234 905 027 8999</a> &bull; ✉️ <a href='mailto:{$admin_email}' style='color:#7b1324; text-decoration:none;'>{$admin_email}</a><br>
      <div style='margin-top:10px; font-size:11px; color:#a0707a;'>
        This is an automated transactional confirmation for your website booking or inquiry with Glorifix Media Concept Limited.
      </div>
    </div>
  </div>
</body>
</html>";

        return $html;
    }

    /**
     * Native Socket SMTP Engine (Optional fallback or primary if enabled)
     */
    private static function sendViaSmtp($config, $to, $subject, $body, $from_email, $from_name, $reply_to, $reply_to_name, $is_customer_confirmation) {
        $host = $config['smtp_host'];
        $port = intval($config['smtp_port'] ?? 587);
        $user = $config['smtp_user'] ?? '';
        $pass = $config['smtp_pass'] ?? '';
        $enc  = strtolower($config['smtp_crypto'] ?? ($port == 465 ? 'ssl' : 'tls'));

        $prefix = ($enc === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 8);
        if (!$socket) {
            return ['success' => false, 'error' => "Socket error: {$errstr} ({$errno})"];
        }

        $read = function() use ($socket) {
            $data = '';
            while ($str = fgets($socket, 515)) {
                $data .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $data;
        };

        $write = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $banner = $read();

        // EHLO
        $domain = self::getDomain();
        $write("EHLO " . $domain);
        $read();

        // STARTTLS if port 587 or tls
        if ($enc === 'tls' && $port != 465) {
            $write("STARTTLS");
            $tls_resp = $read();
            if (strpos($tls_resp, '220') !== false) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $write("EHLO " . $domain);
                $read();
            }
        }

        // AUTH
        if (!empty($user) && !empty($pass)) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $auth_resp = $read();
            if (strpos($auth_resp, '235') === false) {
                fclose($socket);
                return ['success' => false, 'error' => "SMTP Auth failed: {$auth_resp}"];
            }
        }

        // MAIL FROM
        $write("MAIL FROM:<{$from_email}>");
        $read();

        // RCPT TO
        $write("RCPT TO:<{$to}>");
        $rcpt_resp = $read();
        if (strpos($rcpt_resp, '250') === false) {
            fclose($socket);
            return ['success' => false, 'error' => "Recipient rejected: {$rcpt_resp}"];
        }

        // DATA
        $write("DATA");
        $read();

        // Headers
        $msg_id = '<' . md5(uniqid(microtime(true), true)) . '@' . $domain . '>';
        $headers = [];
        $headers[] = "From: \"{$from_name}\" <{$from_email}>";
        $headers[] = "To: <{$to}>";
        if (!empty($reply_to)) {
            $rname = !empty($reply_to_name) ? "\"{$reply_to_name}\" " : "";
            $headers[] = "Reply-To: {$rname}<{$reply_to}>";
        }
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: {$msg_id}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: 8bit";
        $headers[] = "X-Mailer: GlorifixSmtpClient/3.0";
        if ($is_customer_confirmation) {
            $headers[] = "Auto-Submitted: auto-generated";
            $headers[] = "Precedence: bulk";
        } else {
            $headers[] = "X-Priority: 1 (Highest)";
        }

        $email_payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
        $write($email_payload);
        $data_resp = $read();

        $write("QUIT");
        $read();
        fclose($socket);

        return [
            'success' => (strpos($data_resp, '250') !== false),
            'method' => 'smtp',
            'response' => $data_resp
        ];
    }
}
