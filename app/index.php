<?php
// Simple form demo (nginx + php-fpm)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>MySite - Contact Form</title>
  <style>
    /* Reset default browser styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .form-container {
      background: #fff;
      padding: 40px 50px;
      border-radius: 12px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 500px;
    }

    h1 {
      text-align: center;
      color: #333;
      margin-bottom: 30px;
      font-size: 2em;
      letter-spacing: 1px;
    }

    label {
      display: block;
      margin-bottom: 15px;
      color: #555;
      font-weight: 500;
    }

    input[type="text"],
    input[type="email"],
    textarea {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1em;
      transition: 0.3s;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    textarea:focus {
      border-color: #2575fc;
      outline: none;
      box-shadow: 0 0 8px rgba(37, 117, 252, 0.3);
    }

    button {
      width: 100%;
      padding: 15px;
      background: #2575fc;
      color: #fff;
      font-size: 1.1em;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }

    button:hover {
      background: #6a11cb;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    textarea {
      resize: vertical;
    }

    @media (max-width: 600px) {
      .form-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h1>Contact Us</h1>
    <form method="POST" action="/submit.php">
      <label>Enter The Name:
        <input type="text" name="name" required>
      </label>

      <label>Email:
        <input type="email" name="email" required>
      </label>

      <label>Message:
        <textarea name="message" rows="6" required></textarea>
      </label>

      <button type="submit">Send Message</button>
    </form>
  </div>
</body>
</html>
