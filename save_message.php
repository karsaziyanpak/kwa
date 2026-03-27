<?php
/**
 * KWA Contact Form Handler
 * Receives JSON data from the contact form and saves it to a JSON file
 */

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (!$data || !isset($data['name']) || !isset($data['email']) || !isset($data['message']) || !isset($data['category'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate category
$validCategories = ['info', 'donation', 'volunteer', 'general', 'staff'];
$category = trim(strtolower($data['category']));
if (!in_array($category, $validCategories)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid category']);
    exit;
}

// Sanitize input
$name = trim(htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'));
$email = trim(filter_var($data['email'], FILTER_SANITIZE_EMAIL));
$phone = isset($data['phone']) ? trim(htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8')) : '';
$subject = isset($data['subject']) ? trim(htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8')) : '';
$message = trim(htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8'));
$timestamp = isset($data['timestamp']) ? $data['timestamp'] : date('c');

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate name and message lengths
if (strlen($name) < 2 || strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name must be between 2 and 100 characters']);
    exit;
}

if (strlen($message) < 5 || strlen($message) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message must be between 5 and 5000 characters']);
    exit;
}

// Create data directory if it doesn't exist
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create data directory']);
        exit;
    }
}

// Create the message entry
$messageEntry = [
    'id' => uniqid('msg_', true),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'category' => $category,
    'subject' => $subject,
    'message' => $message,
    'timestamp' => $timestamp,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'status' => 'new'
];

// Determine the JSON file path
$jsonFile = $dataDir . '/messages.json';

// Read existing messages
$messages = [];
if (file_exists($jsonFile)) {
    $existingData = file_get_contents($jsonFile);
    if ($existingData) {
        $messages = json_decode($existingData, true);
        if (!is_array($messages)) {
            $messages = [];
        }
    }
}

// Add new message
$messages[] = $messageEntry;

// Write updated messages back to file
if (file_put_contents($jsonFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save message']);
    exit;
}

// Optional: Send email notification to admin
$adminEmail = 'info@kwa.com.pk'; // Change this to the actual admin email
$subject_email = 'New Contact Form Submission from KWA Website - ' . ucfirst($category);
$emailBody = "
A new message has been received from the KWA website contact form.

Name: {$name}
Email: {$email}
Phone: " . ($phone ? $phone : 'Not provided') . "
Category: " . ucfirst($category) . "
Subject: " . ($subject ? $subject : 'Not provided') . "
Timestamp: {$timestamp}

Message:
{$message}

---
This is an automated message from the KWA website contact form.
To reply to this message, use the Admin Panel at: admin.php
";

$headers = "From: noreply@kwa.com.pk\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Attempt to send email (non-blocking)
@mail($adminEmail, $subject_email, $emailBody, $headers);

// Return success response
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Message saved successfully',
    'id' => $messageEntry['id']
]);
exit;
?>
