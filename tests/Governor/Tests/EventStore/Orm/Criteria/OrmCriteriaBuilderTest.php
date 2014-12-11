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

namespace Governor\Tests\EventStore\Orm\Criteria;

use Governor\Framework\EventStore\Orm\Criteria\OrmCriteriaBuilder;
use Governor\Framework\EventStore\Orm\Criteria\ParameterRegistry;
/**
 * Description of OrmCriteriaBuilderTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class OrmCriteriaBuilderTest extends \PHPUnit_Framework_TestCase
{

    public function testParameterTypeRetained()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThan((double) 1.0)
                ->andX($builder->property("property2")->is(1))
                ->orX($builder->property("property3")->isNot("1"));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $params = $parameters->getParameters();

        $this->assertInternalType('float', $params['param0']);
        $this->assertInternalType('integer', $params['param1']);
        $this->assertInternalType('string', $params['param2']);
    }

    public function testBuildCriteria_ComplexStructureWithUnequalNull()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThan("less")
                ->andX($builder->property("property2")->greaterThan("gt"))
                ->orX($builder->property("property3")->notIn($builder->property("collection")))
                ->orX($builder->property("property4")->isNot(null));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $this->assertEquals(
                "(((entry.property < :param0) AND (entry.property2 > :param1)) OR (entry.property3 NOT IN entry.collection)) OR (entry.property4 IS NOT NULL)",
                $query);
        $this->assertCount(2, $parameters->getParameters());
        $this->assertEquals("less", $parameters->getParameters()["param0"]);
        $this->assertEquals("gt", $parameters->getParameters()["param1"]);
    }

    public function testBuildCriteria_ComplexStructureWithUnequalValue()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThan("less")
                ->andX($builder->property("property2")->greaterThanEquals("gte"))
                ->orX($builder->property("property3")->in(array("one", "two")))
                ->orX($builder->property("property4")->isNot("4"));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $this->assertEquals(
                "(((entry.property < :param0) AND (entry.property2 >= :param1)) OR (entry.property3 IN (:param2))) OR (entry.property4 <> :param3)",
                $query);
        $this->assertCount(4, $parameters->getParameters());
        $this->assertEquals("less", $parameters->getParameters()["param0"]);
        $this->assertEquals("gte", $parameters->getParameters()["param1"]);
        $this->assertEquals("4", $parameters->getParameters()["param3"]);
        $this->assertEquals(array("one", "two"),
                $parameters->getParameters()["param2"]);
    }

    public function testBuildCriteria_ComplexStructureWithUnequalProperty()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThanEquals("lte")
                ->andX($builder->property("property2")->greaterThanEquals("gte"))
                ->orX($builder->property("property3")->in(array("one", "two")))
                ->orX($builder->property("property4")->isNot($builder->property("property4")));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $this->assertEquals(
                "(((entry.property <= :param0) AND (entry.property2 >= :param1)) OR (entry.property3 IN (:param2))) OR (entry.property4 <> entry.property4)",
                $query);

        $this->assertCount(3, $parameters->getParameters());
        $this->assertEquals("lte", $parameters->getParameters()["param0"]);
        $this->assertEquals("gte", $parameters->getParameters()["param1"]);
        $this->assertEquals(array("one", "two"),
                $parameters->getParameters()["param2"]);
    }

    public function testBuildCriteria_ComplexStructureWithEqualNull()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThan("less")
                ->andX($builder->property("property2")->greaterThanEquals("gte"))
                ->orX($builder->property("property3")->in(array("one", "two")))
                ->orX($builder->property("property4")->is(null));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $this->assertEquals(
                "(((entry.property < :param0) AND (entry.property2 >= :param1)) OR (entry.property3 IN (:param2))) OR (entry.property4 IS NULL)",
                $query);
        $this->assertCount(3, $parameters->getParameters());
        $this->assertEquals("less", $parameters->getParameters()["param0"]);
        $this->assertEquals("gte", $parameters->getParameters()["param1"]);
        $this->assertEquals(array("one", "two"),
                $parameters->getParameters()["param2"]);
    }

    public function testBuildCriteria_ComplexStructureWithEqualValue()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThan("less")
                ->andX($builder->property("property2")->greaterThanEquals("gte"))
                ->orX($builder->property("property3")->in(array("one", "two")))
                ->orX($builder->property("property4")->is("4"));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $this->assertEquals(
                "(((entry.property < :param0) AND (entry.property2 >= :param1)) OR (entry.property3 IN (:param2))) OR (entry.property4 = :param3)",
                $query);
        $this->assertCount(4, $parameters->getParameters());
        $this->assertEquals("less", $parameters->getParameters()["param0"]);
        $this->assertEquals("gte", $parameters->getParameters()["param1"]);
        $this->assertEquals("4", $parameters->getParameters()["param3"]);
        $this->assertEquals(array("one", "two"),
                $parameters->getParameters()["param2"]);
    }

    public function testBuildCriteria_ComplexStructureWithEqualProperty()
    {
        $builder = new OrmCriteriaBuilder();
        $criteria = $builder->property("property")->lessThan($builder->property("prop1"))
                ->andX($builder->property("property2")->greaterThanEquals("gte"))
                ->orX($builder->property("property3")->in(array("one", "two")))
                ->orX($builder->property("property4")->is($builder->property("property4")));

        $query = "";
        $parameters = new ParameterRegistry();
        $criteria->parse("entry", $query, $parameters);

        $this->assertEquals(
                "(((entry.property < entry.prop1) AND (entry.property2 >= :param0)) OR (entry.property3 IN (:param1))) OR (entry.property4 = entry.property4)",
                $query);
        $this->assertCount(2, $parameters->getParameters());
        $this->assertEquals("gte", $parameters->getParameters()["param0"]);
        $this->assertEquals(array("one", "two"),
                $parameters->getParameters()["param1"]);
    }

}
