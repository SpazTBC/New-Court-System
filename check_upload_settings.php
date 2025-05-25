<?php
echo "<h2>PHP Upload Settings</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Setting</th><th>Current Value</th><th>Recommended</th></tr>";

$settings = [
    'upload_max_filesize' => '200M',
    'post_max_size' => '200M',
    'memory_limit' => '512M',
    'max_execution_time' => '300',
    'max_input_time' => '300'
];

foreach($settings as $setting => $recommended) {
    $current = ini_get($setting);
    $color = ($current == $recommended) ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$setting}</td>";
    echo "<td style='color: {$color}'>{$current}</td>";
    echo "<td>{$recommended}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Upload Test</h3>";
echo "<p>Maximum file size that can be uploaded: " . min(
    ini_get('upload_max_filesize'),
    ini_get('post_max_size'),
    ini_get('memory_limit')
) . "</p>";

// Convert to bytes for comparison
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

$upload_max = convertToBytes(ini_get('upload_max_filesize'));
$post_max = convertToBytes(ini_get('post_max_size'));
$memory_max = convertToBytes(ini_get('memory_limit'));

echo "<p>Actual maximum upload size: " . round(min($upload_max, $post_max, $memory_max) / 1024 / 1024, 2) . " MB</p>";
?>