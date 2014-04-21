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

use Governor\Framework\EventHandling\EventListenerInterface;
use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of AnnotatedEventHandler
 *
 * @author 255196
 */
class AnnotatedEventListener implements EventListenerInterface
{

    private $eventName;
    private $methodName;
    private $eventTarget;

    function __construct($eventName, $methodName, $eventTarget)
    {
        $this->eventName = $eventName;
        $this->methodName = $methodName;
        $this->eventTarget = $eventTarget;
    }

    public function handle(EventMessageInterface $event)
    {
        try {
            $this->verifyEventMessage($event);
            $reflMethod = new \ReflectionMethod($this->eventTarget,
                    $this->methodName);

            $arguments = array($event->getPayload());
            
            // !!! TODO more checks this is just temporary
            if (2 === $reflMethod->getNumberOfParameters()) {
                $arguments[] = $event;
            }
            
            $reflMethod->invokeArgs($this->eventTarget, $arguments);
        } catch (\Exception $ex) {
            // ignore everything
        }
    }

    protected function verifyEventMessage(EventMessageInterface $message)
    {
        if ($message->getPayloadType() !== $this->eventName) {
            throw new \BadMethodCallException(sprintf("Invalid event in listener %s, expected %s but got %s",
                    get_class($this), $this->eventName,
                    $message->getPayloadType()));
        }
    }

}
