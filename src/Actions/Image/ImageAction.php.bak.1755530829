<?php

declare(strict_types=1);

namespace App\Actions\Image;

use Common\Actions\Action;
use App\Support\Config;
use Psr\Log\LoggerInterface;

abstract class ImageAction extends Action
{
    /** App config (typed) */
    protected Config $config;

    /**
     * Constructor injection (typed; no service-locator / container argument).
     */
    public function __construct(
        LoggerInterface $logger,
        Config $config
    ) {
        parent::__construct($logger);
        $this->config = $config;
    }
}
