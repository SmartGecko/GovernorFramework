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

/**
 * Description of EmptyCollectionMatcher
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class EmptyCollectionMatcher extends BaseMatcher
{
    /**    
     * @var string
     */
    private $contentDescription;

    /**
     * Creates a matcher of a list of empty items. The name of the item type (in plural) is passed in the given
     * <code>contentDescription</code> and will be part of the description of this matcher.
     *
     * @param string $contentDescription The description of the content type of the collection
     */
    public function __construct($contentDescription)
    {
        $this->contentDescription = $contentDescription;
    }

    /**     
     * @param array $item
     * @return boolean
     */
    public function matches($item)
    {
        //return item instanceof Collection && ((Collection) item).isEmpty();
        return is_array($item) && 0 === count($item);
    }

    /**
     * @param \Hamcrest\Description $description
     */
    public function describeTo(Description $description)
    {
        $description->appendText("no ");
        $description->appendText($this->contentDescription);
    }

}
