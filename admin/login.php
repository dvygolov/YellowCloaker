<?php
require_once __DIR__."/password.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (check_password()) {
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Yellow Cloaker Login</title>
    <style>
    body {
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 20px;
        background: #1b2a47;
    }

    #main {
        margin-top: 50px;
    }

    #title {
        font-size: 40px;
        margin-bottom: 40px;
        color: white;
    }

    #login-form {
        display: inline-block;
    }

    #password {
        width: 300px;
        height: 40px;
        font-size: 20px;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    label {
        font-size: 20px;
        color: white;
    }

    button {
        width: 300px;
        height: 40px;
        font-size: 20px;
    }

    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
</head>
<body>
    <div id="main">
        <div id="title">
            <img src="img/logobig.png" />
        </div>
        <form id="login-form" method="post" action="./login.php">
            <label for="password">Enter Admin Password👇</label><br />
            <input type="password" id="password" name="password" required/ /><br />
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
