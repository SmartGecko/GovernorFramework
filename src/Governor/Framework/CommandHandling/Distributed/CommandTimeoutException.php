<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\CommandHandling\Distributed;


class CommandTimeoutException extends \Exception
{
    /**
     * @param string $commandIdentifier
     * @param int $timeout
     */
    public function __construct($commandIdentifier, $timeout = 10)
    {
        parent::__construct(
            sprintf('Timeout [%d] reached while waiting for command reply [%s] ', $timeout, $commandIdentifier)
        );
    }
}