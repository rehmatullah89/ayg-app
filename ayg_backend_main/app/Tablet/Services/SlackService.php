<?php
namespace App\Tablet\Services;
use App\Tablet\Dtos\SlackMessage;
use App\Tablet\Exceptions\Exception;
use Httpful\Request;

/**
 * Class SlackService
 * @package App\Tablet\Services
 */
class SlackService extends Service
{
    protected $url;
    protected $identifier;

    public function __construct($url, $identifier)
    {
        $this->url = $url;
        $this->identifier = $identifier;
    }

}