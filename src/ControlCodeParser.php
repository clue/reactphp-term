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

    /**
     * we know about the following C1 types (7 bit only)
     *
     * followed by "[" means it's CSI (Control Sequence Introducer)
     * followed by "]" means it's OSC (Operating System Controls)
     * followed by "_" means it's APC (Application Program-Control)
     * followed by "P" means it's DPS (Device-Control string)
     * followed by "^" means it's PM (Privacy Message)
     *
     * Each of these will be parsed until the sequence ends and then emitted
     * under their respective name.
     *
     * All other C1 types will be emitted under the "c1" name without any
     * further processing.
     *
     * C1 types in 8 bit are currently not supported, as they require special
     * care with regards to whether UTF-8 mode is enabled. So far this has
     * turned out to be a non-issue because most terminal emulators *accept*
     * boths formats, but usually *send* in 7 bit mode exclusively.
     */
    private $types = array(
        '[' => 'csi',
        ']' => 'osc',
        '_' => 'apc',
        'P' => 'dps',
        '^' => 'pm',
    );

    public function __construct(ReadableStreamInterface $input)
    {
        $this->input = $input;

        if (!$this->input->isReadable()) {
            return $this->close();
        }

        $this->input->on('data', array($this, 'handleData'));
        $this->input->on('end', array($this, 'handleEnd'));
        $this->input->on('error', array($this, 'handleError'));
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
        $this->buffer = '';

        $this->input->close();

        $this->emit('close');
        $this->removeAllListeners();
    }

    /** @internal */
    public function handleData($data)
    {
        $this->buffer .= $data;

        while ($this->buffer !== '') {
            // search for first control character (C0 and DEL)
            $c0 = false;
            for ($i = 0; isset($this->buffer[$i]); ++$i) {
                $code = ord($this->buffer[$i]);
                if ($code < 0x20 || $code === 0x7F) {
                    $c0 = $i;
                    break;
                }
            }

            // no C0 found, emit whole buffer as data
            if ($c0 === false) {
                $data = $this->buffer;
                $this->buffer = '';

                $this->emit('data', array($data));
                return;
            }

            // C0 found somewhere inbetween, emit everything before C0 as data
            if ($c0 !== 0) {
                $data = substr($this->buffer, 0, $c0);
                $this->buffer = substr($this->buffer, $c0);

                $this->emit('data', array($data));
                continue;
            }

            // C0 is now at start of buffer
            // check if this is a normal C0 code or an ESC (\x1B = \033)
            // normal C0 will be emitted, ESC will be parsed further
            if ($this->buffer[0] !== "\x1B") {
                $data = $this->buffer[0];
                $this->buffer = (string)substr($this->buffer, 1);

                $this->emit('c0', array($data));
                continue;
            }

            // check following byte to determine type
            if (!isset($this->buffer[1])) {
                // type currently unknown, wait for next data chunk
                break;
            }

            // if this is an unknown type, just emit as "c1" without further parsing
            if (!isset($this->types[$this->buffer[1]])) {
                $data = substr($this->buffer, 0, 2);
                $this->buffer = (string)substr($this->buffer, 2);

                $this->emit('c1', array($data));
                continue;
            }

            // this is known type, check for the sequence end
            $type = $this->types[$this->buffer[1]];
            $found = false;

            if ($type === 'csi') {
                // CSI is now at the start of the buffer, search final character
                for ($i = 2; isset($this->buffer[$i]); ++$i) {
                    $code = ord($this->buffer[$i]);

                    // final character between \x40-\x7E
                    if ($code >= 64 && $code <= 126) {
                        $data = substr($this->buffer, 0, $i + 1);
                        $this->buffer = (string)substr($this->buffer, $i + 1);

                        $this->emit($type, array($data));
                        $found = true;
                        break;
                    }
                }
            } else {
                // all other types are terminated by ST
                // only OSC can also be terminted by BEL (whichever comes first)
                $st = strpos($this->buffer, "\x1B\\");
                $bel = ($type === 'osc') ? strpos($this->buffer, "\x07") : false;

                if ($st !== false && ($bel === false || $bel > $st)) {
                    // ST comes before BEL or no BEL found
                    $data = substr($this->buffer, 0, $st + 2);
                    $this->buffer = (string)substr($this->buffer, $st + 2);

                    $this->emit($type, array($data));
                    $found = true;
                } elseif ($bel !== false) {
                    // BEL comes before ST or no ST found
                    $data = substr($this->buffer, 0, $bel + 1);
                    $this->buffer = (string)substr($this->buffer, $bel + 1);

                    $this->emit($type, array($data));
                    $found = true;
                }
            }

            // no final character found => wait for next data chunk
            if (!$found) {
                break;
            }
        }
    }

    /** @internal */
    public function handleEnd()
    {
        if (!$this->closed) {
            if ($this->buffer === '') {
                $this->emit('end');
            } else {
                $this->emit('error', array(new \RuntimeException('Stream ended with incomplete control code sequence in buffer')));
            }
            $this->close();
        }
    }

    /** @internal */
    public function handleError(\Exception $e)
    {
        $this->emit('error', array($e));
        $this->close();
    }
}
