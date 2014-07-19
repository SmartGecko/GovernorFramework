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
use Governor\Framework\Domain\MessageInterface;

/**
 * Description of PayloadsMatcher
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class PayloadsMatcher extends BaseMatcher
{

    /**     
     * @var Matcher
     */
    private $matcher;

    /**
     * Constructs an instance that uses the given <code>matcher</code> to match the payloads.
     *
     * @param Matcher $matcher             The matcher to match the payloads with
     */
    public function __construct(Matcher $matcher)
    {
        $this->matcher = $matcher;
    }

    public function matches($item)
    {
        if (!is_array($item)) {
            return false;
        }

        $payloads = array();
        
        foreach ($item as $listItem) {
            if ($listItem instanceof MessageInterface) {
                $payloads[] = $listItem->getPayload();
            } else {
                $payloads[] = $item;
            }
        }
        
        return $this->matcher->matches($payloads);
    }

    public function describeTo(Description $description)
    {
        $description->appendText("List with EventMessages with Payloads matching <");
        $this->matcher->describeTo($description);
        $description->appendText(">");
    }

}
