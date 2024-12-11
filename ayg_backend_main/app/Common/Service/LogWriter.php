<?php

namespace App\Common\Service;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Logger;

class LogWriter
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var string
     */
    private $envCode;

    public function __construct()
    {
        $output = "%channel%.%level_name%: %message%";
        $formatter = new LineFormatter($output);

        $this->logger = new Logger('log');
        $sysLogHandler = new SyslogUdpHandler("logs3.papertrailapp.com", 46487);

        $sysLogHandler->setFormatter($formatter);
        $this->logger->pushHandler($sysLogHandler);
        //$this->envCode = $envCode;
    }

    public function write($message)
    {
        // check if message already contains Sherpa part, if not add Error one
        $possiblePrefixes = [
            'Sherpa-Error-Critical',
            'Sherpa-Error-Fatal',
            'Sherpa-Warning',
            'Sherpa-Info-Notification'
        ];

        $found = false;
        foreach ($possiblePrefixes as $possiblePrefix) {
            if (strpos($message, $possiblePrefix) !== false) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            //$message = ':::: Sherpa-Error-Critical :: (' . $this->envCode . ') :: ' . $message;
        }

        try {
            $this->logger->addRecord(Logger::INFO, $message);
        }catch (\Exception $exception){
            var_dump($exception->getMessage());
            var_dump($exception->getCode());
            var_dump($exception->getFile());
            var_dump($exception->getTraceAsString());
        }
    }
}
