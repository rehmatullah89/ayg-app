<?php
namespace App\Background\Entities;


class DeliveryUserUpdateStats extends Entity
{

    /**
     * @var string
     */
    private $airportIataCode;
    /**
     * @var string
     */
    private $email;
    /**
     * @var int
     */
    private $updated;
    /**
     * @var int
     */
    private $inserted;

    public function __construct(string $airportIataCode, string $email, bool $updated, bool $inserted)
    {
        $this->airportIataCode = $airportIataCode;
        $this->email = $email;
        $this->updated = $updated;
        $this->inserted = $inserted;
    }

    /**
     * @return string
     */
    public function getAirportIataCode(): string
    {
        return $this->airportIataCode;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function getUpdated(): bool
    {
        return $this->updated;
    }

    /**
     * @return bool
     */
    public function getInserted(): bool
    {
        return $this->inserted;
    }

    public function toString(): string
    {
        $action = '';
        if ($this->updated) {
            $action = 'updated';
        }
        if ($this->inserted) {
            $action = 'inserted';
        }

        return 'User from airport ' . $this->getAirportIataCode() . ' with email ' . $this->getEmail() . ' has been ' . $action;
    }
}
