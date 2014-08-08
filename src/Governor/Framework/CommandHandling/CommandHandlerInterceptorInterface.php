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

namespace Governor\Framework\CommandHandling;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;

/**
 * Workflow interface that allows for customized command handler invocation chains. A CommandHandlerInterceptor can add
 * customized behavior to command handler invocations, both before and after the invocation.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface CommandHandlerInterceptorInterface
{

    /**
     * The handle method is invoked each time a command is dispatched through the command bus that the
     * CommandHandlerInterceptorInterface is declared on. The incoming command and contextual information can be found in the
     * given <code>unitOfWork</code>.
     * <p/>
     * The interceptor is responsible for the continuation of the dispatch process by invoking the {@link
     * InterceptorChain#proceed(CommandMessage)} method on the given
     * <code>interceptorChain</code>.
     * <p/>
     * Any information gathered by interceptors may be attached to the unitOfWork. This information is made
     * available to the CommandCallbackInterface provided by the dispatching component.
     * <p/>
     * Interceptors are highly recommended not to change the type of the command handling result, as the dispatching
     * component might expect a result of a specific type.
     *
     * @param CommandMessageInterface $commandMessage   The command being dispatched
     * @param UnitOfWorkInterface $unitOfWork       The UnitOfWork in which
     * @param InterceptorChainInterface $interceptorChain The interceptor chain that allows this interceptor to proceed the dispatch process
     * @return mixed the result of the command handler. May have been modified by interceptors.
     */
    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork,
            InterceptorChainInterface $interceptorChain);
}
