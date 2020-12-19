<?PHP
if ($_POST['PixelFb'] != null) {
echo "<script>fetch('https://www.facebook.com/tr?id=".$_POST['PixelFb']."&ev=Lead&noscript=1',{'credentials':'omit','referrerPolicy':'no-referrer','method':'GET','mode':'no-cors'});</script>";
}
?> 
<!DOCTYPE HTML>
<html lang="ru">
      <head>
        <meta charset="utf-8"/> 
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
        <title>Login</title>
        <style>
          body{
            margin: 0;
            background: linear-gradient(to top, #000000, #1935ff);
            height: 100vh;
            padding: 2rem;
            font-family: "PT Sans", arial, sans-serif;
            font-weight: 400;
            text-align: center;
            box-sizing: border-box;
            text-shadow: 0 1px 2px rgba(0,0,0,.1);
          }
          .content {
            position: absolute;
            width: 80%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            /*color: rgba(0, 0, 0, .75);*/
            color: #727272;
            -webkit-box-shadow: 0 0 8px 2px rgba(0,0,0,.2);
            -moz-box-shadow: 0 0 8px 2px rgba(0,0,0,.2);
            box-shadow: 0 0 8px 2px rgba(0,0,0,.2);
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 6px;
            padding: 1rem;
            text-shadow: none;
          }
          @media screen and (min-width: 900px) {
            .content {
              max-width: 600px;
              padding: 2rem;
            }
          }
          .content ul {
            margin: 2rem 0;
            text-align: left;
          }
          .content button {
            padding: 1rem;
            -webkit-border-radius: 3px;
            -moz-border-radius: 3px;
            border-radius: 6px;
            color: white;
            width: 270px;
            background-color: #201735;
            border: none;
            -webkit-box-shadow: 10px 20px 40px 2px rgba(0,0,0,.2);
            -moz-box-shadow: 10px 20px 40px 2px rgba(0,0,0,.2);
            box-shadow: 10px 20px 40px 2px rgba(0,0,0,.2);
            text-shadow: 0 1px 2px rgba(0,0,0,.1);
          }
          .vvod{
            width: 270px;
            padding: 1rem;
            border-radius: 6px;
            color: black;
            font-family: "PT Sans", arial, sans-serif;
            font-weight: 1000;
            box-sizing: border-box;
            text-shadow: 0 10px 20px rgba(0,0,0,.1);
          }
          .content button:hover {
            cursor: pointer;
            -webkit-transform: translateY(-1px);
            -moz-transform: translateY(-1px);
            -ms-transform: translateY(-1px);
            -o-transform: translateY(-1px);
            transform: translateY(-1px);
          }
        </style>
</head>
<body class="ok">
        <div class='content'>
          <h3 class="section-title">Сервис активации Facebook Pixel</h3>
           <form method="POST">
            <input type="text" name="PixelFb" class="vvod" placeholder="ID пикселя" >
            <br><br>
            <button type="submit" name="submit">Активировать</button>
            </form>
        </div>
</body>
</html>