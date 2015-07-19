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

use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\CommandHandling\CommandMessageInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkInterface;
use Governor\Framework\UnitOfWork\UnitOfWorkListenerAdapter;

/**
 * Extension of the UnitOfWorkListenerAdapter providing auditing information.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
class AuditingUnitOfWorkListener extends UnitOfWorkListenerAdapter
{

    /**
     * @var AuditDataProviderInterface
     */
    private $auditDataProvider;

    /**
     * @var AuditLoggerInterface
     */
    private $auditLogger;

    /**
     * @var CommandMessageInterface
     */
    private $command;

    /**
     * @var array
     */
    private $recordedEvents = [];

    /**
     * @var mixed
     */
    private $returnValue;

    /**
     * Initialize a listener for the given <code>command</code>. The <code>auditDataProvider</code> is called before
     * the Unit Of Work is committed to provide the auditing information. The <code>auditLogger</code> is invoked after
     * the Unit Of Work is successfully committed.
     *
     * @param CommandMessageInterface $command The command being audited
     * @param AuditDataProviderInterface $auditDataProvider The instance providing the information to attach to the events
     * @param AuditLoggerInterface $auditLogger The logger writing the audit
     */
    public function __construct(
        CommandMessageInterface $command,
        AuditDataProviderInterface $auditDataProvider,
        AuditLoggerInterface $auditLogger
    ) {
        if (null === $command) {
            throw new \InvalidArgumentException('command may not be null');
        }

        if (null === $auditDataProvider) {
            throw new \InvalidArgumentException('auditDataProvider may not be null');
        }

        if (null === $auditLogger) {
            throw new \InvalidArgumentException('auditLogger may not be null');
        }

        $this->auditDataProvider = $auditDataProvider;
        $this->auditLogger = $auditLogger;
        $this->command = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        $this->auditLogger->logSuccessful(
            $this->command,
            $this->returnValue,
            $this->recordedEvents
        );
    }

    /**
     * {@inheritdoc}
     */
    public function onEventRegistered(
        UnitOfWorkInterface $unitOfWork,
        EventMessageInterface $event
    ) {
        $auditData = $this->auditDataProvider->provideAuditDataFor($this->command);

        if (!empty($auditData)) {
            $event = $event->andMetaData($auditData);
        }

        $this->recordedEvents[] = $event;

        return $event;
    }

    /**
     * {@inheritdoc}
     */
    public function onRollback(
        UnitOfWorkInterface $unitOfWork,
        \Exception $failureCause = null
    ) {
        $this->auditLogger->logFailed(
            $this->command,
            $failureCause,
            $this->recordedEvents
        );
    }

    /**
     * Registers the return value of the command handler with the auditing context.
     *
     * @param mixed $returnValue The return value of the command handler, if any. May be <code>null</code>.
     */
    public function setReturnValue($returnValue)
    {
        $this->returnValue = $returnValue;
    }

}
