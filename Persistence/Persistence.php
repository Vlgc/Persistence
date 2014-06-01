<?php
/**
 * Copyright (c) 2014 Vlgc <vlgc@system-lords.de>
 * All rights reserved.
 *
 * @package   Krpano
 * @author    Vlgc <vlgc@system-lords.de>
 * @copyright Vlgc <vlgc@system-lords.de>, All rights reserved
 * @license   BSD License
 */
namespace Vlgc\Persistence;

class Persistence implements \SplSubject
{
    /**
     * @var \PDO
     */
    protected $_pdo;

    /**
     * @var \SplObjectStorage
     */
    protected $_observer;

    /**
     * @var \PDOStatement
     */
    protected $_statement;

    /**
     * @var object
     */
    protected $_exception;

    /**
     * @param \PDO $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->_observer = new \SplObjectStorage();
        $this->_pdo      = $pdo;
    }

    /**
     * Attach $observer
     *
     * @return void
     */
    public function attach(\SplObserver $observer)
    {
        $this->_observer->attach($observer);
    }

    /**
     * Detach $observer
     *
     * @return void
     */
    public function detach(\SplObserver $observer)
    {
        $this->_observer->detach($observer);
    }

    public function notify()
    {
        foreach ($this->_observer as $observer) {
            $observer->update($this);
        }
    }

    /**
     * Get
     *
     * @return mixed $exception
     */
    public function get()
    {
        return $this->_exception;
    }

    /**
     * Prepare $sql
     *
     * @param string $sql
     * @return void
     */
    public function prepare($sql)
    {
        try {
            $this->_statement = $this->_pdo->prepare($sql);
        } catch (\PDOException $exception) {
            $this->_exception = $exception;
            $this->notify();
        }
    }

    /**
     * Bind $value to $parameter with $dataType
     *
     * @param mixed $parameter
     * @param mixed $value
     * @param int $dataType [default: \PDO::PARAM_STR]
     * @return void
     */
    public function bindValue($parameter, $value, $dataType = \PDO::PARAM_STR)
    {
        try {
            $this->_statement->bindValue($parameter, $value, $dataType);
        } catch (\PDOException $exception) {
            $this->_exception = $exception;
            $this->notify();
        }
    }

    /**
     * Execute
     *
     * @return boolean $status
     */
    public function execute()
    {
        $status               = false;
        try {
            $status           = $this->_statement->execute();
            if (!$status) {
                $this->_statement->closeCursor();
                throw new \PDOException(
                    json_encode($this->_statement->errorInfo()),
                    400
                );
            }
        } catch (\PDOException $exception) {
            $this->_exception = $exception;
            $this->notify();
        }
    }

    /**
     * Fetch $result as AssocArray
     *
     * @return array $result
     */
    public function fetchAssoc()
    {
        try {
            $result           = $this->_statement->fetchAll(\PDO::FETCH_ASSOC);
            $this->_statement->closeCursor();
        } catch (\PDOException $exception) {
            $this->_exception = $exception;
            $this->notify();
        }

        if (is_array($result)) {
            return $result;
        }

        return array();
    }

    /**
     * Create a query string for InArray queries
     *
     * @param array $array
     * @return string $placeholder
     */
    public function getInArrayQueryString(array $array)
    {
        return implode(',', array_fill(0, count($array), '?'));
    }

    /**
     * Get the last insertId
     *
     * @return int $lastInsertId
     */
    public function lastInsertId()
    {
        $lastInsertId     = 0;
        try {
            $lastInsertId = $this->_pdo->lastInsertId();
        } catch (\PDOException $exception) {
            $this->_exception = $exception;
            $this->notify();
        }

        return $lastInsertId;
    }
}
