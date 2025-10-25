<?php
// Simple form demo (nginx + php-fpm)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>MySite - Simple Form</title>
</head>
<body>
  <h1>Contact form</h1>
  <form method="POST" action="/submit.php">
    <label>Name: <input type="text" name="name" required></label><br><br>
    <label>Email: <input type="email" name="email" required></label><br><br>
    <label>Message:<br>
      <textarea name="message" rows="6" cols="40" required></textarea>
    </label><br><br>
    <button type="submit">Send</button>
  </form>
</body>
</html>
