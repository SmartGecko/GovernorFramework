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

namespace Governor\Tests\CommandHandling;

use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\InterceptorChainInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\DefaultInterceptorChain;
use Governor\Framework\CommandHandling\CommandHandlerInterceptorInterface;

/**
 * Description of DefaultInterceptorChainTest
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class DefaultInterceptorChainTest extends \PHPUnit_Framework_TestCase
{

    private $mockUnitOfWork;
    private $mockCommandHandler;

    public function setUp()
    {
        $this->mockUnitOfWork = \Phake::mock(UnitOfWorkInterface::class);
        $this->mockCommandHandler = \Phake::mock(CommandHandlerInterface::class);
    }

    public function testChainWithDifferentProceedCalls()
    {
        $interceptor1 = new Interceptor1();
        $interceptor2 = new Interceptor2();

        \Phake::when($this->mockCommandHandler)->handle(\Phake::anyParameters())->thenReturn('Result');

        $testSubject = new DefaultInterceptorChain(GenericCommandMessage::asCommandMessage(new Payload("original")),
                $this->mockUnitOfWork, $this->mockCommandHandler,
                array($interceptor1, $interceptor2));

        $actual = $testSubject->proceed();

        $this->assertSame("Result", $actual);
    }

}

class Payload
{

    private $value;

    function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

}

class Interceptor1 implements CommandHandlerInterceptorInterface
{

    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork,
            InterceptorChainInterface $interceptorChain)
    {
        return $interceptorChain->proceed(GenericCommandMessage::asCommandMessage(new Payload("testing")));
    }

}

class Interceptor2 implements CommandHandlerInterceptorInterface
{

    public function handle(CommandMessageInterface $commandMessage,
            UnitOfWorkInterface $unitOfWork,
            InterceptorChainInterface $interceptorChain)
    {
        return $interceptorChain->proceed();
    }

}
