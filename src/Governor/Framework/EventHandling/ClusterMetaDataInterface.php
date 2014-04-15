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
 * Allows arbitrary information to be attached to a cluster.
 */
interface ClusterMetaDataInterface
{

    /**
     * Returns the property stored using the given {@code key}. If no property has been stored using that key, it
     * returns {@code null}.
     *
     * @param key The key under which the property was stored
     * @return The value stored under the given {@code key}
     */
    public function getProperty($key);

    /**
     * Stores a property {@code value} under the given {@code key}.
     *
     * @param key   the key under which to store a value
     * @param value the value to store
     */
    public function setProperty($key, $value);

    /**
     * Removes the value store under the given {@code key}. If no such value is available, nothing happens.
     *
     * @param key the key to remove
     */
    public function removeProperty($key);

    /**
     * Indicates whether a value is stored under the given {@code key}. Will also return {@code true} if a {@code null}
     * value is stored under the given {@code key}.
     *
     * @param key The key to find
     * @return {@code true} if a value was stored under the given {@code key}, otherwise {@code false}.
     */
    public function isPropertySet($key);
}
