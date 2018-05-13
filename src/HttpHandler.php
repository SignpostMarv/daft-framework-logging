<?php
/**
* @author SignpostMarv
*/
declare(strict_types=1);

namespace SignpostMarv\DaftFramework\Logging;

use Psr\Log\LoggerInterface;
use SignpostMarv\DaftFramework\HttpHandler as Base;

class HttpHandler extends Base
{
    use Logger;

    public function __construct(
        LoggerInterface $logger,
        string $baseUrl,
        string $basePath,
        array $config = []
    ) {
        parent::__construct($baseUrl, $basePath, $config);

        $this->logger = $logger;
    }
}