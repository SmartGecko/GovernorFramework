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

use Governor\Framework\Common\Annotation\AnnotationReaderFactoryInterface;
use Governor\Framework\Annotations\CommandHandler;
use Governor\Framework\Common\ParameterResolverFactoryInterface;
use Governor\Framework\Common\Annotation\MethodMessageHandlerInspector;
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
     * @var CommandTargetResolverInterface
     */
    private $targetResolver;

    /**
     * @param string $className
     * @param string $methodName
     * @param ParameterResolverFactoryInterface $parameterResolver
     * @param RepositoryInterface $repository
     * @param CommandTargetResolverInterface $targetResolver
     * @param AnnotationReaderFactoryInterface $annotationReaderFactory
     */
    public function __construct(
        $className,
        $methodName,
        ParameterResolverFactoryInterface $parameterResolver,
        RepositoryInterface $repository,
        CommandTargetResolverInterface $targetResolver = null,
        AnnotationReaderFactoryInterface $annotationReaderFactory = null
    ) {
        parent::__construct($className, $methodName, $parameterResolver, $annotationReaderFactory);
        $this->repository = $repository;

        $this->targetResolver = null === $targetResolver ? new AnnotationCommandTargetResolver($annotationReaderFactory)
            : $targetResolver;
    }

    public function handle(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork
    ) {
        if ($this->getMethod()->isConstructor()) {
            $this->handleConstructor($commandMessage, $unitOfWork);

            return null;
        }

        return $this->handleMethod($commandMessage, $unitOfWork);
    }

    private function handleConstructor(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork
    ) {
        $reflectionClass = $this->getMethod()->getDeclaringClass();
        $arguments = $this->resolveArguments($commandMessage);

        $object = $reflectionClass->newInstanceArgs($arguments);

        $this->repository->add($object);
    }

    private function handleMethod(
        CommandMessageInterface $commandMessage,
        UnitOfWorkInterface $unitOfWork
    ) {
        $versionedId = $this->targetResolver->resolveTarget($commandMessage);
        $aggregate = $this->repository->load(
            $versionedId->getIdentifier(),
            $versionedId->getVersion()
        );

        $arguments = $this->resolveArguments($commandMessage);

        return $this->getMethod()->invokeArgs($aggregate, $arguments);
    }

    /**
     * @param string $className
     * @param RepositoryInterface $repository
     * @param CommandBusInterface $commandBus
     * @param ParameterResolverFactoryInterface $parameterResolver
     * @param CommandTargetResolverInterface $targetResolver
     * @param AnnotationReaderFactoryInterface $annotationReaderFactory
     */
    public static function subscribe(
        $className,
        RepositoryInterface $repository,
        CommandBusInterface $commandBus,
        ParameterResolverFactoryInterface $parameterResolver,
        CommandTargetResolverInterface $targetResolver = null,
        AnnotationReaderFactoryInterface $annotationReaderFactory = null
    ) {
        $inspector = new MethodMessageHandlerInspector(
            $annotationReaderFactory,
            new \ReflectionClass($className),
            CommandHandler::class
        );

        foreach ($inspector->getHandlerDefinitions() as $handlerDefinition) {
            $handler = new AnnotatedAggregateCommandHandler(
                $className,
                $handlerDefinition->getMethod()->name, $parameterResolver,
                $repository, $targetResolver, $annotationReaderFactory
            );

            $commandBus->subscribe(
                $handlerDefinition->getPayloadType(),
                $handler
            );
        }
    }

}
