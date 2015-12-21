<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Cluster\Worker;


interface WorkerInterface
{

    /**
     * Starts the worker.
     */
    public function start();

    /**
     * Stops the worker.
     */
    public function stop();

    /**
     * Performs a worker restart.
     */
    public function restart();

}