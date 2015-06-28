<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Saga\Repository\Mongo;

use MongoCollection;
use Governor\Framework\Common\Mongo\AuthenticatingMongoTemplate;

/**
 * Default implementation for the {@link MongoTemplate}. This implementation requires access to the configured {@link
 * Mongo} object. You can influence the names of the collections used to store the events as well as the
 * snapshot events.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultMongoTemplate extends AuthenticatingMongoTemplate implements MongoTemplateInterface
{

    const DEFAULT_SAGAS_COLLECTION_NAME = "sagas";

    /**
     * @var string
     */
    private $sagasCollectionName;


    /**
     * Creates a template connecting to given <code>mongo</code> instance, and loads events in the collection with
     * given <code>domainEventsCollectionName</code> and snapshot events from <code>snapshotEventsCollectionName</code>,
     * in a database with given <code>databaseName</code>. When not <code>null</code>, the given <code>userName</code>
     * and <code>password</code> are used to authenticate against the database.
     *
     * @param string $server The Mongo instance configured to connect to the Mongo Server
     * @param string $databaseName The name of the database containing the data
     * @param string $authenticationDatabaseName The name of the database to authenticate to
     * @param string $sagasCollectionName The name of the collection containing sagas
     */
    public function __construct(
        $server,
        $databaseName,
        $authenticationDatabaseName = null,
        $sagasCollectionName = self::DEFAULT_SAGAS_COLLECTION_NAME
    ) {
        parent::__construct($server, $databaseName, $authenticationDatabaseName);
        $this->sagasCollectionName = $sagasCollectionName;


    }

    /**
     * Returns a reference to the collection containing the sagas.
     *
     * @return MongoCollection containing the domain events
     */
    public function sagaCollection()
    {
        return $this->getDatabase()->selectCollection($this->sagasCollectionName);
    }


}