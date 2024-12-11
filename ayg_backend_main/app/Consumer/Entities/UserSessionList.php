<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class UserSessionList implements \IteratorAggregate, \Countable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(UserSession $userSession)
    {
        $this->data[] = $userSession;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getFirst():?UserSession
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return $this->data[0];
    }

    /**
     * @var $item UserSession
     */
    public function getTokens():array{
        $list = [];

        foreach($this->data as $item){
            $list[] = rtrim($item->getToken(),'-c');
        }
        return $list;
    }
}
