<?php

// this simple example reads from STDIN, assigns a random text color and then prints to STDOUT.
// you can run this example and notice anything you type will get a random color:
// $ php random-colors.php
//
// you can also pipe the output of other commands into this like this:
// $ echo hello | php random-colors.php
//
// notice how if the input contains any colors to begin with, they will be replaced
// with random colors:
// $ phpunit --color=always | php random-colors.php

use React\EventLoop\Factory;
use Clue\React\Term\ControlCodeParser;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;

require __DIR__ . '/../vendor/autoload.php';

$loop = Factory::create();

if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
    // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
    shell_exec('stty -icanon -echo');
}

// process control codes from STDIN
$stdin = new ReadableResourceStream(STDIN, $loop);
$parser = new ControlCodeParser($stdin);

$stdout = new WritableResourceStream(STDOUT, $loop);

// pass all c0 codes through to output
$parser->on('c0', array($stdout, 'write'));

// replace any color codes (SGR) with a random color
$parser->on('csi', function ($code) use ($stdout) {
    // we read any color code (SGR) on the input
    // assign a new random foreground and background color instead
    if (substr($code, -1) === 'm') {
        $code = "\033[" . mt_rand(30, 37) . ';' . mt_rand(40, 47) . "m";
    }

    $stdout->write($code);
});

// reset to default color at the end
$stdin->on('close', function() use ($stdout) {
    $stdout->write("\033[m");
});

// pass plain data to output
$parser->pipe($stdout, array('end' => false));

// start with random color
$stdin->emit('data', array("\033[m"));

$loop->run();
