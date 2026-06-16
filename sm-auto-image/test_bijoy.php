<?php
require 'Unicode2Bijoy.php';

$test_str = 'আমার সোনার বাংলা';
$converted = \mirazmac\Unicode2Bijoy::convert($test_str);

echo "Original: " . $test_str . "\n";
echo "Converted: " . $converted . "\n";
