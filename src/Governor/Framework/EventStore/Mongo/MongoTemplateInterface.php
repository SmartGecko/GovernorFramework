<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\EventStore\Mongo;

use MongoCollection;

/**
 * Interface describing a mechanism that provides access to the Database and Collections required by the
 * MongoEventStore.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface MongoTemplateInterface
{

    /**
     * Returns a reference to the collection containing the domain events.
     *
     * @return MongoCollection containing the domain events
     */
    public function domainEventCollection();

    /**
     * Returns a reference to the collection containing the snapshot events.
     *
     * @return MongoCollection containing the snapshot events
     */
    public function snapshotEventCollection();
}