<?php

namespace Clue\Tests\React\Term;

use Clue\React\Term\ControlCodeParser;
use React\EventLoop\Loop;
use React\Stream\ReadableResourceStream;

class FunctionalControlCodeParserTest extends TestCase
{
    public function testPipingReadme()
    {
        $input = new ReadableResourceStream(fopen(__DIR__ . '/../README.md', 'r+'));
        $parser = new ControlCodeParser($input);

        $buffer = '';
        $parser->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });

        Loop::run();

        $readme = str_replace(
            "\n",
            '',
            file_get_contents(__DIR__ . '/../README.md')
        );

        $this->assertEquals($readme, $buffer);
    }
}
