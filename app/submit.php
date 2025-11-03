<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Form Submitted</title>
        <style>
            body {
                background: #000;
                font-family: Poppins, sans-serif;
                color: #fff;
                margin: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            .card {
                background: #111;
                padding: 30px 35px;
                border-radius: 12px;
                width: 420px;
                border: 1px solid rgba(255,215,0,0.3);
                box-shadow: 0 0 20px rgba(255,215,0,0.15);
                transition: .3s ease-in-out;
            }
            .card:hover {
                box-shadow: 0 0 25px rgba(255,215,0,0.4);
                transform: translateY(-5px);
            }
            h1 {
                margin: 0 0 10px;
                font-size: 26px;
                background: linear-gradient(90deg, #ffd700, #b8860b);
                -webkit-background-clip: text;
                color: transparent;
            }
            .info {
                margin: 8px 0;
                font-size: 18px;
            }
            pre {
                background: #222;
                padding: 10px;
                border-radius: 8px;
                color: #ffd700;
                font-size: 16px;
            }
            a {
                display: inline-block;
                margin-top: 20px;
                text-decoration: none;
                background: #ffd700;
                color: #000;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: bold;
                transition: .3s;
            }
            a:hover {
                background: #b8860b;
                box-shadow: 0 0 15px rgba(255,215,0,0.7);
                transform: scale(1.08);
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Thanks, <?= $name ?> ✨</h1>
            <p class="info"><strong>Email:</strong> <?= $email ?></p>
            <p class="info"><strong>Message:</strong></p>
            <pre><?= $message ?></pre>

            <a href="/">⬅ Back to Form</a>
        </div>
    </body>
    </html>
    <?php
} else {
    header('Location: /');
    exit;
}
