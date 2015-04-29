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

namespace Governor\Framework\Saga;

// !!! TODO move to bundle
use Governor\Framework\Annotations\Inject;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Common\ReflectionUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * An implementation of the ResourceInjectorInterface which uses the Symfony service container to inject resources.
 * It scans the Saga for methods annotated with an Inject annotation.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ContainerAwareResourceInjector implements ResourceInjectorInterface, ContainerAwareInterface
{

    /**
     * @var ContainerInterface Service container.
     */
    private $container;

    /**
     * @var AnnotationReader Annotation reader. 
     */
    private $reader;

    /**
     * Creates a new ContainerAwareResourceInjector.
     */
    public function __construct()
    {
        $this->reader = new AnnotationReader();
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

                $service = $this->container->get($annotation->service);
                $reflectionMethod->invokeArgs($saga, array($service));
            }
        }
    }

    /**
     * {@inheritDoc}     
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

}
