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

namespace Governor\Framework\EventHandling;

/**
 * ClusterMetaDataInterface implementation using a PHP array as storage.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class DefaultClusterMetaData implements ClusterMetaDataInterface
{
    /**
     * @var array
     */
    private $properties;

    public function __construct()
    {
        $this->properties = [];
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getProperty($key)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setProperty($key, $value)
    {
        if (null === $key) {
            throw new \InvalidArgumentException("null is not allowed as key");
        }

        $this->properties[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function removeProperty($key)
    {
        unset($this->properties[$key]);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isPropertySet($key)
    {
        return array_key_exists($key, $this->properties);
    }

}
