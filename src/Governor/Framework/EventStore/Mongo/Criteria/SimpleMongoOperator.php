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
 * Implementation of the simple Mongo Operators (those without special structural requirements), such as Less Than,
 * Less Than Equals, etc.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class SimpleMongoOperator extends MongoCriteria
{
    /**
     * @var MongoProperty
     */
    private $property;
    /**
     * @var string
     */
    private $operator;
    /**
     * @var mixed
     */
    private $expression;

    /**
     * Initializes an criterium where the given <code>property</code>, <code>operator</code> and
     * <code>expression</code>
     * make a match. The expression may be a fixed value, as well as a MongoProperty
     *
     * @param MongoProperty $property The property to match
     * @param string $operator The operator to match with
     * @param mixed $expression The expression to match against the property
     */
    public function  __construct(MongoProperty $property, $operator, $expression)
    {
        $this->property = $property;
        $this->operator = $operator;
        $this->expression = $expression;

        if ($expression instanceof MongoProperty) {
            throw new \InvalidArgumentException(
                'The MongoEventStore does not support comparison between two properties'
            );
        }

    }


    public function asMongoObject()
    {
        return [$this->property->getName() => [$this->operator => (string)$this->expression]];
    }
}