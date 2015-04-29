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
use Hamcrest\Matcher;

/**
 * Description of ListWithAnyOfMatcher
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class ListWithAnyOfMatcher extends ListMatcher
{

    /**
     * @param Matcher[] $matchers
     */
    public function __construct($matchers)
    {
        parent::__construct($matchers);
    }

    /**
     * @param array $items
     * @return bool
     */
    public function matchesList(array $items)
    {
        $match = false;
        foreach ($this->getMatchers() as $matcher) {
            $matcherMatch = false;
            
            foreach ($items as $item) {
                if ($matcher->matches($item)) {
                    $match = true;
                    $matcherMatch = true;
                }
            }
            
            if (!$matcherMatch) {
                $this->reportFailed($matcher);
            }
        }
        return $match;
    }

    protected function describeCollectionType(Description $description)
    {
        $description->appendText("any");
    }

    /**
     * @return string
     */
    protected function failedMatcherMessage()
    {
        return "NO MATCH";
    }

    /**
     * @return string
     */
    protected function getLastSeparator()
    {
        return "or";
    }

}
