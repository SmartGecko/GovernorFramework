<?php
/**
 * This file is part of the SmartGecko(c) business platform.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Governor\Framework\Common\Mongo;

use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Common\Logging\NullLogger;
use Psr\Log\LoggerInterface;

/**
 * Abstract implementation for Mongo templates. Mongo templates give access to the collections in a Mongo Database used
 * by components of the Axon Framework. The AuthenticatingMongoTemplate takes care of the authentication against the
 * Mongo database.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AuthenticatingMongoTemplate implements LoggerAwareInterface
{

    const DEFAULT_GOVERNOR_DATABASE = "governor";

    /**
     * @var
     */
    private $logger;

    /**
     * @var \MongoDB
     */
    private $database;


    /**
     * Initializes the MongoTemplate to connect to the database with given
     * <code>databaseName</code>. The given <code>userName</code> and <code>password</code>, when not
     * <code>null</code>, are used to authenticate against the database.
     *
     * @param string $server The MongoDB connect string
     * @param string $databaseName The name of the database
     * @param string $authenticationDatabaseName The name of the database containing the user to authenticate
     */
    public function __construct($server, $databaseName, $authenticationDatabaseName = null)
    {
        $this->logger = new NullLogger();
        $options = [];

        if (null !== $authenticationDatabaseName) {
            $options['authSource'] = $authenticationDatabaseName;
        }

        $client = new \MongoClient($server, $options);
        $this->database = $client->selectDB($databaseName);
    }

    /**
     * Returns a reference to the Database with the configured database name. If a username and/or password have been
     * provided, these are used to authenticate against the database.
     * <p/>
     * Note that the configured <code>userName</code> and <code>password</code> are ignored if the database is already
     * in an authenticated state.
     *
     * @return \MongoDB DB instance, referring to the database with configured name.
     */
    protected function getDatabase()
    {
        return $this->database;
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


}