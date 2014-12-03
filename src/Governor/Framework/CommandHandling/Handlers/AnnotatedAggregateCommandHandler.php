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

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Domain\ResourceInjectorInterface;
use Governor\Framework\CommandHandling\CommandBusInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandTargetResolverInterface;
use Governor\Framework\CommandHandling\AnnotationCommandTargetResolver;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\Repository\RepositoryInterface;

/**
 * Description of AggregateCommandHandler
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class AnnotatedAggregateCommandHandler extends AbstractAnnotatedCommandHandler
{

    /**
     * @var RepositoryInterface 
     */
    private $repository;

    /**
     * @var string 
     */
    private $aggregateType;

    /**
     * @var CommandTargetResolverInterface 
     */
    private $targetResolver;
    
    /**     
     * @var ResourceInjectorInterface
     */
    private $resourceInjector;

    public function __construct($commandName, $methodName, $aggregateType,
            RepositoryInterface $repository,
            CommandTargetResolverInterface $targetResolver = null)
    {
        parent::__construct($commandName, $methodName);
        $this->repository = $repository;
        $this->aggregateType = $aggregateType;
        $this->targetResolver = null === $targetResolver ? new AnnotationCommandTargetResolver()
                    : $targetResolver;
    }

    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork)
    {
        $this->verifyCommandMessage($commandMessage);

        switch ($this->methodName) {
            case '__construct':
                $this->handleConstructor($commandMessage, $unitOfWork);
                break;
            default:
                return $this->handleMethod($commandMessage, $unitOfWork);
        }
    }

    private function handleConstructor(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork)
    {
        $reflectionClass = new \ReflectionClass($this->aggregateType);        
        $instance = $reflectionClass->newInstanceWithoutConstructor();
        
        if ($this->resourceInjector) {
            $this->resourceInjector->injectResources($instance);
        }
        
        $constructor = $reflectionClass->getConstructor();
        call_user_func_array(array($instance, $constructor->getName()), array($commandMessage->getPayload()));        

        $this->repository->add($instance);
    }

    private function handleMethod(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork)
    {
        $versionedId = $this->targetResolver->resolveTarget($commandMessage);
        $aggregate = $this->repository->load($versionedId->getIdentifier(),
                $versionedId->getVersion());

        $reflectionMethod = new \ReflectionMethod($aggregate, $this->methodName);
        return $reflectionMethod->invokeArgs($aggregate,
                        array($commandMessage->getPayload()));
    }

    public static function subscribe($className,
            RepositoryInterface $repository, CommandBusInterface $commandBus)
    {
        $reflClass = new \ReflectionClass($className);
        $reader = new AnnotationReader();

        // !!! TODO one reflection scanner
        foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $annot = $reader->getMethodAnnotation($method,
                    \Governor\Framework\Annotations\CommandHandler::class);

            // not a handler
            if (null === $annot) {
                continue;
            }

            $commandParam = current($method->getParameters());

            // command type must be typehinted
            if (!$commandParam->getClass()) {
                continue;
            }

            $commandClassName = $commandParam->getClass()->name;
            $methodName = $method->name;

            $handler = new AnnotatedAggregateCommandHandler($commandClassName,
                    $methodName, $reflClass->getName(), $repository);

            $commandBus->subscribe($commandClassName, $handler);
        }
    }

    /**     
     * @param \Governor\Framework\Domain\ResourceInjectorInterface $resourceInjector
     */
    public function setResourceInjector(ResourceInjectorInterface $resourceInjector)
    {
        $this->resourceInjector = $resourceInjector;
    }

}
