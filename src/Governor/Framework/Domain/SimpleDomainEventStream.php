<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of SimpleDomainEventStream
 *
 * @author david
 */
class SimpleDomainEventStream implements DomainEventStreamInterface
{

    private $events;
    private $nextIndex;

    public function __construct(array $events = array())
    {
        $this->events = $events;
        $this->nextIndex = 0;
    }

    public function hasNext()
    {
        return count($this->events) > $this->nextIndex;
    }

    public function next()
    {
        if (!$this->hasNext()) {
            throw new \OutOfBoundsException('Trying to peek beyond the limits of this stream');
        }

        return $this->events[$this->nextIndex++];
    }

    public function peek()
    {
        if (!$this->hasNext()) {
            throw new \OutOfBoundsException('Trying to peek beyond the limits of this stream');
        }

        return $this->events[$this->nextIndex];
    }

    public static function emptyStream()
    {
        return new static();
    }

}
