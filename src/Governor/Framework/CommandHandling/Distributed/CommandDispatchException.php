<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\CommandHandling\Distributed;


class CommandDispatchException extends \Exception
{
    /**
     * @param string $message
     * @param \Exception $previous
     */
    public function __construct($message = "", \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

}