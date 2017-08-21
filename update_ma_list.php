<?php
ini_set('memory_limit', '2G');
require(__DIR__ . '/libs/include.php');

if (php_sapi_name() != 'cli') {
    header('Content-type: text/plain');
}

$time_start = microtime(true);

$srcUrls = array(
    'http://www.soumu.go.jp/main_content/000124070.xls',
    'http://www.soumu.go.jp/main_content/000124071.xls',
    'http://www.soumu.go.jp/main_content/000124072.xls',
    'http://www.soumu.go.jp/main_content/000124073.xls',
    'http://www.soumu.go.jp/main_content/000124074.xls',
    'http://www.soumu.go.jp/main_content/000124075.xls',
    'http://www.soumu.go.jp/main_content/000124076.xls',
    'http://www.soumu.go.jp/main_content/000124077.xls',
    'http://www.soumu.go.jp/main_content/000124078.xls',
);
$parser = new \TelephoneNumberParser(__DIR__ . '/setting.yml');

$obj = new \UpdateMaList($srcUrls, __DIR__ . '/tmp');
$obj->create();
$sqls = $obj->createSqls();

echo 'Count: ' . count($sqls) . PHP_EOL;

foreach ($sqls as $i => $sql) {
    $parser->getDatabase()->query($sql);
    echo 'Query ' . ($i + 1) . ' done.' . PHP_EOL;
}

echo 'done.' . PHP_EOL;
echo (microtime(true) - $time_start) . ' sec.' . PHP_EOL;

