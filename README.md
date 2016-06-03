# clue/term-react [![Build Status](https://travis-ci.org/clue/php-term-react.svg?branch=master)](https://travis-ci.org/clue/php-term-react)

Streaming terminal emulator, built on top of React PHP

> Note: This project is in early alpha stage! Feel free to report any issues you encounter.

## Usage

### ControlCodeParser

The `ControlCodeParser(ReadableStreamInterface $input)` class can be used to
parse any control code byte sequences when reading from an input stream and it
only returns its plain data stream.
It wraps a given `ReadableStreamInterface` and exposes its plain data through
the same interface.

```php
$stdin = new Stream(STDIN, $loop);

$stream = new ControlCodeParser($stdin);

$stream->on('data', function ($chunk) {
    var_dump($chunk);
});
```

As such, you can be sure the resulting `data` events never include any control
code byte sequences and it can be processed like a normal plain data stream.

React's streams emit chunks of data strings and make no assumption about any
byte sequences.
These chunks do not necessarily represent complete control code byte sequences,
as a sequence may be broken up into multiple chunks.
This class reassembles these sequences by buffering incomplete ones.

One of the most common forms of control code sequences is
[CSI (Control Sequence Introducer)](https://en.wikipedia.org/wiki/ANSI_escape_code#CSI_codes).
For example, CSI is used to print colored console output, also known as
"ANSI color codes" or the more technical term
[SGR (Select Graphic Rendition)](https://en.wikipedia.org/wiki/ANSI_escape_code#graphics).
CSI codes also appear on `STDIN`, for example when the user hits special keys,
such as the cursor, `HOME`, `END` etc. keys.
Each CSI code gets emitted as a `csi` event with its raw byte sequence:

```php
$stream->on('csi', function ($sequence) {
    if ($sequence === "\x1B[A") {
        echo 'cursor UP pressed';
    } else if ($sequence === "\x1B[B") {
        echo 'cursor DOWN pressed';
    }
});
```

Another common form of control code sequences is OSC (Operating System Command).
For example, OSC is used to change the window title or window icon.
Each OSC code gets emitted as an `osc` event with its raw byte sequence:

```php
$stream->on('osc', function ($sequence) {
    // handle byte sequence
});
```

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

```bash
$ composer require clue/term-react:dev-master
```

## License

MIT
