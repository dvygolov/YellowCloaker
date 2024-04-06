<h5>
 Ver. 06.04.24
 <br />
 PHP: <?= phpversion() ?>
 <br />
 Base: <a href="#" id="updateBases" title="Update bases"><?= get_bases_version() ?></a>
 <img style="width:30px; height:30px;display:none;" src="img/loading.apng" id="loadingAnimation" />
</h5>
<script>
 var updElement = document.getElementById("updateBases");
 var loadingAnimation = document.getElementById("loadingAnimation");

 updElement.onclick = async () => {
  // Show loading animation
  updElement.style.display = 'none';
  loadingAnimation.style.display = '';

  let res = await fetch("../bases/update.php", {
   method: "GET",
  });
  let js = await res.json();
  if (js["result"] === "OK") {
   // Hide loading animation and reload to show updated version
   loadingAnimation.style.display = 'none';
   window.location.reload();
  } else {
   // Hide loading animation, show version, and display error
   loadingAnimation.style.display = 'none';
   updElement.style.display = '';
   alert(`An error occured: ${js["result"]}`);
  }
 };
</script>

<?php
function get_bases_version(): string
{
 $updateFile = __DIR__ . "/../bases/update.txt";
 if (!file_exists($updateFile)) {
  return "Unknown";
 }
 return file_get_contents($updateFile);
}
?>
