<?php

// this example prints anything you type on STDIN to STDOUT
// control codes will be given as their hex values for easier inspection.
// also try this out with special keys on your keyboard:
// $ php stdin-codes.php
//
// you can also pipe the output of other commands into this to see any control
// codes like this:
// $ phpunit --color=always | php stdin-codes.php

use React\EventLoop\Factory;
use Clue\React\Term\ControlCodeParser;
use React\Stream\ReadableResourceStream;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();

if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
    // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
    shell_exec('stty -icanon -echo');
}

// process control codes from STDIN
$stdin = new ReadableResourceStream(STDIN, $loop);
$parser = new ControlCodeParser($stdin);

$decoder = function ($code) {
    echo 'Code:';
    for ($i = 0; isset($code[$i]); ++$i) {
        echo sprintf(" %02X", ord($code[$i]));
    }
    echo PHP_EOL;
};

$parser->on('csi', $decoder);
$parser->on('osc', $decoder);
$parser->on('c1', $decoder);
$parser->on('c0', $decoder);

$parser->on('data', function ($bytes) {
    echo 'Data: ' . $bytes . PHP_EOL;
});

$loop->run();
