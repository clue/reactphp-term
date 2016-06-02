<?php

use React\Stream\ReadableStream;
use Clue\React\Term\ControlCodeParser;

class ControlCodeParserTest extends TestCase
{
    private $input;
    private $parser;

    public function setUp()
    {
        $this->input = new ReadableStream();
        $this->parser = new ControlCodeParser($this->input);
    }

    public function testEmitsDataAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableOnceWith('hello'));
        $this->parser->on('csi', $this->expectCallableNever());

        $this->input->emit('data', array('hello'));
    }

    public function testEmitsDataInMultipleChunks()
    {
        $buffer = '';
        $this->parser->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });
        $this->parser->on('csi', $this->expectCallableNever());

        $this->input->emit('data', array('hello'));
        $this->input->emit('data', array('world'));

        $this->assertEquals('helloworld', $buffer);
    }

    public function testEmitsCsiAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("\x1B[A"));
    }

    public function testEmitsCsiAndData()
    {
        $this->parser->on('data', $this->expectCallableOnceWith("hello"));
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("\x1B[Ahello"));
    }

    public function testEmitsDataAndCsi()
    {
        $this->parser->on('data', $this->expectCallableOnceWith("hello"));
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("hello\x1B[A"));
    }

    public function testEmitsDataAndCsiAndData()
    {
        $buffer = '';
        $this->parser->on('data', function ($chunk) use (&$buffer) {
            $buffer .= $chunk;
        });
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("hello\x1B[Aworld"));

        $this->assertEquals('helloworld', $buffer);
    }
}
