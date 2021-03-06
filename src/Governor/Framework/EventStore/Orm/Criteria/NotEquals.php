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

namespace Governor\Framework\EventStore\Orm\Criteria;

/**
 * Description of Equals
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class NotEquals extends OrmCriteria
{

    /**
     * @var OrmProperty
     */
    private $propertyName;

    /**
     * @var mixed
     */
    private $expression;

    /**
     * Initializes an Equals operator matching the given <code>property</code> against the given
     * <code>expression</code>.
     *
     * @param OrmProperty $property   The property to match
     * @param mixed $expression The expression to match against. May be <code>null</code>.
     */
    public function __construct(OrmProperty $property, $expression)
    {
        $this->propertyName = $property;
        $this->expression = $expression;
    }

    public function parse($entryKey, &$whereClause,
            ParameterRegistry $parameters)
    {
        $this->propertyName->parse($entryKey, $whereClause);

        if (null === $this->expression) {
            $whereClause .= " IS NOT NULL";
        } else {
            $whereClause .= " <> ";
            if ($this->expression instanceof OrmProperty) {
                $this->expression->parse($entryKey, $whereClause);
            } else {
                $whereClause .= $parameters->register($this->expression);
            }
        }
    }

}
