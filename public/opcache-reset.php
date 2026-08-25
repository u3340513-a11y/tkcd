<?php
// Hangi canli-ara.php çalışıyor?
$file = dirname(__DIR__) . '/yonetim/inc/canli-ara.php';
echo 'Dosya mtime: ' . date('Y-m-d H:i:s', filemtime($file)) . PHP_EOL;
echo 'ikamet_ilcesi var mi: ' . (strpos(file_get_contents($file), 'ikamet_ilcesi') !== false ? 'EVET' : 'HAYIR') . PHP_EOL;
echo 'Sunucu dizini: ' . __DIR__;
