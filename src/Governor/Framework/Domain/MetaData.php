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

namespace Governor\Framework\Domain;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Exclude;

/**
 * The object holds meta data information attached to a {@see MessageInterface}
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class MetaData implements \IteratorAggregate, \Countable
{

    /**
     * @Exclude
     * @var MetaData 
     */
    private static $emptyInstance;

    const METADATA_IMMUTABLE = 'The MetaData object is immutable';

    /**
     * Metadata storage.
     *
     * @Type ("array")
     * @var array
     */
    private $metadata = [];

    /**
     * Constructor.
     *     
     * @param array $metadata
     */
    public function __construct(array $metadata = [])
    {
        $this->metadata = $metadata;
    }

    /**
     * Returns the metadata.
     *
     * @return array An array of metadata
     *
     * @api
     */
    public function all()
    {
        return $this->metadata;
    }

    /**
     * Returns the metadata keys.
     *
     * @return array An array of metadata keys
     *
     * @api
     */
    public function keys()
    {
        return array_keys($this->metadata);
    }

    /**
     * Returns a metadadta.
     *
     * @param string $key    The metadadta key 
     *
     * @return mixed
     */
    public function get($key)
    {
        return isset($this->metadata[$key]) ? $this->metadata[$key] : null;
    }

    /**
     * Adds metadata.
     *
     * @param array $metadata An array of metadata
     * @return \Governor\Framework\Domain\MetaData
     */
    public function mergeWith(array $metadata = [])
    {
        if (empty($metadata)) {
            return $this;
        }

        return new MetaData(array_replace($this->metadata, $metadata));
    }

    /**
     * 
     * @param array $keys
     * @return \Governor\Framework\Domain\MetaData
     */
    public function withoutKeys(array $keys = [])
    {
        if (empty($keys)) {
            return $this;
        }

        $newMetadata = $this->metadata;

        foreach ($keys as $key) {
            if (isset($newMetadata[$key])) {
                unset($newMetadata[$key]);
            }
        }

        return new MetaData($newMetadata);
    }

    /**
     * 
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->metadata);
    }

    /**
     * Returns true if the key is defined.
     *
     * @param string $key The key
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     * @api
     */
    public function has($key)
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Returns the number of metadata entries.
     * 
     * @return integer Element count
     */
    public function count()
    {
        return count($this->metadata);
    }

    /**
     * Returns an iterator,
     * 
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->metadata);
    }

    /**
     * 
     * @return MetaData
     */
    public static function emptyInstance()
    {
        if (!isset(self::$emptyInstance)) {
            self::$emptyInstance = new MetaData();
        }

        return self::$emptyInstance;
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function isEqualTo($other)
    {       
        if (is_array($other)) {
            return $this->metadata == $other;
        }

        if (is_object($other)) {
            return $this == $other;
        }

        return false;
    }

}
