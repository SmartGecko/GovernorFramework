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

use Hamcrest\Description;

/**
 * A matcher that will match if all the given <code>matchers</code> against the event in a list at their respective
 * index. That means the first matcher must match against the first event, the second matcher against the second event,
 * and so forth.
 * <p/>
 * If the number of Events is larger than the number of matchers, the excess events are not evaluated. Use {@link
 * Matchers#exactSequenceOf(Hamcrest\Matcher[])} to match the sequence exactly. If there are more matchers
 * than Events, the remainder of matchers is evaluated against a <code>null</code> value.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ExactSequenceMatcher extends ListMatcher
{

    /**
     * Construct a matcher that will return true if all the given <code>matchers</code> match against the event with
     * the same index in a given List if Events.
     *
     * @param array $matchers The matchers that must match against at least one Event in the list.
     */
    public function __construct($matchers)
    {
        parent::__construct($matchers);
    }

    public function matchesList(array $events)
    {
        $eventIterator = new \ArrayIterator($events);
        $matcherIterator = new \ArrayIterator($this->getMatchers());

        while ($eventIterator->valid() && $matcherIterator->valid()) {
            $matcher = $matcherIterator->current();

            if (!$matcher->matches($eventIterator->current())) {
                $this->reportFailed($matcher);
                return false;
            }

            $eventIterator->next();
            $matcherIterator->next();
        }

        return $this->matchRemainder($matcherIterator);
    }

    protected function describeCollectionType(Description $description)
    {
        $description->appendText("exact sequence");
    }

}
