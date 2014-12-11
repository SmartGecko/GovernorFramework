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

namespace Governor\Framework\Common\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Common\ReflectionUtils;

/**
 * Description of MethodMessageHandlerInspector
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class MethodMessageHandlerInspector
{

    /**
     * @var \ReflectionClass
     */
    private $targetClass;

    /**
     * @var string 
     */
    private $annotation;

    /**
     * @var array
     */
    private $handlers;

    function __construct(\ReflectionClass $targetClass, $annotation)
    {
        $this->handlers = array();
        $this->targetClass = $targetClass;
        $this->annotation = $annotation;

        $this->inspect();
    }

    private function inspect()
    {
        $reader = new AnnotationReader();

        foreach (ReflectionUtils::getMethods($this->targetClass) as $method) {
            $annotation = $reader->getMethodAnnotation($method,
                    $this->annotation);

            if (!$annotation) {
                continue;
            }

            $payloadType = $this->extractPayloadType($method);
            $methodAnnotations = $reader->getMethodAnnotations($method);

            if ($payloadType) {
                $this->handlers[] = new AnnotatedHandlerDefinition($this->targetClass,
                        $method, $methodAnnotations, $payloadType);
            }
        }
    }

    private function extractPayloadType(\ReflectionMethod $method)
    {
        $param = current($method->getParameters());

        if ($param->getClass()) {
            return $param->getClass()->name;
        }

        return null;
    }

    /**
     * @return HandlerDefinitionInterface[]
     */
    function getHandlerDefinitions()
    {
        return $this->handlers;
    }

}
