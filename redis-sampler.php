<?php

require_once __DIR__ . "/../vendor/autoload.php";

$options = getopt("h:n:s:k:t:", [
    "host:", "db:", "sample:", "key:", "keytype:"
]);

function help($message = "Missing parameters") {
    die(<<<EOF
$message
Examples: 
    - php script.php -h <host> -n <db> -- <number_of_keys>
    - php script.php --host <host> --db <db> --sample <number_of_keys>
    - php script.php -h <host> -n <db> -s <number_of_keys> -key <key>

EOF
);
}

if (!$options) {
    help();
}

$options['host'] = isset($options['host']) || isset($options['h']) ? (isset($options['host']) ? $options['host'] : $options['h']) : "";
$options['db'] = isset($options['db']) || isset($options['n']) ? (isset($options['db']) ? $options['db'] : $options['n']) : "";
$options['sample'] = isset($options['sample']) || isset($options['s']) ? (isset($options['sample']) ? $options['sample'] : $options['s']) : "";
$options['key'] = isset($options['key']) || isset($options['k']) ? (isset($options['key']) ? $options['key'] : $options['k']) : "";

if (!$options['host'] || !preg_match("#[a-zA-Z0-9.]+#", $options['host'])) {
    help("Missing or invalid host parameter (--host or -h)");
}
if (!$options['db'] || intval($options['db']) != $options['db']) {
    help("--db (-n) parameter must be a number");
}
if (!$options['sample'] || intval($options['sample']) != $options['sample']) {
    help("--sample (-s) parameter must be a number");
}

$sampleCount = intval($options['sample']);

$redisConfig = [
    'host' => $options['host'],
    'port' => '6379',
    'db' => $options['db'],
    'timeout' => 5,
    'read_timeout' => 5,
];

$redis = new \Redis();
$redis->connect($redisConfig['host'], (integer)$redisConfig['port'], (integer)$redisConfig['timeout']);
$redis->select((integer)$redisConfig['db']);
$redis->setOption(\Redis::OPT_READ_TIMEOUT, (integer)$redisConfig['read_timeout']);

$lengths = [];
if (!empty($options['key'])) {
    $key = $options['key'];
    $keyType = $redis->type($key);
    if ($keyType != Redis::REDIS_HASH) {
        help("Key $key must be a hash");
    }

    $totalKeys = $redis->hLen($key);
    $iterator = null;
    while (--$sampleCount) {
        $hashKeys = $redis->hScan($key, $iterator, "*", 1);
        if (count($hashKeys)) {
            reset($hashKeys);
            $hashKey = key($hashKeys);
            $hashValue = current($hashKeys);
            $lengths[] = strlen($hashKey) + strlen($hashValue);
        }
    }
} else {
    $totalKeys = $redis->dbSize();

    while (--$sampleCount) {
        $key = $options['key'] ?: $redis->randomKey();
        $keyType = $redis->type($key);
        if ($keyType == Redis::REDIS_HASH) {
            $hash = $redis->hGetAll($key);
            if (is_array($hash)) {
                foreach ($hash as $hashKey => $hashValue) {
                    $lengths[] = strlen($hashValue) + strlen($hashKey);
                }
            }
        } else {
            $lengths[] = $redis->strlen($key);
        }
    }
}

$sum = array_sum($lengths);
$count = count($lengths);

printf("Number of keys is %d\n", $totalKeys);
printf("Sum is %d\n", $sum);
printf("Average is %d\n", $count ? $sum/$count : 0);
printf("Max is %d\n", $count ? max($lengths) : 0);
printf("Min is %d\n", $count ? min($lengths) : 0);
