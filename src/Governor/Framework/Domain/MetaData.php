<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Domain;

/**
 * Description of MetaData
 *
 * @author david
 */
class MetaData implements \IteratorAggregate, \Countable
{

    private static $emptyInstance;

    const METADATA_IMMUTABLE = 'The MetaData object is immutable';

    /**
     * Metadata storage.
     * 
     * @var array
     */
    private $metadata = array();

    /**
     * Constructor.
     *     
     * @param array $metadata
     */
    public function __construct(array $metadata = array())
    {
        $this->metadata = $metadata;
    }

    /**
     * Returns the metadata.
     *
     * @return array An array of metadata
     *
     * @api
     */
    public function all()
    {
        return $this->metadata;
    }

    /**
     * Returns the metadata keys.
     *
     * @return array An array of metadata keys
     *
     * @api
     */
    public function keys()
    {
        return array_keys($this->metadata);
    }

    /**
     * Returns a metadadta.
     *
     * @param string $key    The metadadta key 
     *
     * @return mixed
     */
    public function get($key)
    {
        return array_key_exists($key, $this->metadata) ? $this->metadata[$key] : null;
    }

    /**
     * Adds metadata.
     *
     * @param array $metadata An array of metadata
     * @return \Governor\Framework\Domain\MetaData
     */
    public function mergeWith(array $metadata = array())
    {
        if (empty($metadata)) {
            return $this;
        }

        return new MetaData(array_replace($this->metadata, $metadata));
    }

    public function withoutKeys(array $keys = array())
    {
        if (empty($keys)) {
            return $this;
        }

        $newMetadata = $this->metadata;

        foreach ($keys as $key) {
            if (isset($newMetadata[$key])) {
                unset($newMetadata[$key]);
            }
        }

        return new MetaData($newMetadata);
    }

    public function isEmpty()
    {
        return empty($this->metadata);
    }

    /**
     * Returns true if the key is defined.
     *
     * @param string $key The key
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     * @api
     */
    public function has($key)
    {
        return array_key_exists($key, $this->metadata);
    }

    /**
     * Returns the number of metadata entries.
     * 
     * @return integer Element count
     */
    public function count()
    {
        return count($this->metadata);
    }

    /**
     * Returns an iterator,
     * 
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->metadata);
    }

    public static function emptyInstance()
    {
        if (!isset(self::$emptyInstance)) {
            self::$emptyInstance = new MetaData();
        }

        return self::$emptyInstance;
    }

    public function isEqualTo($other)
    {       
        if (is_array($other)) {
            return $this->metadata == $other;
        }

        if (is_object($other)) {
            return $this == $other;
        }

        return false;
    }

}
