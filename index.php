<?php
require(__DIR__ . '/libs/include.php');

$testdata = yaml_parse_file(__DIR__ . '/data/test_data.yml');
$parser = new \TelephoneNumberParser(__DIR__ . '/setting.yml');

dump('Test Data: ' . count($testdata['phoneNos']));

$time_start = microtime(true);

foreach ($testdata['phoneNos'] as $i => $phoneNo) {
    $result = $parser->parse($phoneNo[0]);
    if ($result['is_error']) {
        dump($result);
    }
    if ($result['joined'] != $phoneNo[1]) {
        dump($result);
    }
}

dump('done.');
dump((microtime(true) - $time_start) . 'sec.');

