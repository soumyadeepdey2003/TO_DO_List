<?php
// Set Mailpit/MailHog SMTP for local development
ini_set('SMTP', '127.0.0.1');
ini_set('smtp_port', '1025');

// File path constants
const TASKS_FILE = __DIR__ . '/tasks.txt';
const SUBSCRIBERS_FILE = __DIR__ . '/subscribers.txt';
const PENDING_FILE = __DIR__ . '/pending_subscriptions.txt';

// Add a new task to the list
function addTask(string $task_name): bool {
    $file = TASKS_FILE;
    $task_name = trim($task_name);
    if ($task_name === '') { return false; }
    $tasks = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $tasks = json_decode($json, true) ?: [];
        foreach ($tasks as $task) {
            if (strcasecmp($task['name'], $task_name) === 0) {
                return false; // Prevent duplicate task names
            }
        }
    }
    $task = [
        'id' => uniqid('task_', true),
        'name' => $task_name,
        'completed' => false
    ];
    $tasks[] = $task;
    return file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

// Get all tasks from tasks.txt
function getAllTasks(): array {
    $file = TASKS_FILE;
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $tasks = json_decode($json, true);
    return is_array($tasks) ? $tasks : [];
}

// Mark/unmark a task as complete
function markTaskAsCompleted(string $task_id, bool $is_completed): bool {
    $file = TASKS_FILE;
    if (!file_exists($file)) {
        return false;
    }
    $json = file_get_contents($file);
    $tasks = json_decode($json, true) ?: [];
    $updated = false;
    foreach ($tasks as &$task) {
        if ($task['id'] === $task_id) {
            $task['completed'] = (bool)$is_completed;
            $updated = true;
            break;
        }
    }
    unset($task); // break reference
    if ($updated) {
        file_put_contents($file, json_encode($tasks, JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $updated;
}

// Delete a task from the list
function deleteTask(string $task_id): bool {
    $file = TASKS_FILE;
    if (!file_exists($file)) {
        return false;
    }
    $json = file_get_contents($file);
    $tasks = json_decode($json, true) ?: [];
    $new_tasks = [];
    $deleted = false;
    foreach ($tasks as $task) {
        if ($task['id'] === $task_id) {
            $deleted = true;
            continue;
        }
        $new_tasks[] = $task;
    }
    if ($deleted) {
        file_put_contents($file, json_encode($new_tasks, JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $deleted;
}

// Generate a 6-digit verification code
function generateVerificationCode(): string {
    return str_pad(strval(random_int(0, 999999)), 6, '0', STR_PAD_LEFT);
}

// Add email to pending subscriptions and send verification
function subscribeEmail(string $email): bool {
    $pending_file = PENDING_FILE;
    $subscribers_file = SUBSCRIBERS_FILE;
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { return false; }
    $subscribers = [];
    if (file_exists($subscribers_file)) {
        $json = file_get_contents($subscribers_file);
        $subscribers = json_decode($json, true) ?: [];
        if (in_array($email, $subscribers)) {
            return false; // Already subscribed
        }
    }
    $pending = [];
    if (file_exists($pending_file)) {
        $json = file_get_contents($pending_file);
        $pending = json_decode($json, true) ?: [];
    }
    $code = generateVerificationCode();
    $pending[$email] = [
        'code' => $code,
        'timestamp' => time()
    ];
    file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT), LOCK_EX);
    $verify_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/verify.php?email=' . urlencode($email) . '&code=' . urlencode($code);
    $subject = 'Verify subscription to Task Planner';
    $message = '<p>Click the link below to verify your subscription to Task Planner:</p>';
    $message .= '<p><a id="verification-link" href="' . htmlspecialchars($verify_link) . '">Verify Subscription</a></p>';
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: no-reply@example.com\r\nTo: $email";
    return mail($email, $subject, $message, $headers);
}

// Verify email subscription
function verifySubscription(string $email, string $code): bool {
    $pending_file = PENDING_FILE;
    $subscribers_file = SUBSCRIBERS_FILE;
    $email = strtolower(trim($email));
    $pending = [];
    if (file_exists($pending_file)) {
        $json = file_get_contents($pending_file);
        $pending = json_decode($json, true) ?: [];
    }
    if (isset($pending[$email]) && $pending[$email]['code'] === $code) {
        unset($pending[$email]);
        file_put_contents($pending_file, json_encode($pending, JSON_PRETTY_PRINT), LOCK_EX);
        $subscribers = [];
        if (file_exists($subscribers_file)) {
            $json = file_get_contents($subscribers_file);
            $subscribers = json_decode($json, true) ?: [];
        }
        if (!in_array($email, $subscribers)) {
            $subscribers[] = $email;
            file_put_contents($subscribers_file, json_encode($subscribers, JSON_PRETTY_PRINT), LOCK_EX);
        }
        return true;
    }
    return false;
}

// Remove email from subscribers list
function unsubscribeEmail(string $email): bool {
    $subscribers_file = SUBSCRIBERS_FILE;
    $email = strtolower(trim($email));
    if (!file_exists($subscribers_file)) {
        return false;
    }
    $json = file_get_contents($subscribers_file);
    $subscribers = json_decode($json, true) ?: [];
    $new_subs = [];
    $removed = false;
    foreach ($subscribers as $sub) {
        if ($sub === $email) {
            $removed = true;
            continue;
        }
        $new_subs[] = $sub;
    }
    if ($removed) {
        file_put_contents($subscribers_file, json_encode($new_subs, JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $removed;
}

// Sends task reminders to all subscribers
function sendTaskReminders(): void {
    $subscribers_file = SUBSCRIBERS_FILE;
    if (!file_exists($subscribers_file)) {
        return;
    }
    $json = file_get_contents($subscribers_file);
    $subs = json_decode($json, true) ?: [];
    $tasks = getAllTasks();
    $pending_tasks = array_filter($tasks, function($task) { return !$task['completed']; });
    foreach ($subs as $email) {
        sendTaskEmail($email, $pending_tasks);
    }
}

// Sends a task reminder email to a subscriber with pending tasks.
function sendTaskEmail(string $email, array $pending_tasks): bool {
    $subject = 'Task Planner - Pending Tasks Reminder';
    if (empty($pending_tasks)) {
        return true;
    }
    $body = '<h2>Pending Tasks Reminder</h2>';
    $body .= '<p>Here are the current pending tasks:</p>';
    $body .= '<ul>';
    foreach ($pending_tasks as $task) {
        $body .= '<li>' . htmlspecialchars($task['name']) . '</li>';
    }
    $body .= '</ul>';
    $unsubscribe_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/unsubscribe.php?email=' . urlencode($email);
    $body .= '<p><a id="unsubscribe-link" href="' . htmlspecialchars($unsubscribe_link) . '">Unsubscribe from notifications</a></p>';
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: no-reply@example.com\r\nTo: $email";
    return mail($email, $subject, $body, $headers);
}
