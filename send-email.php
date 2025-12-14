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

$html = "
<h2>טופס בריאות חדש</h2>
<p><b>שם:</b> {$data['client_name']}</p>
<p><b>אימייל:</b> {$data['client_email']}</p>
<p><b>טלפון:</b> {$data['client_phone']}</p>
";

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
