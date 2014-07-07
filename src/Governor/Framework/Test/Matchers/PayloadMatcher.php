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
use Governor\Framework\Domain\MessageInterface;

/**
 * Matcher that matches any message (e.g. Event, Command) who's payload matches the given matcher.
 */
class PayloadMatcher extends BaseMatcher
{

    private $payloadMatcher;

    /**
     * Constructs an instance with the given <code>payloadMatcher</code>.
     *
     * @param payloadMatcher The matcher that must match the Message's payload.
     */
    public function __construct(Matcher $payloadMatcher)
    {
        $this->payloadMatcher = $payloadMatcher;
    }

    public function matches($item)
    {
        return $item instanceof MessageInterface && $this->payloadMatcher->matches($item->getPayload());
    }

    public function describeTo(Description $description)
    {
        $description->appendText("Message with payload <");
        $this->payloadMatcher->describeTo($description);
        $description->appendText(">");
    }

}
