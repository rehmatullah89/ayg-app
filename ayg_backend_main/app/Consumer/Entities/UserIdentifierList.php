<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class UserIdentifierList implements \IteratorAggregate, \Countable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(UserIdentifier $userIdentifier)
    {
        $this->data[] = $userIdentifier;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getFirst():?UserIdentifier
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return $this->data[0];
    }

    public function getLast():?UserIdentifier
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return $this->data[count($this->data) - 1];
    }

    public function checkIfAllHasTheSameUserId()
    {
        $userIdList = [];
        /**
         * @var UserIdentifier $userIdentifier
         */
        foreach ($this->data as $userIdentifier) {
            $userIdList[$userIdentifier->getParseUserId()] = $userIdentifier->getParseUserId();
        }

        if (count($userIdList) > 1) {
            return false;
        }
        return true;
    }
}
