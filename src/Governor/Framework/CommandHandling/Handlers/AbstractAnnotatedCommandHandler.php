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

namespace Governor\Framework\CommandHandling\Handlers;

use Governor\Framework\Common\Annotation\SimpleAnnotationReaderFactory;
use Governor\Framework\Common\Annotation\AnnotationReaderFactoryInterface;
use Governor\Framework\Common\ParameterResolverFactoryInterface;
use Governor\Framework\Common\PayloadParameterResolver;
use Governor\Framework\Domain\MessageInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;

/**
 * Description of AbstractAnnotatedCommandHandler
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class AbstractAnnotatedCommandHandler implements CommandHandlerInterface
{

    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * @var array
     */
    private $annotations;

    /**
     * @var ParameterResolverFactoryInterface
     */
    private $parameterResolver;

    /**
     * @var AnnotationReaderFactoryInterface
     */
    private $annotationReaderFactory;

    /**
     * @param string $className
     * @param string $methodName
     * @param ParameterResolverFactoryInterface $parameterResolver
     * @param AnnotationReaderFactoryInterface $annotationReaderFactory
     */
    function __construct(
        $className,
        $methodName,
        ParameterResolverFactoryInterface $parameterResolver,
        AnnotationReaderFactoryInterface $annotationReaderFactory = null
    ) {
        $this->method = new \ReflectionMethod($className, $methodName);

        $this->annotationReaderFactory = null === $annotationReaderFactory ? new SimpleAnnotationReaderFactory(
        ) : $annotationReaderFactory;

        $this->annotations = $this->annotationReaderFactory->getReader()->getMethodAnnotations($this->method);
        $this->parameterResolver = $parameterResolver;
    }

    /**
     * @return \ReflectionMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * @return ParameterResolverFactoryInterface
     */
    public function getParameterResolver()
    {
        return $this->parameterResolver;
    }

    /**
     * @param MessageInterface $message
     * @return array
     */
    protected function resolveArguments(MessageInterface $message)
    {
        $arguments = [];
        $parameters = $this->method->getParameters();
        $count = count($parameters);

        for ($cc = 0; $cc < $count; $cc++) {
            if ($cc === 0) {
                $resolver = new PayloadParameterResolver($message->getPayloadType());
                $arguments[] = $resolver->resolveParameterValue($message);
            } else {
                $resolver = $this->parameterResolver->createInstance(
                    $this->annotations,
                    $parameters[$cc]
                );

                $arguments[] = $resolver->resolveParameterValue($message);
            }
        }

        return $arguments;
    }

}
