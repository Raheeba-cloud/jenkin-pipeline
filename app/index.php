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
/* Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background: #0d0d0d; /* Black luxury background */
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

/* Form */
.form-container {
  background: #1a1a1a;
  padding: 45px 55px;
  border-radius: 14px;
  border: 1px solid #b9972d; /* Gold border */
  box-shadow: 0 8px 30px rgba(255, 215, 0, 0.15);
  width: 100%;
  max-width: 500px;
}

h1 {
  text-align: center;
  color: #d4af37;
  margin-bottom: 30px;
  font-size: 2.2em;
  font-weight: 700;
  letter-spacing: 1.5px;
}

/* Inputs */
label {
  display: block;
  margin-bottom: 15px;
  color: #e6e6e6;
  font-weight: 500;
}

input[type="text"],
input[type="email"],
textarea {
  width: 100%;
  padding: 12px 15px;
  border: 1px solid #555;
  border-radius: 8px;
  font-size: 1em;
  color: #fff;
  background: #222;
  transition: 0.3s;
}

input:focus,
textarea:focus {
  border-color: #d4af37;
  outline: none;
  box-shadow: 0 0 12px rgba(212, 175, 55, 0.4);
}

/* Button */
button {
  width: 100%;
  padding: 15px;
  background: #d4af37;
  color: #000;
  font-size: 1.15em;
  font-weight: bold;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.35s ease-in-out;
  box-shadow: 0 4px 18px rgba(212,175,55,0.35);
}

button:hover {
  background: #b9972d;
  transform: translateY(-4px) scale(1.02); /* Hover animation */
  box-shadow: 0 6px 25px rgba(212,175,55,0.5);
}

/* Responsive */
@media (max-width: 600px) {
  .form-container {
    padding: 35px 25px;
  }
}
  </style>
</head>
<body>
  <div class="form-container">
    <h1>Contact Us</h1>
    <form method="POST" action="/submit.php">
      <label>Name:
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
