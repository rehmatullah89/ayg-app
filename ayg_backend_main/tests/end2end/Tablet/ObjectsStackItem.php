<?php

/**
 * Class ObjectsStackItem
 */
class ObjectsStackItem
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var int
     */
    private $className;

    /**
     * TestResponse constructor.
     * @param $id
     * @param $className
     */
    public function __construct($id, $className)
    {
        $this->id = $id;
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getClassName()
    {
        return $this->className;
    }

}