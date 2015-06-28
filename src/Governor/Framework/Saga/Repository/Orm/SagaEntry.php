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

namespace Governor\Framework\Saga\Repository\Orm;

use Doctrine\ORM\Mapping as ORM;
use Governor\Framework\Saga\SagaInterface;
use Governor\Framework\Serializer\SerializerInterface;
use Governor\Framework\Serializer\SimpleSerializedObject;
use Governor\Framework\Serializer\SimpleSerializedType;

/**
 * Class defining a Saga in the ORM.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 * @ORM\Entity
 * @ORM\Table(name="governor_sagas")
 */
class SagaEntry
{

    /**
     * @ORM\Id
     * @ORM\Column(type="string", name="saga_id")
     * @var string
     */
    private $sagaId;

    /**
     * @ORM\Column(type="string", name="saga_type")
     * @var string
     */
    private $sagaType;
    /**
     * @ORM\Column(type="string", name="saga_revision", nullable=true)
     * @var string
     */
    private $revision;
    /**
     * @ORM\Column(type="text", name="serialized_saga")
     * @var string
     */
    private $serializedSaga;

    /**
     * @var SagaInterface
     */
    private $saga;

    /**
     * Constructs a new SagaEntry for the given <code>saga</code>. The given saga must be serializable. The provided
     * saga is not modified by this operation.
     *
     * @param SagaInterface $saga The saga to store
     * @param SerializerInterface $serializer The serialization mechanism to convert the Saga to a byte stream
     */
    public function __construct(
        SagaInterface $saga,
        SerializerInterface $serializer
    ) {
        $this->sagaId = $saga->getSagaIdentifier();
        $serialized = $serializer->serialize($saga);
        $this->serializedSaga = $serialized->getData();
        $this->sagaType = $serialized->getType()->getName();
        $this->revision = $serialized->getType()->getRevision();
        $this->saga = $saga;
    }

    /**
     * Returns the Saga instance stored in this entry.
     *
     * @param SerializerInterface $serializer The serializer to decode the Saga
     * @return SagaInterface the Saga instance stored in this entry
     */
    public function getSaga(SerializerInterface $serializer)
    {
        if (null !== $this->saga) {
            return $this->saga;
        }

        return $serializer->deserialize(
            new SimpleSerializedObject(
                $this->serializedSaga,
                new SimpleSerializedType(
                    $this->sagaType,
                    $this->revision
                )
            )
        );
    }

    /**
     * Returns the serialized form of the Saga.
     *
     * @return string the serialized form of the Saga
     */
    public function getSerializedSaga()
    {
        return $this->serializedSaga;
    }

    /**
     * Returns the identifier of the saga contained in this entry
     *
     * @return string the identifier of the saga contained in this entry
     */
    public function getSagaId()
    {
        return $this->sagaId;
    }

    /**
     * Returns the revision of the serialized saga
     *
     * @return string the revision of the serialized saga
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Returns the type identifier of the serialized saga.
     *
     * @return string the type identifier of the serialized saga
     */
    public function getSagaType()
    {
        return $this->sagaType;
    }

}
