<?php

use Clue\React\Term\ControlCodeParser;
use React\EventLoop\Factory;
use React\Stream\ReadableResourceStream;

class FunctionalControlCodeParserTest extends TestCase
{
    public function testPipingReadme()
    {
        $loop = Factory::create();

        $input = new ReadableResourceStream(fopen(__DIR__ . '/../README.md', 'r+'), $loop);
        $parser = new ControlCodeParser($input);

        $buffer = '';
        $parser->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });

        $loop->run();

        $readme = str_replace(
            "\n",
            '',
            file_get_contents(__DIR__ . '/../README.md')
        );

        $this->assertEquals($readme, $buffer);
    }
}
