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

namespace Governor\Framework\EventSourcing;

use Governor\Framework\Domain\DomainEventStreamInterface;

/**
 * Description of CapturingEventStream
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class CapturingEventStream implements DomainEventStreamInterface
{

    /**
     * @var DomainEventStreamInterface
     */
    private $eventStream;
    private $unseenEvents;
    private $expectedVersion;

    public function __construct(DomainEventStreamInterface $events,
            &$unseenEvents, $expectedVersion)
    {
        $this->eventStream = $events;
        $this->unseenEvents = &$unseenEvents;
        $this->expectedVersion = $expectedVersion;
    }

    public function hasNext()
    {
        return $this->eventStream->hasNext();
    }

    public function next()
    {
        $next = $this->eventStream->next();
        if (null !== $this->expectedVersion && $next->getScn() > $this->expectedVersion) {            
            $this->unseenEvents[] = $next;
        }
        
        return $next;
    }

    public function peek()
    {
        return $this->eventStream->peek();
    }

}
