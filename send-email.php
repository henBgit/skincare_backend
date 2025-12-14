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
</head>
<body style="
  margin: 0;
  padding: 0;
  direction: rtl;
  text-align: right;
  font-family: Arial, sans-serif;
  background-color: #f6f6f6;
">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f6f6; padding: 20px;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table width="600" cellpadding="0" cellspacing="0" style="
          background-color: #ffffff;
          border-radius: 8px;
          overflow: hidden;
          box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        ">

          <!-- Header / Logo -->
          <tr>
            <td style="padding: 20px; text-align: right; background-color: #fff0f5;">
              <img 
                src="https://dana-cosmetic.onrender.com/Web_Photo_Editor.jpg"
                alt="SKINCARE"
                style="max-height: 100px;"
              />
            </td>
          </tr>

          <!-- Title -->
          <tr>
            <td style="padding: 20px;">
              <h2 style="margin: 0; color: #db3c78;">
                טופס בריאות חדש
              </h2>
              <p style="margin: 8px 0 0; color: #666;">
                התקבל טופס חדש מלקוחה
              </p>
            </td>
          </tr>

          <!-- Divider -->
          <tr>
            <td style="padding: 0 20px;">
              <hr style="border: none; border-top: 1px solid #eee;">
            </td>
          </tr>

          <!-- Details Table -->
          <tr>
            <td style="padding: 20px;">
              <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
                <tr style="background-color: #fafafa;">
                  <td style="font-weight: bold; width: 35%;">שם</td>
                  <td>'.$data['client_name'].'</td>
                </tr>
                <tr>
                  <td style="font-weight: bold;">אימייל</td>
                  <td>
                    <a href="mailto:'.$data['client_email'].'" style="color:#db3c78; text-decoration:none;">
                      '.$data['client_email'].'
                    </a>
                  </td>
                </tr>
                <tr style="background-color: #fafafa;">
                  <td style="font-weight: bold;">טלפון</td>
                  <td>'.$data['client_phone'].'</td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="
              padding: 15px 20px;
              background-color: #f9f9f9;
              font-size: 12px;
              color: #999;
              text-align: center;
            ">
              הטופס נשלח אוטומטית ממערכת טפסי הבריאות
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
  "from" => "טופס בריאות <onboarding@resend.dev>",
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
