<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
  echo json_encode(["success" => false, "message" => "No data"]);
  exit;
}

$apiKey = getenv('RESEND_API_KEY');
$to = getenv('BUSINESS_EMAIL');
$html = '
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>טופס בריאות חדש</title>
</head>

<body style="margin:0; padding:0; background-color:#f6f6f6; direction:rtl;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f6f6;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background:#ffffff; margin:20px auto; border-radius:12px; overflow:hidden; font-family:Arial, sans-serif;">
          
          <!-- Header -->
          <tr>
            <td style="background:#fdecef; padding:20px; text-align:center;">
              <img src="https://skincare-frontend-9nwm.onrender.com/Web_Photo_Editor.jpg"
                   alt="Skincare Salon"
                   style="max-width:120px; height:auto; margin-bottom:10px;">
              <h1 style="margin:0; color:#d81b60;">טופס בריאות חדש</h1>
              <p style="margin:5px 0 0; color:#555;">התקבל טופס חדש מלקוחה</p>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:20px; text-align:right;">

              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                  <td style="padding:10px; font-weight:bold; border-bottom:1px solid #eee;">שם</td>
                  <td style="padding:10px; border-bottom:1px solid #eee;">'.$data['client_name'].'</td>
                </tr>
                <tr>
                  <td style="padding:10px; font-weight:bold; border-bottom:1px solid #eee;">אימייל</td>
                  <td style="padding:10px; border-bottom:1px solid #eee;">'.$data['client_email'].'</td>
                </tr>
                <tr>
                  <td style="padding:10px; font-weight:bold;">טלפון</td>
                  <td style="padding:10px;">'.$data['client_phone'].'</td>
                </tr>
              </table>

              <p style="margin-top:20px; color:#777; font-size:14px;">
                📎 קובץ PDF מצורף למייל זה
              </p>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#fafafa; padding:15px; text-align:center; font-size:12px; color:#999;">
              הטופס נשלח אוטומטית ממערכת Skincare
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>
</body>
</html>
';



$payload = [
  "from" => "טופס בריאות <henborochov2@gmail.com>",
  "to" => [$to],
  "reply_to" => $data['client_email'],
  "subject" => "טופס בריאות חדש – {$data['client_name']}",
  "html" => $html,
  "attachments" => [
    [
      "filename" => "health-form.pdf",
      "content" => $data['pdf_attachment']
    ]
  ]
];

$ch = curl_init("https://api.resend.com/emails");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "message" => $response]);
}
