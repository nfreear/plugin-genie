<?php namespace IET_OU\SubClasses;

/**
 * OffsetIterator utility class.
 *
 * @copyright 2015 The Open University.
 * @author  N.D.Freear, 23 May 2015.
 * @link    https://gist.github.com/nfreear/72a3a62b8ac810ea4c49
 * @link    http://php.net/manual/en/class.iterator.php
 */

class OffsetIterator implements \Iterator
{

    private $offset;
    private $position;
    private $array = array();

    public function __construct($array, $offset = 0)
    {
        $this->position = $this->offset = $offset;
        $this->array = $array;
    }

    public function rewind()
    {
        $this->position = $this->offset;
    }

    public function current()
    {
        #var_dump(__METHOD__, $this->position);
        return $this->array[ $this->position ];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->array[ $this->position ]);
    }
}
