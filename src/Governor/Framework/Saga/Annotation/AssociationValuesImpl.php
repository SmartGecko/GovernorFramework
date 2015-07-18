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

namespace Governor\Framework\Saga\Annotation;

use JMS\Serializer\Annotation as Serializer;
use Governor\Framework\Saga\AssociationValue;
use Governor\Framework\Saga\AssociationValuesInterface;

/**
 * Description of AssociationValuesImpl
 *
 */
class AssociationValuesImpl implements AssociationValuesInterface
{

    /**
     * @Serializer\Type ("array<Governor\Framework\Saga\AssociationValue>")
     * @var AssociationValue[]
     */
    private $values;

    /**
     * @Serializer\Exclude
     * @var AssociationValue[]
     */
    private $addedValues;

    /**
     * @Serializer\Exclude
     * @var AssociationValue[]
     */
    private $removedValues;

    public function __construct()
    {
        $this->values = [];
        $this->addedValues = [];
        $this->removedValues = [];
    }

    /**
     * @Serializer\PostDeserialize
     */
    public function postDeserialize()
    {
        $this->addedValues = [];
        $this->removedValues = [];
    }

    /**
     * Searches the array containes an association value identical to the specified one.
     * Elements are compared with <code>==</code> for equality.
     *
     * @param \Governor\Framework\Saga\AssociationValue $associationValue
     * @param array $collection
     * @return boolean
     */
    private function inCollection(
        AssociationValue $associationValue,
        array $collection
    ) {
        foreach ($collection as $element) {
            if ($element == $associationValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function add(AssociationValue $associationValue)
    {
        if (!$this->inCollection($associationValue, $this->values)) {
            $this->values[] = $associationValue;
            $added = true;
        } else {
            $added = false;
        }

        if ($added) {
            if ($this->inCollection($associationValue, $this->removedValues)) {
                $this->removedValues = array_udiff(
                    $this->removedValues,
                    array($associationValue),
                    function (AssociationValue $a, AssociationValue $b) {
                        return $a->compareTo($b);
                    }
                );
            } else {
                $this->addedValues[] = $associationValue;
            }
        }

        return $added;
    }

    /**
     * {@inheritdoc}
     */
    public function addedAssociations()
    {
        return $this->addedValues;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->addedValues = [];
        $this->removedValues = [];
    }

    /**
     * {@inheritdoc}
     */
    public function contains(AssociationValue $associationValue)
    {
        return $this->inCollection($associationValue, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(AssociationValue $associationValue)
    {
        if ($this->inCollection($associationValue, $this->values)) {
            $this->values = array_udiff(
                $this->values,
                array($associationValue),
                function (AssociationValue $a, AssociationValue $b) {
                    return $a->compareTo($b);
                }
            );
            $removed = true;
        } else {
            $removed = false;
        }

        if ($removed) {
            if ($this->inCollection($associationValue, $this->addedValues)) {
                $this->addedValues = array_udiff(
                    $this->addedValues,
                    array($associationValue),
                    function ($a, $b) {
                        return $a->compareTo($b);
                    }
                );
            } else {
                $this->removedValues[] = $associationValue;
            }
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function removedAssociations()
    {
        return $this->removedValues;
    }

    /**
     * {@inheritdoc}
     */
    public function size()
    {
        return count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function asArray()
    {
        return $this->values;
    }


}
