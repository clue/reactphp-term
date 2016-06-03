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

The following [C1 control codes](https://en.wikipedia.org/wiki/C0_and_C1_control_codes#C1_set)
are supported as defined in [ISO/IEC 2022](https://en.wikipedia.org/wiki/ISO/IEC_2022):

* [CSI (Control Sequence Introducer)](https://en.wikipedia.org/wiki/ANSI_escape_code#CSI_codes)
  is one of the most common forms of control code sequences.
  For example, CSI is used to print colored console output, also known as
  "ANSI color codes" or the more technical term
  [SGR (Select Graphic Rendition)](https://en.wikipedia.org/wiki/ANSI_escape_code#graphics).
  CSI codes also appear on `STDIN`, for example when the user hits special keys,
  such as the cursor, `HOME`, `END` etc. keys.

* OSC (Operating System Command)
  is another common form of control code sequences.
  For example, OSC is used to change the window title or window icon.

* APC (Application Program-Control)

* DPS (Device-Control string)

* PM (Privacy Message)

Each code sequence gets emitted with a dedicated event with its raw byte sequence:

```php
$stream->on('csi', function ($sequence) {
    if ($sequence === "\x1B[A") {
        echo 'cursor UP pressed';
    } else if ($sequence === "\x1B[B") {
        echo 'cursor DOWN pressed';
    }
});

$stream->on('osc', function ($sequence) { … });
$stream->on('apc', function ($sequence) { … });
$stream->on('dps', function ($sequence) { … });
$stream->on('pm', function ($sequence) { … });
```

Other lesser known [C1 control codes](https://en.wikipedia.org/wiki/C0_and_C1_control_codes#C1_set)
not listed above are supported by just emitting their 2-byte sequence.
Each generic C1 code gets emitted as an `c1` event with its raw 2-byte sequence:

```php
$stream->on('c1', function ($sequence) { … });
```

All other [C0 control codes](https://en.wikipedia.org/wiki/C0_and_C1_control_codes#C0_.28ASCII_and_derivatives.29),
also known as [ASCII control codes](https://en.wikipedia.org/wiki/ASCII#ASCII_control_code_chart),
are supported by just emitting their single-byte value.
Each generic C0 code gets emitted as an `c0` event with its raw single-byte value:

```php
$stream->on('c0', function ($code) {
    if ($code === "\n") {
        echo 'ENTER pressed';
    }
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
