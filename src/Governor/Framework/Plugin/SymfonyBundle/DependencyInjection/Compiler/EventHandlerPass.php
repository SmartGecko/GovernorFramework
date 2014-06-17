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

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\EventHandling\Listeners\AnnotatedEventListener;

/**
 * Compiler pass that subscribes all services tagged with <code>governor.event_handler</code> 
 * to the event bus.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class EventHandlerPass extends AbstractHandlerPass
{

    public function process(ContainerBuilder $container)
    {
        $reader = new AnnotationReader();

        foreach ($container->findTaggedServiceIds('governor.event_handler') as $id => $attributes) {
            $busDefinition = $container->findDefinition(sprintf("governor.event_bus.%s",
                            isset($attributes['event_bus']) ? $attributes['event_bus']
                                        : 'default'));
                     
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();

            $reflClass = new \ReflectionClass($class);

            foreach ($reflClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $annot = $reader->getMethodAnnotation($method,
                        EventHandler::class);

                // not a handler
                if (null === $annot) {
                    continue;
                }

                $eventParam = current($method->getParameters());

                // event type must be typehinted
                if (!$eventParam->getClass()) {
                    continue;
                }

                $eventClassName = $eventParam->getClass()->name;
                $methodName = $method->name;
                $eventTarget = new Reference($id);
                $handlerId = $handlerId = $this->getHandlerIdentifier("governor.event_handler");

                $container
                    ->register($handlerId, AnnotatedEventListener::class)
                    ->addArgument($eventClassName)
                    ->addArgument($methodName)
                    ->addArgument($eventTarget)
                    ->setPublic(true)
                    ->setLazy(true);

                $busDefinition->addMethodCall('subscribe',
                        array(new Reference($handlerId)));                
            }
        }
    }

}
