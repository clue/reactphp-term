<?php

namespace Clue\React\Term;

use Evenement\EventEmitter;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use React\Stream\Util;

class ControlCodeParser extends EventEmitter implements ReadableStreamInterface
{
    private $input;
    private $closed = false;
    private $buffer = '';

    public function __construct(ReadableStreamInterface $input)
    {
        $this->input = $input;

        if (!$this->input->isReadable()) {
            $this->close();
        }

        $this->input->on('data', array($this, 'handleData'));
        $this->input->on('close', array($this, 'close'));
    }

    public function isReadable()
    {
        return $this->input->isReadable();
    }

    public function pause()
    {
        $this->input->pause();
    }

    public function resume()
    {
        $this->input->resume();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;

        $this->input->close();

        $this->emit('close');
    }

    /** @internal */
    public function handleData($data)
    {
        $this->buffer .= $data;

        while ($this->buffer !== '') {
            // search CSI start
            $pos = strpos($this->buffer, "\x1B[");

            // no CSI found, emit whole buffer as data
            if ($pos === false) {
                $data = $this->buffer;
                $this->buffer = '';

                $this->emit('data', array($data));
                return;
            }

            // CSI found somewhere inbetween, emit everything before CSI as data
            if ($pos !== 0) {
                $data = substr($this->buffer, 0, $pos);
                $this->buffer = substr($this->buffer, $pos);

                $this->emit('data', array($data));
            }

            // CSI is now at the start of the buffer, search final character
            $found = false;
            for ($i = 2; isset($this->buffer[$i]); ++$i) {
                $code = ord($this->buffer[$i]);

                // final character between \x40-x7E
                if ($code >= 64 && $code <= 126) {
                    $data = substr($this->buffer, 0, $i + 1);
                    $this->buffer = (string)substr($this->buffer, $i + 1);

                    $this->emit('csi', array($data));
                    $found = true;
                    break;
                }
            }

            // no final character found => wait for next data chunk
            if (!$found) {
                break;
            }
        }
    }
}
