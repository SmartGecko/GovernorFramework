<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Saga\Repository\Mongo;

/**
 * <p>Generic template for accessing Mongo for the axon sagas.</p>
 * <p/>
 * <p>You can ask for the collection of domain events as well as the collection of sagas and association values. We
 * use the mongo client mongo-java-driver. This is a wrapper around the standard mongo methods. For convenience the
 * interface also gives access to the database that contains the axon saga collections.</p>
 * <p/>
 * <p>Implementations of this interface must provide the connection to Mongo.</p>
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface MongoTemplateInterface {
    /**
     * Returns a reference to the collection containing the saga instances.
     *
     * @return \MongoCollection containing the sagas
     */
    public function sagaCollection();

}