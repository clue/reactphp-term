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

    public function testEmitsC0AsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('c0', $this->expectCallableOnce("\n"));

        $this->input->emit('data', array("\n"));
    }

    public function testEmitsCsiAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("\x1B[A"));
    }

    public function testEmitsChunkedStartCsiAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("\x1B"));
        $this->input->emit('data', array("[A"));
    }

    public function testEmitsChunkedMiddleCsiAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[A"));

        $this->input->emit('data', array("\x1B["));
        $this->input->emit('data', array("A"));
    }

    public function testEmitsChunkedEndCsiAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('csi', $this->expectCallableOnceWith("\x1B[2A"));

        $this->input->emit('data', array("\x1B[2"));
        $this->input->emit('data', array("A"));
    }

    public function testEmitsDataAndC0()
    {
        $this->parser->on('data', $this->expectCallableOnceWith("hello world"));
        $this->parser->on('c0', $this->expectCallableOnceWith("\n"));

        $this->input->emit('data', array("hello world\n"));
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

    public function testEmitsOscBelAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('osc', $this->expectCallableOnceWith("\x1B]asd\x07"));

        $this->input->emit('data', array("\x1B]asd\x07"));
    }

    public function testEmitsOscStAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('osc', $this->expectCallableOnceWith("\x1B]asd\x1B\\"));

        $this->input->emit('data', array("\x1B]asd\x1B\\"));
    }

    public function testEmitsChunkedStartOscAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('osc', $this->expectCallableOnceWith("\x1B]asd\x07"));

        $this->input->emit('data', array("\x1B"));
        $this->input->emit('data', array("]asd\x07"));
    }

    public function testEmitsChunkedMiddleOscAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('osc', $this->expectCallableOnceWith("\x1B]asd\x07"));

        $this->input->emit('data', array("\x1B]as"));
        $this->input->emit('data', array("d\x07"));
    }

    public function testEmitsDpsStAsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('dps', $this->expectCallableOnceWith("\x1BPasd\x1B\\"));

        $this->input->emit('data', array("\x1BPasd\x1B\\"));
    }

    public function testDoesNotEmitDpsIfItDoesNotEndWithSt()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('dps', $this->expectCallableNever());

        $this->input->emit('data', array("\x1BPasd\x07"));
    }

    public function testEmitsUnknownC1AsOneChunk()
    {
        $this->parser->on('data', $this->expectCallableNever());
        $this->parser->on('c1', $this->expectCallableOnceWith("\x1B="));

        $this->input->emit('data', array("\x1B="));
    }

    public function testClosingInputWillCloseParser()
    {
        $this->parser->on('close', $this->expectCallableOnce());

        $this->input->close();

        $this->assertFalse($this->parser->isReadable());
    }

    public function testClosingParserWillCloseInput()
    {
        $this->input = $this->getMock('React\Stream\ReadableStreamInterface');
        $this->input->expects($this->once())->method('isReadable')->willReturn(true);
        $this->input->expects($this->once())->method('close');

        $this->parser = new ControlCodeParser($this->input);
        $this->parser->on('close', $this->expectCallableOnce());

        $this->parser->close();

        $this->assertFalse($this->parser->isReadable());
    }

    public function testClosingParserMultipleTimesWillOnlyCloseOnce()
    {
        $this->input = $this->getMock('React\Stream\ReadableStreamInterface');
        $this->input->expects($this->once())->method('isReadable')->willReturn(true);
        $this->input->expects($this->once())->method('close');

        $this->parser = new ControlCodeParser($this->input);
        $this->parser->on('close', $this->expectCallableOnce());

        $this->parser->close();
        $this->parser->close();
    }

    public function testPassingClosedInputToParserWillCloseParser()
    {
        $this->input = $this->getMock('React\Stream\ReadableStreamInterface');
        $this->input->expects($this->once())->method('isReadable')->willReturn(false);

        $this->parser = new ControlCodeParser($this->input);

        $this->assertFalse($this->parser->isReadable());
    }

    public function testWillForwardPauseToInput()
    {
        $this->input = $this->getMock('React\Stream\ReadableStreamInterface');
        $this->input->expects($this->once())->method('pause');

        $this->parser = new ControlCodeParser($this->input);

        $this->parser->pause();
    }

    public function testWillForwardResumeToInput()
    {
        $this->input = $this->getMock('React\Stream\ReadableStreamInterface');
        $this->input->expects($this->once())->method('resume');

        $this->parser = new ControlCodeParser($this->input);

        $this->parser->resume();
    }

    public function testPipeWillReturnDestStream()
    {
        $dest = $this->getMock('React\Stream\WritableStreamInterface');

        $ret = $this->parser->pipe($dest);

        $this->assertSame($dest, $ret);
    }

    public function testEmitsErrorEventAndCloses()
    {
        $this->parser->on('error', $this->expectCallableOnce());
        $this->parser->on('close', $this->expectCallableOnce());

        $this->input->emit('error', array(new \RuntimeException()));

        $this->assertFalse($this->parser->isReadable());
    }

    public function testEmitsEndEventAndCloses()
    {
        $this->parser->on('end', $this->expectCallableOnce());
        $this->parser->on('close', $this->expectCallableOnce());

        $this->input->emit('end', array());

        $this->assertFalse($this->parser->isReadable());
    }

    public function testEmitsErrorWhenEndEventHasWillBufferedData()
    {
        $this->parser->on('end', $this->expectCallableNever());
        $this->parser->on('error', $this->expectCallableOnce());
        $this->parser->on('close', $this->expectCallableOnce());

        // emit incomplete sequence start and then end
        $this->input->emit('data', array("\x1B"));
        $this->input->emit('end', array());

        $this->assertFalse($this->parser->isReadable());
    }
}
