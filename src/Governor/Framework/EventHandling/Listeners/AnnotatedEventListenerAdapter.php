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

namespace Governor\Framework\EventHandling\Listeners;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Common\ReflectionUtils;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\EventHandling\EventListenerProxyInterface;

/**
 * Description of AnnotatedEventListenerAdapter
 *
 * @author david
 */
class AnnotatedEventListenerAdapter implements EventListenerProxyInterface
{

    /**
     * @var EventBusInterface 
     */
    private $eventBus;
    private $annotatedEventListener;

    public function __construct($annotatedEventListener,
            EventBusInterface $eventBus)
    {
        $this->eventBus = $eventBus;
        $this->annotatedEventListener = $annotatedEventListener;

        $this->eventBus->subscribe($this);
    }

    public function getTargetType()
    {
        return get_class($this->annotatedEventListener);
    }

    public function handle(EventMessageInterface $event)
    {        
        $reader = new AnnotationReader();

        foreach (ReflectionUtils::getMethods(new \ReflectionClass($this->annotatedEventListener)) as $method) {
            $annot = $reader->getMethodAnnotation($method,
                    'Governor\Framework\Annotations\EventHandler');

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

            if ($eventClassName === $event->getPayloadType()) {
                $listener = new AnnotatedEventListener($eventClassName, $method->name, $this->annotatedEventListener);
                $listener->handle($event);
            }
        }
    }

}
