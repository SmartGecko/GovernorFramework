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

use Hamcrest\Matcher;
use Hamcrest\BaseMatcher;
use Hamcrest\Description;

/**
 * Abstract implementation for matchers that use event-specific matchers to match against a list of items.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class ListMatcher extends BaseMatcher
{
    /**     
     * @var array
     */
    private $failedMatchers = array();
    
    /**     
     * @var array
     */
    private $matchers = array();

    /**
     * Creates an abstract matcher to match a number of Matchers against Events contained inside a Collection.
     *
     * @param Matcher[] $matchers The matchers to match the individual Events in the Collection
     */
    protected function __construct(array $matchers)
    {
        $this->matchers = $matchers;
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public function matches($item)
    {        
        return is_array($item) && $this->matchesList($item);
    }

    /**
     * Evaluates the matcher for argument <code>item</code>. The item has been verified to be a list, but the exact
     * type of contents of a list cannot be verified, due to Erasure of Generic Types.
     *
     * @param array $item the object against which the matcher is evaluated.
     * @return boolean <code>true</code> if <code>item</code> matches, otherwise <code>false</code>.
     *
     * @see BaseMatcher
     */
    abstract protected function matchesList(array $item);

    /**
     * Matches all the remaining Matchers in the given <code>matcherIterator</code> against <code>null</code>.
     *
     * @param \Iterator $matcherIterator The iterator potentially containing more matchers
     * @return boolean true if no matchers remain or all matchers succeeded
     */
    protected function matchRemainder(\Iterator $matcherIterator)
    {        
        while ($matcherIterator->valid()) {            
            $matcher = $matcherIterator->current();

            if (!$matcher->matches(null)) {                
                $this->failedMatchers[] = $matcher;
                return false;
            }

            $matcherIterator->next();
        }

        return true;
    }

    /**
     * Report the given <code>matcher</code> as a failing matcher. This will be used in the error reporting.
     *
     * @param Matcher $matcher The failing matcher.
     */
    protected function reportFailed(Matcher $matcher)
    {
        $this->failedMatchers[] = $matcher;
    }

    /**
     * Returns a read-only list of Matchers, in the order they were provided in the constructor.
     *
     * @return Matcher[] a read-only list of Matchers, in the order they were provided in the constructor
     */
    protected function getMatchers()
    {
        return $this->matchers;
    }

    /**
     * Describes the type of collection expected. To be used in the sentence: "list with ... of: <description of
     * matchers>". E.g. "all" or "sequence".
     *
     * @param Description $description the description to append the collection type to
     */
    protected abstract function describeCollectionType(Description $description);

    public function describeTo(Description $description)
    {
        $description->appendText("list with ");
        $this->describeCollectionType($description);
        $description->appendText(" of: ");

        for ($t = 0; $t < count($this->matchers); $t++) {            
            if ($t !== 0 && $t < count($this->matchers) - 1) {
                $description->appendText(", ");
            } else if ($t === count($this->matchers) - 1 && $t > 0) {
                $description->appendText(" ");
                $description->appendText($this->getLastSeparator());
                $description->appendText(" ");
            }
            
            $description->appendText("<");
            $this->matchers[$t]->describeTo($description);
            $description->appendText(">");

            if (in_array($this->matchers[$t], $this->failedMatchers, true)) {
                $description->appendText(" (");
                $description->appendText($this->failedMatcherMessage());
                $description->appendText(")");
            }
        }
    }

    /**
     * The message to append behind a failing matcher. Defaults to FAILED!.
     *
     * @return string The message to append behind a failing matcher
     */
    protected function failedMatcherMessage()
    {
        return "FAILED!";
    }

    /**
     * The separator to use between the two last events. Defaults to "and".
     *
     * @return string The separator to use between the two last events. Defaults to "and".
     */
    protected function getLastSeparator()
    {
        return "and";
    }

}
