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

use Governor\Framework\Common\Annotation\AnnotationReaderFactoryInterface;
use Governor\Framework\Common\Annotation\MethodMessageHandlerInspector;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\EventHandling\EventBusInterface;
use Governor\Framework\EventHandling\EventListenerProxyInterface;
use Governor\Framework\EventHandling\Replay\ReplayAwareInterface;
use Governor\Framework\Annotations\EventHandler;

/**
 * Description of AnnotatedEventListenerAdapter
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AnnotatedEventListenerAdapter implements EventListenerProxyInterface, ReplayAwareInterface
{

    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var mixed
     */
    private $annotatedEventListener;

    /**
     * @var ReplayAwareInterface
     */
    private $replayAware;

    /**
     * @var AnnotationReaderFactoryInterface
     */
    private $annotationReaderFactory;

    /**
     * @param mixed $annotatedEventListener
     * @param EventBusInterface $eventBus
     * @param AnnotationReaderFactoryInterface $annotationReaderFactory
     */
    public function __construct(
        $annotatedEventListener,
        EventBusInterface $eventBus,
        AnnotationReaderFactoryInterface $annotationReaderFactory
    ) {
        $this->eventBus = $eventBus;
        $this->annotatedEventListener = $annotatedEventListener;
        $this->annotationReaderFactory = $annotationReaderFactory;

        if ($annotatedEventListener instanceof ReplayAwareInterface) {
            $this->replayAware = $annotatedEventListener;
        }

        $this->eventBus->getEventListenerRegistry()->subscribe($this);
    }

    /**
     * @return string
     */
    public function getTargetType()
    {
        return get_class($this->annotatedEventListener);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(EventMessageInterface $event)
    {
        $inspector = new MethodMessageHandlerInspector(
            $this->annotationReaderFactory,
            new \ReflectionClass($this->annotatedEventListener),
            EventHandler::class
        );

        foreach ($inspector->getHandlerDefinitions() as $handlerDefinition) {
            if ($handlerDefinition->getPayloadType() === $event->getPayloadType()) {
                $listener = new AnnotatedEventListener(
                    $handlerDefinition->getPayloadType(),
                    $handlerDefinition->getMethod()->name,
                    $this->annotatedEventListener
                );
                $listener->handle($event);
            }
        }
    }


    /**
     * @param mixed $annotatedEventListener
     * @param EventBusInterface $eventBus
     * @param AnnotationReaderFactoryInterface $annotationReaderFactory
     * @return AnnotatedEventListenerAdapter
     */
    public static function subscribe(
        $annotatedEventListener,
        EventBusInterface $eventBus,
        AnnotationReaderFactoryInterface $annotationReaderFactory
    ) {
        return new AnnotatedEventListenerAdapter($annotatedEventListener, $eventBus, $annotationReaderFactory);
    }

    /**
     * {@inheritdoc}
     */
    public function afterReplay()
    {
        if (null !== $this->replayAware) {
            $this->replayAware->afterReplay();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beforeReplay()
    {
        if (null !== $this->replayAware) {
            $this->replayAware->beforeReplay();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onReplayFailed(\Exception $cause = null)
    {
        if (null !== $this->replayAware) {
            $this->replayAware->onReplayFailed($cause);
        }
    }

}
