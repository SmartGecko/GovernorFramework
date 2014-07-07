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
use Governor\Framework\Saga\AssociationValue;

/**
 * Description of AssociationValueEntry
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 * @ORM\Entity
 * @ORM\Table(name="governor_association_values")
 */
class AssociationValueEntry
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="saga_id")
     * @var string 
     */
    private $sagaId;

    /**
     * @ORM\Column(type="string", name="association_key")
     * @var string 
     */
    private $associationKey;

    /**
     * @ORM\Column(type="string", name="association_value")
     * @var string 
     */
    private $associationValue;

    /**
     * @ORM\Column(type="string", name="saga_type")
     * @var string 
     */
    private $sagaType;

    /**
     * Initialize a new AssociationValueEntry for a saga with given <code>sagaIdentifier</code> and
     * <code>associationValue</code>.
     *
     * @param string $sagaType         The type of Saga this association value belongs to
     * @param string $sagaIdentifier   The identifier of the saga
     * @param AssociationValue $associationValue The association value for the saga
     */
    public function __construct($sagaType, $sagaIdentifier,
            AssociationValue $associationValue)
    {
        $this->sagaType = $sagaType;
        $this->sagaId = $sagaIdentifier;
        $this->associationKey = $associationValue->getPropertyKey();
        $this->associationValue = $associationValue->getPropertyValue();
    }

    /**
     * Returns the association value contained in this entry.
     *
     * @return AssociationValue the association value contained in this entry
     */
    public function getAssociationValue()
    {
        return new AssociationValue($this->associationKey,
                $this->associationValue);
    }

    /**
     * Returns the Saga Identifier contained in this entry.
     *
     * @return string the Saga Identifier contained in this entry
     */
    public function getSagaIdentifier()
    {
        return $this->sagaId;
    }

    /**
     * Returns the type (fully qualified class name) of the Saga this association value belongs to
     *
     * @return string the type (fully qualified class name) of the Saga
     */
    public function getSagaType()
    {
        return $this->sagaType;
    }

    /**
     * The unique identifier of this entry.
     *
     * @return integer the unique identifier of this entry
     */
    public function getId()
    {
        return $this->id;
    }

}
