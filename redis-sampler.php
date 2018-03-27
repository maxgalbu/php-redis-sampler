<?php

if ($argc < 3) {
    die("Missing parameters - php redis-sampler.php <host> <db> <number_of_keys> <key_type>\n");
}
if (!preg_match("#[a-zA-Z0-9.]+#", $argv[1])) {
    die("First parameter must be the host - php redis-sampler.php <host> <db> <number_of_keys> <key_type>\n");
}
if (intval($argv[2]) != $argv[2]) {
    die("Second parameter must be the db number - php redis-sampler.php <host> <db> <number_of_keys> <key_type>\n");
}
if (intval($argv[3]) != $argv[3]) {
    die("Third parameter must be the number of keys to analyze - php redis-sampler.php <host> <db> <number_of_keys> <key_type>\n");
}
if (isset($argv[4]) && !in_array($argv[4], ["string","hash"])) {
    die("Optional fourth parameter can be the type of keys to analyze (hash or string) - php redis-sampler.php <host> <db> <number_of_keys> <key_type>\n");
}

$numKeys = intval($argv[3]);
$type = "";
if (isset($argv[4])) {
    $type = intval($argv[4]);
}

$redisConfig = [
    'host' => $argv[1],
    'port' => '6379',
    'db' => $argv[2],
    'timeout' => 5,
    'read_timeout' => 5,
];

$redis = new \Redis();
$redis->connect($redisConfig['host'], (integer)$redisConfig['port'], (integer)$redisConfig['timeout']);
$redis->select((integer)$redisConfig['db']);
$redis->setOption(\Redis::OPT_READ_TIMEOUT, (integer)$redisConfig['read_timeout']);

$lengths = [];
while (--$numKeys) {
    $key = $redis->randomKey();
    if ($type == "hash") {
        $hash = $redis->hGetAll($key);
        if (is_array($hash)) {
            foreach ($hash as $key => $value) {
                $lengths[] = strlen($value) + strlen($key);
            }
        }
    } else {
        $lengths[] = $redis->strlen($key);
    }
}

$sum = array_sum($lengths);
$count = count($lengths);

printf("Db number of keys is %d\n", $redis->dbSize());
printf("Sum is %d\n", $sum);
printf("Average is %d\n", $count ? $sum/$count : 0);
printf("Max is %d\n", $count ? max($lengths) : 0);
printf("Min is %d\n", $count ? min($lengths) : 0);
