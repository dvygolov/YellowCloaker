<?php
function check_geo_bases() {
    $bases_dir = __DIR__ . '/../bases/';
    $required_files = ['country.mmdb', 'asn.mmdb'];
    $missing_files = [];
    
    // Check if directory exists
    if (!is_dir($bases_dir)) {
        return '<span class="error">Bases directory NOT FOUND!</span>';
    }
    
    // Check for required files
    foreach ($required_files as $file) {
        if (!file_exists($bases_dir . $file)) {
            $missing_files[] = $file;
        }
    }
    
    // If we have missing files, return the list
    if (!empty($missing_files)) {
        return '<span class="error">Missing: ' . implode(', ', $missing_files) . '</span>';
    }
    
    // Check for update.txt
    if (file_exists($bases_dir . 'update.txt')) {
        $version = trim(file_get_contents($bases_dir . 'update.txt'));
        return $version;
    }
    
    // All files found but no update.txt
    return 'Found';
}
?>
<div id="version">
    <div class="version-grid">
        <div class="version-column">
            <div class="version-item">
                <span class="version-label">Version:</span>
                <span class="version-value"><?= file_get_contents(__DIR__.'/version.txt') ?></span>
            </div>
            <div class="version-item">
                <span class="version-label">GeoBases:</span>
                <span class="version-value"><?= check_geo_bases() ?></span>
            </div>
            <div class="version-item">
                <span class="version-label">SQLite:</span>
                <span class="version-value"><?= extension_loaded('sqlite3') ? SQLite3::version()['versionString'] : '<span class="error">Not Found!</span>' ?></span>
            </div>
        </div>
        <div class="version-column">
            <div class="version-item">
                <span class="version-label">cURL:</span>
                <span class="version-value"><?= extension_loaded('curl') ? curl_version()['version'] : '<span class="error">Not Found!</span>' ?></span>
            </div>
            <div class="version-item">
                <span class="version-label">PHP:</span>
                <span class="version-value"><?= phpversion() ?></span>
            </div>
            <div class="version-item">
                <span class="version-label">Zip:</span>
                <span class="version-value"><?= extension_loaded('zip') ? 'Found' : '<span class="error">Not Found, autoupdate will fail!</span>' ?></span>
            </div>
        </div>
    </div>
</div>
