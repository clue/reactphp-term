<?php

use React\Stream\Stream;
use React\EventLoop\Factory;
use Clue\React\Term\ControlCodeParser;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();

// Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
shell_exec('stty -icanon -echo');

// process control codes from STDIN
$stdin = new Stream(STDIN, $loop);
$parser = new ControlCodeParser($stdin);

$parser->on('csi', function ($code) {
    echo 'Code:';
    for ($i = 0; isset($code[$i]); ++$i) {
        echo sprintf(" %02X", ord($code[$i]));
    }
    echo PHP_EOL;
});

$parser->on('data', function ($bytes) {
    echo 'Data: ' . $bytes . PHP_EOL;
});

$loop->run();
