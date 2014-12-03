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

namespace Governor\Framework\Test;

use Governor\Framework\Annotations\Inject;
use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Domain\AggregateRootInterface;
use Governor\Framework\Domain\ResourceInjectorInterface;

/**
 * Description of FixtureResourceInjector
 *
 * @author david
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
     */
    public function __construct()
    {
        $this->reader = new AnnotationReader();
        $this->resources = array();
    }

    public function injectResources(AggregateRootInterface $aggregate)
    {
        $reflClass = ReflectionUtils::getClass($aggregate);

        foreach (ReflectionUtils::getMethods($reflClass) as $reflMethod) {
            if (null !== $annot = $this->reader->getMethodAnnotation($reflMethod,
                    Inject::class)) {
                
                if (array_key_exists($annot->service, $this->resources)) {
                    $service = $this->resources[$annot->service];
                    $reflMethod->invokeArgs($aggregate, array($service));
                }
            }
        }
    }

    public function registerResource($id, $resource)
    {
        $this->resources[$id] = $resource;
    }

}
