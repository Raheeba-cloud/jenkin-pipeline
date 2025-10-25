<?php
// Very small demo handler: just echo back the data.
// Do not use this as-is in production without sanitizing and CSRF protection.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');
    ?>
    <!doctype html>
    <html>
    <head><meta charset="utf-8"><title>Submitted</title></head>
    <body>
      <h1>Thanks, <?= $name ?></h1>
      <p>Email: <?= $email ?></p>
      <p>Message:</p>
      <pre><?= $message ?></pre>
      <p><a href="/">Back</a></p>
    </body>
    </html>
    <?php
} else {
    header('Location: /');
    exit;
}
