<?php

namespace Chillu\FakeDatabase;

use LogicException;

/**
 * Manages {@link FakeObject} instances in a table-like storage with arbitrary keys.
 * Enforces a unique identifier for each record, either manually set or created as a hash.
 * Since it stores arbitrary key/value data, its main purpose is to provide
 * lightweight object wrappers where no full ORM mapping exists, e.g. with transient
 * data stored in and retrieved from webservices.
 *
 * The database is designed to be persisted as a flat file via json_encode() and json_decode().
 */
class FakeDatabase
{
    public static $logging_enabled = true;

    /**
     * @var String Absolute path to the database file
     */
    protected $path;
    

    /**
     * @param String $path Absolute path to the database file
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        if (file_exists($this->path) && !is_readable($this->path)) {
            throw new LogicException(sprintf('FakeDatabase at %s is not readable', $this->path));
        }

        if (file_exists($this->path)) {
            $content = file_get_contents($this->path);
            return $content ? json_decode(file_get_contents($this->path), true) : array();
        } else {
            return array();
        }
    }

    /**
     * @param array $data Data to be persisted on disk
     */
    protected function setData($data)
    {
        if (file_exists($this->path) && !is_writable($this->path)) {
            // @codeCoverageIgnoreStart
            throw new LogicException(sprintf('FakeDatabase at %s is not writeable'. $this->path));
            // @codeCoverageIgnoreEnd
        }
        $old = umask(0);
        
        file_put_contents($this->path, json_encode($data, JSON_PRETTY_PRINT));

        chmod($this->path, 0777);
        umask($old);
    }

    /**
     * Finds a record matching a certain key/value set
     * Not a terribly efficient implementation, since it retrieves all records from this type
     * and searches in memory. Supports nested keys through dot notation.
     *
     * @param  String       $type
     * @param  String       $key  Supports dot notation
     * @param  String Value
     * @return FakeObject
     */
    public function find($type, $key, $value)
    {
        $data = $this->getData();
        $return = null;
        
        if (isset($data[$type])) {
            $keyParts = explode('.', $key);
            $records = $data[$type];
            
            foreach ($records as $recordKey => $record) {
                $compare = $record;
                
                foreach ($keyParts as $i => $keyPart) {
                    if ($i < count($keyParts)) {
                        if (isset($compare[$keyPart])) {
                            $compare = $compare[$keyPart];
                        } else {
                            continue;
                        }
                    }
                }
                
                if ($compare == $value) {
                    $return = FakeObject::createFromArray($record);
                }
            }
        } else {
            $return = false;
        }

        $this->log(
            sprintf(
                'Find fake %s#%s: %s',
                $type,
                $key,
                json_encode($return)
            )
        );

        return $return;
    }

    /**
     * Get a single record
     *
     * @param  String $type
     * @param  String $key
     * @return FakeObject
     */
    public function get($type, $key)
    {
        $data = $this->getData();
        $return = (isset($data[$type][$key])) ? FakeObject::createFromArray($data[$type][$key]) : null;
        
        $this->log(
            sprintf(
                'Get fake %s#%s: %s',
                $type,
                $key,
                json_encode($return)
            )
        );

        return $return;
    }

    /**
     * Get all records of a certain type.
     *
     * @param  String $type
     * @return array|bool Array of FakeObject records or false if nothing exists of given type
     */
    public function getAll($type)
    {
        $data = $this->getData();
        $return = array();
        
        if (!isset($data[$type])) {
            return false;
        }
        
        foreach ($data[$type] as $record) {
            $return[] = FakeObject::createFromArray($record);
        }

        return $return;
    }

    /**
     * Sets a new record. Replaces existing records with the same key.
     * Use {@link update()} for updating existing records.
     *
     * @param String     $type
     * @param String     $key
     * @param FakeObject $obj
     */
    public function set($type, $key, FakeObject $obj)
    {
        $data = $this->getData();
        
        if (!isset($data[$type])) {
            $data[$type] = array();
        }

        $obj->_key = $key;
        $record = $obj->toArray();
        $data[$type][(string)$key] = $record;
        $this->setData($data);

        $this->log(
            sprintf(
                'Set fake %s#%s: %s',
                $type,
                $key,
                json_encode($record)
            )
        );
    }

    /**
     * Updates an existing record. Merges with existing data.
     * Use {@link set()} to unset values.
     *
     * @param String     $type
     * @param String     $key
     * @param FakeObject $obj
     */
    public function update($type, $key, FakeObject $obj)
    {
        $existing = $this->get($type, $key);
        $data = $this->getData();
        
        $record = $existing ? array_merge($existing->toArray(), $obj->toArray()) : $obj->toArray();
        
        if (!isset($data[$type])) {
            $data[$type] = array();
        }
        
        $data[$type][$key] = $record;
        $this->setData($data);

        $this->log(
            sprintf(
                'Update fake %s#%s: %s',
                $type,
                $key,
                json_encode($record)
            )
        );
    }

    /**
     * Since stores (SQLite tables) aren't tracked reliably across processes,
     * we need to hard reset the DB and reinitialize it.
     */
    public function reset()
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        $this->log('Reset');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getData();
    }

    /**
     * @return String Absolute path to the database file
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param String $msg
     */
    protected function log($msg)
    {
        if (self::$logging_enabled) {
            syslog(LOG_DEBUG, $msg);
        }
    }
}
