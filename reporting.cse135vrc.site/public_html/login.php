<?php
session_start();

// if logged in, skip to dashboard
if (isset($_SESSION['user'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // grader user and pw
    if ($username === 'grader' && $password === 'ilovecse135') {
        $_SESSION['user'] = $username;
        header('Location: /dashboard.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Login</title>
        <style>
            body {
                font-family: Georgia, 'Times New Roman', Times, serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 80vh;
                margin: 0;
            }
            .card{
                padding: 40px;
                border-radius: 8px;
                box-shadow: 1px 5px 10px rgba(0,0,0,0.4);
                width: 300px
            }
            h1 {
                
                font-size: 200%;
                border-bottom: 2px solid black;
                padding-bottom: 5px;
            }
            input, button {
                font-family: inherit;
            }

            button {
                
                --button_radius: 0.75em;
                --button_color: #e8e8e8;
                --button_outline_color: #000000;
                font-size: 18px;
                font-weight: bold;
                border: none;
                cursor: pointer;
                border-radius: var(--button_radius);
                background: var(--button_outline_color);
                padding: 0;          
                margin-bottom: 0.2em; 
                display: block;
                margin: 1.5em auto 0 auto;
            }

            .button_top {
                display: block;
                box-sizing: border-box;
                border: 2px solid var(--button_outline_color);
                border-radius: var(--button_radius);
                padding: 0.5em 1em;
                background: var(--button_color);
                color: var(--button_outline_color);
                transform: translateY(-0.2em);
                transition: transform 0.1s ease;
            }

            button:hover .button_top {
            /* Pull the button upwards when hovered */
            transform: translateY(-0.33em);
            }

            button:active .button_top {
            /* Push the button downwards when pressed */
            transform: translateY(0);
            }

            input[type="text"], input[type="password"]{
                margin-top: 0.5em;
                width: 100%;
                padding: 0.5em;
                border: 1.75px solid #000000;
                border-radius: 0.7em;
                box-sizing: border-box;
                 box-shadow: 2px 2px 2px #000;
                outline:none;
                font-size: 14px;
            }

            label{
                display:block;
                font-size: 120%;
            }

        </style>
    </head>
    <body>
        <div class="card">
              <h1>Login</h1>

            <?php if ($error): ?>
                <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <label>Username: <input type="text" name="username"></label><br>
                <label>Password: <input type="password" name="password"></label><br>
                <button type="submit">
                    <span class="button_top"> Login </span>
                </button>
            </form>
        </div>
          
</html>