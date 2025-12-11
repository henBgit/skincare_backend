<?php
// הגדרות כותרת כדי לאפשר גישה מ-CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// אם זו בקשת OPTIONS (לצורך CORS), תחזיר תגובה ריקה
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// טעינת PHPMailer
require 'PHPMailer-6.9.1/src/Exception.php';
require 'PHPMailer-6.9.1/src/PHPMailer.php';
require 'PHPMailer-6.9.1/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// קבלת הנתונים מה-POST
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "לא התקבלו נתונים"]);
    exit();
}

// חילוץ הנתונים
$client_name = isset($data['client_name']) ? $data['client_name'] : '';
$client_email = isset($data['client_email']) ? $data['client_email'] : '';
$client_phone = isset($data['client_phone']) ? $data['client_phone'] : '';
$pdf_attachment = isset($data['pdf_attachment']) ? $data['pdf_attachment'] : '';

// יצירת תוכן המייל ב-HTML
$html_content = "
<html dir='rtl' lang='he'>
<head>
    <meta charset='UTF-8'>
</head>
<body style='font-family: Arial, sans-serif; direction: rtl; text-align: right;'>
    <h2 style='color: #333;'>טופס חדש נשלח</h2>
    <table style='border-collapse: collapse; width: 100%;'>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>שם:</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($client_name) . "</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>אימייל:</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($client_email) . "</td>
        </tr>
        <tr>
            <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>טלפון:</td>
            <td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($client_phone) . "</td>
        </tr>
    </table>
    <p style='margin-top: 20px;'>" . ($pdf_attachment ? 'קובץ PDF מצורף למייל זה.' : '') . "</p>
</body>
</html>";

// יצירת אובייקט PHPMailer
$mail = new PHPMailer(true);

 $__cfg = [];
 $__envUser = getenv('SMTP_USERNAME') ?: null;
 $__envPass = getenv('SMTP_PASSWORD') ?: null;
 $__envFrom = getenv('FROM_EMAIL') ?: null;
 $__envTo = getenv('TO_EMAIL') ?: null;
 if (file_exists(__DIR__ . '/config.local.php')) {
     $__tmp = include __DIR__ . '/config.local.php';
     if (is_array($__tmp)) { $__cfg = $__tmp; }
 }
try {
    // הגדרות שרת SMTP (Gmail)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $__envUser ?? ($__cfg['SMTP_USERNAME'] ?? '');
    $mail->Password   = $__envPass ?? ($__cfg['SMTP_PASSWORD'] ?? '');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // שולח ונמען
    $mail->setFrom($__envFrom ?? ($__cfg['FROM_EMAIL'] ?? ($__envUser ?? ($__cfg['SMTP_USERNAME'] ?? ''))), 'טופס אתר');
    $to_email = $__envTo ?? ($__cfg['TO_EMAIL'] ?? ($__envUser ?? ($__cfg['SMTP_USERNAME'] ?? '')));
    $mail->addAddress($to_email, $client_name);
    $mail->addReplyTo($client_email, $client_name);

    // תוכן המייל
    $mail->isHTML(true);
    $mail->Subject = "טופס חדש מאת " . $client_name;
    $mail->Body    = $html_content;

    // הוספת קובץ PDF אם יש
    if ($pdf_attachment) {
        $pdf_data = base64_decode(str_replace('data:application/pdf;base64,', '', $pdf_attachment));
        $mail->addStringAttachment($pdf_data, 'form-submission.pdf', 'base64', 'application/pdf');
    }

    // שליחת המייל
    $mail->send();
    echo json_encode(["success" => true, "message" => "המייל נשלח בהצלחה!"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "אירעה שגיאה בשליחת המייל: " . $mail->ErrorInfo]);
}
?>
