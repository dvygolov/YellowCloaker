<?php
$thankyoupage=file_get_contents('thankyou.html');
$fbpixel=$_GET['fbpixel'];
$gtm_id=file_get_contents('gtm.txt');
$thankyoupage=insert_gtm_tag($thankyoupage,$gtm_id,$fbpixel);
echo $thankyoupage;

function insert_gtm_tag($html,$gtm_id,$fbpixel){
	$needle='</head>';
	$pos=strpos($html,$needle,0);
	$gtm_text="<script>dataLayer=[{'fbpixel':'".$fbpixel."'}];(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','".$gtm_id."');</script>";
	$html=substr_replace($html,$gtm_text,$pos,0);
	return $html;
}