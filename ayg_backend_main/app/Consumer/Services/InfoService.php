<?php

namespace App\Consumer\Services;

use App\Consumer\Repositories\InfoRepositoryInterface;

class InfoService extends Service
{
    /**
     * @var InfoRepositoryInterface
     */
    private $infoParseRepository;

    public function __construct(InfoRepositoryInterface $infoParseRepository) {
        $this->infoParseRepository = $infoParseRepository;
    }

    public function getAirLines()
    {
        return $this->infoParseRepository->getAirLines();
    }
}
