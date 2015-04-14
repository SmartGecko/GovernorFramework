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

namespace Governor\Framework\Test\Matchers;

use Hamcrest\BaseMatcher;
use Hamcrest\Description;
use Governor\Framework\Common\ReflectionUtils;

/**
 * Description of EqualFieldsMatcher
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class EqualFieldsMatcher extends BaseMatcher
{

    private $expected;
    private $failedField;
    private $failedFieldExpectedValue;
    private $failedFieldActualValue;

    /**
     * Initializes an EqualFieldsMatcher that will match an object with equal properties as the given
     * <code>expected</code> object.
     *
     * @param mixed $expected The expected object
     */
    public function __construct($expected)
    {
        $this->expected = $expected;
    }

    public function matches($item)
    {
        return $this->expected instanceof $item && $this->matchesSafely($item);
    }

    private function matchesSafely($actual)
    {
        return get_class($this->expected) === get_class($actual) &&
                $this->fieldsMatch(get_class($this->expected), $this->expected,
                        $actual);
    }

    private function fieldsMatch($aClass, $expectedValue, $actual)
    {
        $match = true;
        $reflClass = new \ReflectionClass($aClass);

        foreach (ReflectionUtils::getProperties($reflClass) as $property) {
            $property->setAccessible(true);

            $expectedFieldValue = $property->getValue($expectedValue);
            $actualFieldValue = $property->getValue($actual);

            if ($expectedFieldValue != $actualFieldValue) {
                $this->failedField = $property->name;
                $this->failedFieldExpectedValue = $expectedFieldValue;
                $this->failedFieldActualValue = $actualFieldValue;
                return false;
            }
        }

        return $match;
    }

    /**
     * Returns the field that failed comparison, if any. This value is only populated after {@link #matches(Object)} is
     * called and a mismatch has been detected.
     *
     * @return string the field that failed comparison, if any
     */
    public function getFailedField()
    {
        return $this->failedField;
    }

    /**
     * Returns the expected value of a failed field comparison, if any. This value is only populated after {@link
     * #matches(Object)} is called and a mismatch has been detected.
     *
     * @return mixed the expected value of the field that failed comparison, if any
     */
    public function getFailedFieldExpectedValue()
    {
        return $this->failedFieldExpectedValue;
    }

    /**
     * Returns the actual value of a failed field comparison, if any. This value is only populated after {@link
     * #matches(Object)} is called and a mismatch has been detected.
     *
     * @return mixed the actual value of the field that failed comparison, if any
     */
    public function getFailedFieldActualValue()
    {
        return $this->failedFieldActualValue;
    }

    public function describeTo(Description $description)
    {
        $description->appendText(get_class($this->expected));

        if (null !== $this->failedField) {
            $description->appendText(" (failed on field '")
                    ->appendText($this->failedField)
                    ->appendText("')");
        }
    }

}
