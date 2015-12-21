<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
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