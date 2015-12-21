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

namespace Governor\Framework\EventStore\Mongo\Criteria;


/**
 * Implementation of Collection operators for the Mongo Criteria, such as "In" and "NotIn".
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class CollectionCriteria extends MongoCriteria
{

    /**
     * @var MongoProperty
     */
    private $property;
    /**
     * @var mixed
     */
    private $expression;
    /**
     * @var string
     */
    private $operator;

    /**
     * Returns a criterion that requires the given <code>property</code> value to be present in the given
     * <code>expression</code> to evaluate to <code>true</code>.
     *
     * @param MongoProperty $property The property to match
     * @param string $operator The collection operator to use
     * @param mixed $expression The expression to that expresses the collection to match against the property
     */
    public function __construct(MongoProperty $property, $operator, $expression)
    {
        $this->property = $property;
        $this->expression = $expression;
        $this->operator = $operator;
    }


    public function asMongoObject()
    {
        return [
            $this->property->getName() => [
                $this->operator => $this->expression
            ]
        ];

    }
}