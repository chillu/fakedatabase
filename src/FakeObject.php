<?php

namespace Chillu\FakeDatabase;

use \ArrayObject;

/**
 * Simple key/value map with defaults support.
 */
class FakeObject extends ArrayObject
{

    public function __construct($data = null, $flags = ArrayObject::ARRAY_AS_PROPS, $iteratorClass = 'ArrayIterator')
    {
        if (!$data) {
            $data = array();
        }
        
        parent::__construct($data, $flags, $iteratorClass);

        foreach ($this->getDefaults() as $k => $v) {
            if (!isset($this[$k])) {
                $this[$k] = $v;
            }
        }
    }
    
    public function getDefaults()
    {
        return array();
    }
    
    /**
     * Serialize object and contained objects an array,
     * in a format with "_type" hints, useful for later restoring
     * through {@link createFromArray()}.
     *
     * @see    http://stackoverflow.com/questions/6836592/serializing-php-object-to-json
     * @return array
     */
    public function toArray()
    {
        $array = (array)$this;
        $array['_type'] = get_class($this);
        
        array_walk_recursive(
            $array,
            function (&$property, $key) {
                if ($property instanceof FakeObject) {
                    $property = $property->toArray();
                }
            }
        );
        
        return $array;
    }

    /**
     * Create nested object representation from array,
     * based on "_type" hints.
     *
     * @param  array
     * @return FakeObject
     */
    public static function createFromArray($array)
    {
        // array_walk_recursive doesn't recurse into arrays...
        foreach ($array as &$v) {
            if (is_array($v)) {
                // Convert "has one" relations
                $v = FakeObject::createFromArray($v);
            }
        }
        
        $class = (isset($array['_type'])) ? $array['_type'] : FakeObject::class;
        unset($array['_type']);

        return new $class($array);
    }
}
