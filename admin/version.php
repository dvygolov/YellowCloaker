<h5>
 Ver. 02.01.24
 <br />
 PHP: <?= phpversion() ?>
 <br />
 Base: <?= get_bases_version() ?>
</h5>

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
