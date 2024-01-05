<?php
include __DIR__."/password.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (check_password()) {
        header("Location: statistics.php");
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
        }
        #main {
            margin-top: 50px;
        }
        #title {
            font-size: 40px;
            margin-bottom: 40px;
        }
        #login-form {
            display: inline-block;
        }
        #password {
            width: 300px;
            height: 40px;
            font-size: 20px;
            margin-bottom: 10px;
        }
        label {
            font-size: 20px;
        }
        button {
            width: 300px;
            height: 40px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div id="main">
    <div id="title">Yellow Cloaker Login</div>
    <form id="login-form" method="post" action="login.php">
        <label for="password">Admin Password</label><br>
        <input type="password" id="password" name="password"><br>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
