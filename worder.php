<?php
include 'redirect.php';
//чисто для формы на вайте, чтобы был один и тот же thankyou для вайта и блэка
redirect("thankyou/thankyou.php?".http_build_query($_GET));
?>