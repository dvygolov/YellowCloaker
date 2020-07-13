<?php
	//чисто для формы на вайте, чтобы был один и тот же thankyou для вайта и блэка
	header("Location: thankyou.php?".http_build_query($_GET));
?>