<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once __DIR__.'/../bases/ipcountry.php';
$ip = getip();
$country = getcountry($ip);
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Get FREE coupons for popular products.</title>
  <meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE,NOSNIPPET" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script type="text/javascript" src="//code.jquery.com/jquery-1.9.1.min.js"></script>
  <script type="text/javascript" src="/404/scripts.js"></script>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="description" content="Get FREE coupons for popular products." />
  <meta name="keywords" content="Coupons,Gas coupons,free coupons,rebates,Grocery coupons" />
  <link rel="stylesheet" href="/404/style.css" type="text/css" />
  <link rel="shortcut icon" type="image/x-icon" href="image.png" />
</head>

<body>
  <div id="coordAnchor">
    <div id="top-cookie">
		<p>404 Page Not Found</p>
	</div>
    <div id="main_rr">
      <div id="asset2" class="assetClass">
        <form class="email-box" onsubmit="mailCheck();return false;">
          <input id="email" type="email" placeholder="Enter your e-mail here" value="" required />

          <div onclick="mailCheck();" value="YES, I want coupons" class="send-btn">
            YES, I want coupons
          </div>
        </form>

        <div class="thanks" hidden="">
          Thank you for registering.<br />
          We'll send your free coupons soon.
        </div>
      </div>

      <div id="bullets">
        <ul>
          <li>Huge savings on high quality products for fitness, beauty, car,<br />
          home and garden.</li>

          <li>Get coupon codes for extra discounts and exclusive offers.</li>

          <li>Enter your email to get FREE coupons!</li>
        </ul>
      </div>

      <div id="Html1Coords" class="assetClass">
        <div id="cr_logo"><img src="/404/image1.png" border="0" /></div>

        <div id="logo"><img src="/404/image2.png" border="0" /></div>

        <div id="err404">
          <p>404 Error<br />
          Sorry, we could not find the page you&#39;re looking for.</p>
        </div>

        <div id="cr_headline">
          Get FREE coupons for popular products.
        </div>
      </div>
    </div>

    <div class="footer">
      <a href="/tos/index.php" target="_blank">Privacy Policy</a> | 
	  <a href="/tos/index.php" target="_blank">Terms of Use</a>
    </div>
  </div>
</body>
</html>
