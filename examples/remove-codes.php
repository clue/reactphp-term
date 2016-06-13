<?php

// this simple example reads from STDIN, removes ALL codes and then prints to STDOUT.
// you can run this example and notice that special keys will be filtered out:
// $ php remove-codes.php
//
// you can also pipe the output of other commands into this to remove any control
// codes like this:
// $ phpunit --color=always | php remove-codes.php

use React\Stream\Stream;
use React\EventLoop\Factory;
use Clue\React\Term\ControlCodeParser;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();

if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
    // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
    shell_exec('stty -icanon -echo');
}

// process control codes from STDIN
$stdin = new Stream(STDIN, $loop);
$parser = new ControlCodeParser($stdin);

$stdout = new Stream(STDOUT, $loop);
$stdout->pause();

// pipe data from STDIN to STDOUT without any codes
$parser->pipe($stdout);

// only forward \r, \n and \t
$parser->on('c0', function ($code) use ($stdout) {
    if ($code === "\n" || $code === "\r" || $code === "\t") {
        $stdout->write($code);
    }
});

$loop->run();
