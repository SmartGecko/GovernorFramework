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

namespace Governor\Framework\Audit;

use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\CommandHandling\InterceptorChainInterface;
use Governor\Framework\CommandHandling\CommandHandlerInterceptorInterface;

/**
 * Description of AuditingInterceptor
 *
 * @author david
 */
class AuditingInterceptor implements CommandHandlerInterceptorInterface
{

    /**
     * @var AuditDataProviderInterface 
     */
    private $auditDataProvider;

    /**
     * @var AuditLoggerInterface
     */
    private $auditLogger;

    public function __construct()
    {
        $this->auditDataProvider = new EmptyDataProvider();
        $this->auditLogger = new NullAuditLogger();
    }

    public function handle(CommandMessageInterface $command,
            UnitOfWorkInterface $unitOfWork,
            InterceptorChainInterface $interceptorChain)
    {
        $auditListener = new AuditingUnitOfWorkListener($command,
                $this->auditDataProvider, $this->auditLogger);

        $unitOfWork->registerListener($auditListener);
        $returnValue = $interceptorChain->proceed();
        $auditListener->setReturnValue($returnValue);

        return $returnValue;
    }

    public function setAuditDataProvider(AuditDataProviderInterface $auditDataProvider)
    {
        $this->auditDataProvider = $auditDataProvider;
    }

    public function setAuditLogger(AuditLoggerInterface $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

}
