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
 * Callback that does absolutely nothing when invoked. For performance reasons, an instance of this callback can be
 * obtained using <code>NoOpCallback.INSTANCE</code>. A generics-compatible alternative is provided by
 * <code>NoOpCallback.&lt;C&gt;instance()</code>.
 *
 * @author Allard Buijze
 * @since 0.6
 */
final class NoOpCallback implements CommandCallbackInterface
{

    /**
     * {@inheritDoc}
     * <p/>
     * This implementation does nothing.
     */
    public function onSuccess($result)
    {
        
    }

    /**
     * {@inheritDoc}
     * <p/>
     * This implementation does nothing.
     */
    public function onFailure(\Exception $cause)
    {
        
    }

}
