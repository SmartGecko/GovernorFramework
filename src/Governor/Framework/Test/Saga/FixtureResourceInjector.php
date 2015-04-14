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

namespace Governor\Framework\Test\Saga;

use Governor\Framework\Annotations\Inject;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Saga\ResourceInjectorInterface;


/**
 * An implementation of the ResourceInjectorInterface for the fixture testing mechanism.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */

class FixtureResourceInjector implements ResourceInjectorInterface
{

    /**
     * @var array
     */
    private $resources;

    /**
     * @var AnnotationReader Annotation reader.
     */
    private $reader;

    /**
     * Creates a new ContainerAwareResourceInjector.
     * @param array $resources
     */
    public function __construct(array &$resources = array())
    {
        $this->reader = new AnnotationReader();
        $this->resources = &$resources;
    }

    /**
     * {@inheritDoc}
     */
    public function injectResources(SagaInterface $saga)
    {
        $reflectionClass = ReflectionUtils::getClass($saga);

        foreach (ReflectionUtils::getMethods($reflectionClass) as $reflectionMethod) {
            if (null !== $annotation = $this->reader->getMethodAnnotation($reflectionMethod,
                    Inject::class)) {

                if (!array_key_exists($annotation->service, $this->resources)) {
                    throw new \RuntimeException(sprintf("Resource id \"%s\" is not registered", $annotation->service));
                }

                $reflectionMethod->invokeArgs($saga, array($this->resources[$annotation->service]));
            }
        }
    }

}