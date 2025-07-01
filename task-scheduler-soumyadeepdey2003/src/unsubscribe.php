<?php
require_once __DIR__ . '/functions.php';
$message = '';
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    if (unsubscribeEmail($email)) {
        $message = 'You have been unsubscribed from task updates.';
    } else {
        $message = 'Unsubscription failed. Email not found.';
    }
} else {
    $message = 'Invalid unsubscription link.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Unsubscribe</title>
    <meta charset="UTF-8">
    <style>
        .message { color: green; margin-bottom: 15px; }
        .error { color: red; margin-bottom: 15px; }
        body { font-family: Arial, sans-serif; margin: 40px; }
    </style>
</head>
<body>
    <h1 id="unsubscription-heading">Unsubscribe from Task Updates</h1>
    <div class="<?php echo ($message === 'You have been unsubscribed from task updates.') ? 'message' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <a href="index.php">Back to Task Scheduler</a>
</body>
</html>
