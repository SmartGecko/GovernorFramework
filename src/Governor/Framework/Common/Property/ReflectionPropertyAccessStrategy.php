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
 * PropertyAccessStrategy implementation using reflection to find the suitable property.
 * This implementation will first try to find the property directly by looking for a class property by its name.
 * Then it will try if any methods with the signature [get,is,has]propertyName exist and use the first it will found.
 * If there is no match a null will be returned.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class ReflectionPropertyAccessStrategy extends PropertyAccessStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function propertyFor($targetClass, $property)
    {
        $reflectionClass = new \ReflectionClass($targetClass);

        if ($reflectionClass->hasProperty($property)) {
            return new ReflectionPropertyImpl($reflectionClass->getProperty($property));
        }

        foreach (array('get', 'is', 'has') as $prefix) {
            $methodName = sprintf('%s%s', $prefix, ucfirst($property));

            foreach ($reflectionClass->getMethods() as $method) {
                if (0 === strcmp($method->getName(), $methodName)) {
                    return new ReflectionMethodImpl($method);
                }
            }
        }

        return null;
    }

}