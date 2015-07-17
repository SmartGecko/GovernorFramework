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

namespace Governor\Framework\EventHandling\Io;

use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\MessageSerializer;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Domain\DomainEventMessageInterface;

/**
 * EventMessageWriter is responsible to convert an {@link EventMessageInterface} to a binary stream.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class EventMessageWriter
{

    /**
     * @var MessageSerializer 
     */
    private $serializer;

    /**     
     * @param SerializerInterface $serializer Serializer.
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = new MessageSerializer($serializer);
    }

    /**
     * @param EventMessageInterface $event
     * @return mixed
     */
    public function writeEventMessage(EventMessageInterface $event)
    {
        if ($event instanceof DomainEventMessageInterface) {
            $type = 3;
        } else {
            $type = 1;
        }

        $serializedPayload = $this->serializer->serializePayload($event);
        $serializedMetaData = $this->serializer->serializeMetaData($event);
                
        $data = pack("na36N", $type, $event->getIdentifier(),
                $event->getTimestamp()->format('U'));

        if ($event instanceof DomainEventMessageInterface) {
            $data .= pack("a36N", $event->getAggregateIdentifier(),
                    $event->getScn());
        }

        // TODO payload revision
        $packFormat = sprintf("Na%sNa%sNa%s",
                strlen($serializedPayload->getType()->getName()),
                strlen($serializedPayload->getData()),
                strlen($serializedMetaData->getData()));

        $data .= pack($packFormat,
                strlen($serializedPayload->getType()->getName()),
                $serializedPayload->getType()->getName(),
                strlen($serializedPayload->getData()),
                $serializedPayload->getData(),
                strlen($serializedMetaData->getData()),
                $serializedMetaData->getData());
                
        return $data;
    }

}