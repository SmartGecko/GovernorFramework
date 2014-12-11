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

namespace Governor\Tests\CommandHandling\Interceptors;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ValidatorBuilder;
use Governor\Framework\CommandHandling\GenericCommandMessage;
use Governor\Framework\CommandHandling\InterceptorChainInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\Interceptors\ValidatorInterceptor;
use Governor\Framework\CommandHandling\Interceptors\ValidatorException;

/**
 * ValidatorInterceptor unit tests.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class ValidatorInterceptorTest extends \PHPUnit_Framework_TestCase
{

    private $testSubject;
    private $mockInterceptorChain;
    private $uow;

    public function setUp()
    {
        $validatorBuilder = new ValidatorBuilder();
        $validatorBuilder->enableAnnotationMapping();

        $this->testSubject = new ValidatorInterceptor($validatorBuilder->getValidator());
        $this->mockInterceptorChain = $this->getMock(InterceptorChainInterface::class);
        $this->uow = $this->getMock(UnitOfWorkInterface::class);
    }

    public function testValidateSimpleObject()
    {
        $command = new GenericCommandMessage(new \stdClass());

        $this->mockInterceptorChain->expects($this->once())
                ->method('proceed');

        $this->testSubject->handle($command, $this->uow,
                $this->mockInterceptorChain);
    }

    public function testValidateAnnotatedObject_IllegalNullValue()
    {
        $this->mockInterceptorChain->expects($this->never())
                ->method('proceed');
        try {
            $this->testSubject->handle(new GenericCommandMessage(new AnnotatedInstance(null)),
                    $this->uow, $this->mockInterceptorChain);
            $this->fail("Expected exception");
        } catch (ValidatorException $ex) {
            $this->assertFalse(empty($ex->getViolationList()));
        }
    }

    public function testValidateAnnotatedObject_LegalValue()
    {
        $commandMessage = new GenericCommandMessage(new AnnotatedInstance("abc"));

        $this->mockInterceptorChain->expects($this->once())
                ->method('proceed');
        $this->testSubject->handle($commandMessage, $this->uow,
                $this->mockInterceptorChain);
    }
    
    public function testValidateAnnotatedObjectDispatch_LegalValue() 
    {
        $commandMessage = new GenericCommandMessage(new AnnotatedInstance("abc"));
        
        $this->testSubject->dispatch($commandMessage);
    }
    
}

class AnnotatedInstance
{

    /**
     * @Assert\Regex("/^ab./")
     * @Assert\NotNull
     */
    private $notNull;

    public function __construct($notNull)
    {
        $this->notNull = $notNull;
    }

}
