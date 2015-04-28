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

namespace Governor\Tests\EventHandling\Listeners;

use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Common\Annotation\SimpleAnnotationReaderFactory;
use Governor\Framework\Domain\GenericEventMessage;
use Governor\Framework\EventHandling\Listeners\AnnotatedEventListenerAdapter;
use Governor\Framework\Annotations\EventHandler;
use Governor\Framework\Test\Utils\RecordingEventBus;
use Governor\Tests\Test\MyEvent;
use Governor\Tests\Test\MyOtherEvent;

/**
 * AnnotatedEventListenerAdapter unit test
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AnnotatedEventListenerAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventMessageInterface[]
     */
    private $publishedEvents = [];

    /**
     * @var RecordingEventBus
     */
    private $eventBus;

    public function setUp()
    {
        $this->eventBus = new RecordingEventBus($this->publishedEvents);
    }

    public function testSubscribe()
    {
        $listener = new AnnotatedListener();
        AnnotatedEventListenerAdapter::subscribe($listener, $this->eventBus, new SimpleAnnotationReaderFactory());

        $event1 = new MyEvent("a", "b");
        $event2 = new MyOtherEvent();

        $this->eventBus->publish(array(GenericEventMessage::asEventMessage($event1)));

        $this->assertCount(1, $this->publishedEvents);
        $this->assertSame($listener->event, $event1);

        $this->eventBus->publish(array(GenericEventMessage::asEventMessage($event2)));

        $this->assertCount(2, $this->publishedEvents);
        $this->assertSame($listener->event, $event2);

    }
}

class AnnotatedListener
{
    /**
     * @var mixed
     */
    public $event;

    /**
     * @EventHandler()
     * @param MyEvent $event
     */
    public function onMyEvent(MyEvent $event)
    {
        $this->event = $event;
    }

    /**
     * @EventHandler()
     * @param MyOtherEvent $event
     */
    public function onMyOtherEvent(MyOtherEvent $event)
    {
        $this->event = $event;
    }
}