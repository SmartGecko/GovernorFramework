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

use Governor\Framework\EventStore\Management\PropertyInterface;

/**
 * Description of OrmProperty
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class OrmProperty implements PropertyInterface
{

    /**
     * @var string
     */
    private $propertyName;

    function __construct($propertyName)
    {
        $this->propertyName = $propertyName;
    }

    public function greaterThan($expression)
    {
        return new SimpleOperator($this, ">", $expression);
    }

    public function greaterThanEquals($expression)
    {
        return new SimpleOperator($this, ">=", $expression);
    }

    public function in($expression)
    {
        return new CollectionOperator($this, "IN", $expression);
    }

    public function is($expression)
    {
        return new Equals($this, $expression);
    }

    public function isNot($expression)
    {
        return new NotEquals($this, $expression);
    }

    public function lessThan($expression)
    {
        return new SimpleOperator($this, "<", $expression);
    }

    public function lessThanEquals($expression)
    {
        return new SimpleOperator($this, "<=", $expression);
    }

    public function notIn($expression)
    {
        return new CollectionOperator($this, "NOT IN", $expression);
    }

    /**
     * Parse the property value to a valid DQL expression.
     *
     * @param string $entryKey      The variable assigned to the entry holding the property
     * @param string $stringBuilder The builder to append the expression to
     */
    public function parse($entryKey, &$stringBuilder)
    {
        $stringBuilder .= $entryKey . "." . $this->propertyName;        
    }

}
