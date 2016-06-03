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
        return !$this->closed && $this->input->isReadable();
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
            // search ESC (\x1B = \033)
            $esc = strpos($this->buffer, "\x1B");

            // no ESC found, emit whole buffer as data
            if ($esc === false) {
                $data = $this->buffer;
                $this->buffer = '';

                $this->emit('data', array($data));
                return;
            }

            // ESC found somewhere inbetween, emit everything before ESC as data
            if ($esc !== 0) {
                $data = substr($this->buffer, 0, $esc);
                $this->buffer = substr($this->buffer, $esc);

                $this->emit('data', array($data));
            }

            // ESC is now at start of buffer

            // check following byte to determine type
            if (!isset($this->buffer[1])) {
                // type currently unknown, wait for next data chunk
                break;
            }

            if ($this->buffer[1] === '[') {
                // followed by "[" means it's CSI
            } else {
                $data = substr($this->buffer, 0, 2);
                $this->buffer = (string)substr($this->buffer, 2);

                $this->emit('data', array($data));
                continue;
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
