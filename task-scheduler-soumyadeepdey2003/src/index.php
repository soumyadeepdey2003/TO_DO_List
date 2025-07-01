<?php
require_once __DIR__ . '/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Task
    if (isset($_POST['task-name'])) {
        $task_name = trim($_POST['task-name']);
        if ($task_name !== '') {
            if (addTask($task_name)) {
                $message = 'Task added!';
            } else {
                $message = 'Task already exists or error adding task.';
            }
        } else {
            $message = 'Task name cannot be empty.';
        }
    }
    // Mark Complete/Incomplete
    if (isset($_POST['toggle-task'])) {
        $task_id = $_POST['toggle-task'];
        $is_completed = isset($_POST['status']) && $_POST['status'] === '1';
        markTaskAsCompleted($task_id, !$is_completed);
    }
    // Delete Task
    if (isset($_POST['delete-task'])) {
        $task_id = $_POST['delete-task'];
        deleteTask($task_id);
    }
    // Subscribe
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (subscribeEmail($email)) {
                $message = 'Verification email sent! Please check your inbox.';
            } else {
                $message = 'Subscription failed or already subscribed.';
            }
        } else {
            $message = 'Invalid email address.';
        }
    }
}
$tasks = getAllTasks();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Scheduler</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .task-list { list-style: none; padding: 0; }
        .task-item { margin-bottom: 10px; }
        .completed { text-decoration: line-through; color: #888; }
        .message { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Task Scheduler</h1>
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Add Task Form (always visible) -->
    <form method="post" id="add-task-form">
        <input type="text" name="task-name" id="task-name" placeholder="Enter new task" required>
        <button type="submit" id="add-task">Add Task</button>
    </form>

    <!-- Task List (always visible) -->
    <ul class="task-list" id="task-list">
        <?php foreach ($tasks as $task): ?>
            <li class="task-item<?php if ($task['completed']) echo ' completed'; ?>">
                <form method="post" style="display:inline;">
                    <input type="hidden" name="toggle-task" value="<?php echo htmlspecialchars($task['id']); ?>">
                    <input type="hidden" name="status" value="<?php echo $task['completed'] ? '1' : '0'; ?>">
                    <input type="checkbox" class="task-status" <?php if ($task['completed']) echo 'checked'; ?> onchange="this.form.submit()">
                </form>
                <span><?php echo htmlspecialchars($task['name']); ?></span>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete-task" value="<?php echo htmlspecialchars($task['id']); ?>">
                    <button class="delete-task" type="submit">Delete</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Subscribe Form (always visible) -->
    <form method="post" id="subscribe-form">
        <input type="email" name="email" id="email" placeholder="Enter your email for reminders" required>
        <button type="submit" id="subscribe-btn">Subscribe</button>
    </form>
</body>
</html>
