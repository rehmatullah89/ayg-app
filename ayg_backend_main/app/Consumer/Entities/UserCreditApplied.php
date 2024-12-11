<?php
namespace App\Consumer\Entities;


/**
 * Class UserCreditApplied
 * @package App\Consumer\Entities
 */
class UserCreditApplied extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var float
     */
    private $appliedInCents;
    /**
     * @var $user User
     */
    private $user;
    /**
     * @var $appliedToOrder Order
     */
    private $appliedToOrder;


    /**
     * UserCreditApplied constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->appliedInCents = $data['appliedInCents'];
        $this->user = $data['user'];
        $this->appliedToOrder = $data['appliedToOrder'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    // function called when encoded with json_encode

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return Order
     */
    public function getAppliedToOrder()
    {
        return $this->appliedToOrder;
    }

    /**
     * @param Order $appliedToOrder
     */
    public function setAppliedToOrder($appliedToOrder)
    {
        $this->appliedToOrder = $appliedToOrder;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return float
     */
    public function getCreditsInCents()
    {
        return $this->appliedInCents;
    }

    /**
     * @param float $appliedInCents
     */
    public function setCreditsInCents($appliedInCents)
    {
        $this->appliedInCents = $appliedInCents;
    }

    /**
     * @param string $id
     * @return UserCreditApplied
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}