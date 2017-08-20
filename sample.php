<?php
require(__DIR__ . '/libs/include.php');

$parser = new \TelephoneNumberParser(__DIR__ . '/setting.yml');
$result = $parser->parse('09012345678'); // 090-1234-5678

header('Content-type: text/plain');
var_export($result);

