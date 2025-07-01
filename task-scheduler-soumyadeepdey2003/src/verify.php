<?php
require_once __DIR__ . '/functions.php';
$message = '';
$email = '';
$code = '';
if (isset($_GET['email']) && isset($_GET['code'])) {
    $email = $_GET['email'];
    $code = $_GET['code'];
    // Sanitize email
    $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower(trim($email)) : '';
    if ($email && $code && verifySubscription($email, $code)) {
        $message = 'Your email has been verified and you are now subscribed!';
    } else {
        $message = 'Verification failed. Invalid or expired code.';
    }
} else {
    $message = 'Invalid verification link.';
}
// Debug log for verification process
file_put_contents(__DIR__ . '/debug_verify.log', date('c') . "\n" . print_r([
    'email' => $email,
    'code' => $code,
    'pending' => file_exists(PENDING_FILE) ? file_get_contents(PENDING_FILE) : 'no file',
    'subscribers' => file_exists(SUBSCRIBERS_FILE) ? file_get_contents(SUBSCRIBERS_FILE) : 'no file',
    'message' => $message
], true), FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Email Verification</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .message { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1 id="verification-heading">Subscription Verification</h1>
    <div class="<?php echo ($message === 'Your email has been verified and you are now subscribed!') ? 'message' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <a href="index.php">Back to Task Scheduler</a>
</body>
</html>