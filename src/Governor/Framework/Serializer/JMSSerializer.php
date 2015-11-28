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

namespace Governor\Framework\Serializer;

use Governor\Framework\Serializer\Handlers\RamseyUuidHandler;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface as JMSSerializerInterface;

/**
 * Serializer implementation using the JMS serializer component.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class JMSSerializer extends AbstractSerializer
{

    /**
     * @var JMSSerializerInterface
     */
    private $serializer;

    /**
     *
     * @param RevisionResolverInterface $revisionResolver
     * @param \JMS\Serializer\SerializerInterface $serializer
     */
    public function __construct(
        RevisionResolverInterface $revisionResolver = null,
        JMSSerializerInterface $serializer = null
    ) {
        parent::__construct($revisionResolver);

        if (null === $serializer) {
            $this->serializer = SerializerBuilder::create()
                ->addDefaultHandlers()
                ->configureHandlers(
                    function (HandlerRegistry $registry) {
                        $registry->registerSubscribingHandler(new RamseyUuidHandler());
                    }
                )->build();
        } else {
            $this->serializer = $serializer;
        }
    }

    public function deserialize(SerializedObjectInterface $data)
    {
        try {
            return $this->serializer->deserialize(
                $data->getData(),
                $data->getContentType(),
                'json'
            );
        } catch (\Exception $ex) {
            throw new UnknownSerializedTypeException($data->getType(), $ex);
        }
    }

    public function serialize($object)
    {
        $result = $this->serializer->serialize($object, 'json');

        return new SimpleSerializedObject($result, $this->typeForClass($object));
    }

}
