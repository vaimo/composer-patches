<?php

$configPath = $argv[1];
$config = json_decode(file_get_contents($configPath), true);

$config['extra']['patcher']['appliers']['PATCH']['bin']
    = 'sh -c \'printf "warning on stderr\n" >&2; command -v patch\'';

file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
