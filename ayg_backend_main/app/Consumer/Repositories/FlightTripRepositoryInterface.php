<?php

namespace App\Consumer\Repositories;

interface FlightTripRepositoryInterface
{
    public function switchFlightTripOwner(string $fromUserId, string $toUserUserId): void;
}
