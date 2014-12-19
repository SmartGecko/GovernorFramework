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
 * A matcher that will match if all the given <code>matchers</code> each match against an item that the previous
 * matcher matched against. That means the second matcher should match an item that follow the item that the first
 * matcher matched.
 * <p/>
 * If the number of items is larger than the number of matchers, the excess items are not evaluated. Use {@link
 * Matchers#exactSequenceOf(Hamcrest\Matcher[])} to match the sequence exactly. If the last item of the list
 * has been evaluated, and Matchers still remain, they are evaluated against a <code>null</code> value.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class SequenceMatcher extends ListMatcher
{

    /**
     * Construct a matcher that will return true if all the given <code>matchers</code> match against an item
     * positioned after the item that the previous matcher matched against.
     *
     * @param array $matchers The matchers that must match against at least one item in the list.
     */
    public function __construct(array $matchers)
    {
        parent::__construct($matchers);
    }

    public function matchesList(array $items)
    {
        $itemIterator = new \ArrayIterator($items);
        $matcherIterator = new \ArrayIterator($this->getMatchers());
        $currentMatcher = null;

        if ($matcherIterator->valid()) {
            $currentMatcher = $matcherIterator->current();            
        }

        while ($itemIterator->valid() && null !== $currentMatcher) {
            $hasMatch = $currentMatcher->matches($itemIterator->current());

            if ($hasMatch) {
                $matcherIterator->next();
                
                if ($matcherIterator->valid()) {
                    $currentMatcher = $matcherIterator->current();
                } else {
                    $currentMatcher = null;
                }
            }

            $itemIterator->next();
        }

        //echo sprintf("%s->%s, %s->%s\n", $itemIterator->key(), $itemIterator->count(),
        //      $matcherIterator->key(), $matcherIterator->count());

        if (null !== $currentMatcher && !$currentMatcher->matches(null)) {
            $this->reportFailed($currentMatcher);
            return false;
        }
        
        $matcherIterator->next();

        return $this->matchRemainder($matcherIterator);
    }

    protected function describeCollectionType(Description $description)
    {
        $description->appendText("sequence");
    }

}
