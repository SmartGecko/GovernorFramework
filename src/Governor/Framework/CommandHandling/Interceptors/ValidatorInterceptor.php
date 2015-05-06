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

namespace Governor\Framework\CommandHandling\Interceptors;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterceptorInterface;
use Governor\Framework\CommandHandling\CommandDispatchInterceptorInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\InterceptorChainInterface;

/**
 * Implementation of the {@see CommandHandlerInterceptorInterface} interface that 
 * performs a structural validation of the command payload. 
 * 
 * The command payload is validated using the Symfony Validator component. 
 * A {@see ValidatorException} is throws upon an unsuccessfull validation attempt.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ValidatorInterceptor implements CommandHandlerInterceptorInterface, CommandDispatchInterceptorInterface
{

    /**
     * @var ValidatorInterface 
     */
    private $validator;

    function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function handle(CommandMessageInterface $command,
            UnitOfWorkInterface $unitOfWork,
            InterceptorChainInterface $interceptorChain)
    {
        return $interceptorChain->proceed($this->doHandle($command));
    }

    private function doHandle(CommandMessageInterface $command)
    {
        $violations = $this->validator->validate($command->getPayload());

        if (0 !== $violations->count()) {
            throw new ValidatorException("One or more constraints were violated.",
            $violations);
        }

        return $command;
    }

    public function dispatch(CommandMessageInterface $command)
    {
        return $this->doHandle($command);
    }

}
