<?php

// this simple example reads from STDIN, removes ALL codes and then prints to STDOUT.
// you can run this example and notice that special keys will be filtered out:
// $ php remove-codes.php
//
// you can also pipe the output of other commands into this to remove any control
// codes like this:
// $ phpunit --color=always | php remove-codes.php

require __DIR__ . '/../vendor/autoload.php';

if (function_exists('posix_isatty') && posix_isatty(STDIN)) {
    // Disable icanon (so we can fread each keypress) and echo (we'll do echoing here instead)
    shell_exec('stty -icanon -echo');
}

// process control codes from STDIN
$stdin = new React\Stream\ReadableResourceStream(STDIN);
$parser = new Clue\React\Term\ControlCodeParser($stdin);

// pipe data from STDIN to STDOUT without any codes
$stdout = new React\Stream\WritableResourceStream(STDOUT);
$parser->pipe($stdout);

// only forward \r, \n and \t
$parser->on('c0', function ($code) use ($stdout) {
    if ($code === "\n" || $code === "\r" || $code === "\t") {
        $stdout->write($code);
    }
});
