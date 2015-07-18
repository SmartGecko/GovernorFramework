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

namespace Governor\Framework\CommandHandling\Callbacks;

use Governor\Framework\CommandHandling\CommandCallbackInterface;

/**
 * Closure based implementation of the CommandCallbackInterface.
 * Depending on the outcome of the execution either the success or failure Closure function is invoked.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
final class ClosureCommandCallback implements CommandCallbackInterface
{

    /**
     * @var \Closure
     */
    private $successCallback;

    /**
     * @var \Closure
     */
    private $failureCallback;

    /**
     * @param callable $successCallback
     * @param callable $failureCallback
     */
    public function __construct(
        \Closure $successCallback,
        \Closure $failureCallback
    ) {
        $this->successCallback = $successCallback;
        $this->failureCallback = $failureCallback;
    }

    /**
     * Invoked when command handling execution was successful.
     *
     * @param mixed $result The result of the command handling execution, if any.
     */
    public function onSuccess($result)
    {
        $cb = $this->successCallback;
        $cb($result);
    }

    /**
     * Invoked when command handling execution resulted in an error.
     *
     * @param \Exception $exception The exception raised during command handling
     */
    public function onFailure(\Exception $exception)
    {
        $cb = $this->failureCallback;
        $cb($exception);
    }

}
