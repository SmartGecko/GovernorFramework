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


namespace Governor\Framework\Common\Property;

/**
 * Description of PropertyAccessStrategyCollection
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class PropertyAccessStrategy
{
    /**
     * @var PropertyAccessStrategy
     */
    private static $strategy;

    /**
     * @param mixed $target
     * @param string $propertyName
     * @return PropertyInterface
     */
    public static function getProperty($target, $propertyName)
    {
        if (null === self::$strategy) {
            self::$strategy = new ReflectionPropertyAccessStrategy();
        }

        return self::$strategy->propertyFor($target, $propertyName);
    }

    /**
     * Returns a Property instance for the given <code>property</code>, defined in given
     * <code>targetClass</code>, or <code>null</code> if no such property is found on the class.
     *
     * @param mixed $targetClass The class on which to find the property
     * @param string $property The name of the property to find
     * @return PropertyInterface the Property instance providing access to the property value, or <code>null</code> if property could not
     * be found.
     */
    abstract protected function propertyFor($targetClass, $property);
}
