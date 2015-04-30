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

namespace Governor\Framework\UnitOfWork;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Governor\Framework\Common\Logging\NullLogger;

/**
 * Description of DummyUnitOfWork
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
abstract class NestableUnitOfWork implements UnitOfWorkInterface, LoggerAwareInterface
{

    /**
     * @var UnitOfWorkInterface
     */
    private $outerUnitOfWork;

    /**
     * @var NestableUnitOfWork[]
     */
    private $innerUnitsOfWork = [];

    /**
     * @var bool
     */
    private $isStarted;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function commit()
    {
        $this->logger->debug("Committing Unit Of Work");
        $this->assertStarted();
        try {
            $this->notifyListenersPrepareCommit();
            $this->saveAggregates();
            if (null === $this->outerUnitOfWork) {
                $this->logger->debug("This Unit Of Work is not nested. Finalizing commit...");
                $this->doCommit();
                $this->stop();
                $this->performCleanup();
            } else {
                $this->logger->debug("This Unit Of Work is nested. Commit will be finalized by outer Unit Of Work.");
            }
        } catch (\RuntimeException $ex) {
            $this->logger->debug("An error occurred while committing this UnitOfWork. Performing rollback...");
            $this->doRollback($ex);
            $this->stop();
            if (null === $this->outerUnitOfWork) {
                $this->performCleanup();
            }

            throw $ex;
        } finally {
            $this->logger->debug("Clearing resources of this Unit Of Work.");
            $this->clear();
        }
    }

    private function performCleanup()
    {
        foreach ($this->innerUnitsOfWork as $uow) {
            $uow->performCleanup();
        }
        $this->notifyListenersCleanup();
    }

    public function start()
    {
        $this->logger->debug("Starting Unit Of Work.");
        if ($this->isStarted) {
            throw new \RuntimeException("UnitOfWork is already started");
        }

        $this->doStart();
        if (CurrentUnitOfWork::isStarted()) {
            // we're nesting.
            $this->outerUnitOfWork = CurrentUnitOfWork::get();

            if ($this->outerUnitOfWork instanceof NestableUnitOfWork) {
                $this->outerUnitOfWork->registerInnerUnitOfWork($this);
            } else {
                $listener = new CommitOnOuterCommitTask(
                    function (UnitOfWorkInterface $uow) {
                        $this->performInnerCommit();
                    },
                    function (UnitOfWorkInterface $uow) {
                        $this->performCleanup();
                    },
                    function (UnitOfWorkInterface $uow, \Exception $ex = null) {
                        CurrentUnitOfWork::set($this);
                        $this->rollback($ex);
                    }
                );

                $this->outerUnitOfWork->registerListener($listener);
            }
        }
        $this->logger->debug("Registering Unit Of Work as CurrentUnitOfWork");
        CurrentUnitOfWork::set($this);
        $this->isStarted = true;
    }

    public function rollback(\Exception $ex = null)
    {
        if (null !== $ex) {
            $this->logger->debug(
                "Rollback requested for Unit Of Work due to exception. {exception} ",
                array('exception' => $ex->getMessage())
            );
        } else {
            $this->logger->debug("Rollback requested for Unit Of Work for unknown reason.");
        }

        try {
            if ($this->isStarted()) {
                foreach ($this->innerUnitsOfWork as $inner) {
                    CurrentUnitOfWork::set($inner);
                    $inner->rollback($ex);
                }
                $this->doRollback($ex);
            }
        } catch (\Exception $ex) {

        }

        if (null === $this->outerUnitOfWork) {
            $this->performCleanup();
        }

        $this->clear();
        $this->stop();

        if ($ex) {
            throw $ex;
        }
    }

    public function isStarted()
    {
        return $this->isStarted;
    }

    /**
     * Performs logic required when starting this UnitOfWork instance.
     */
    protected abstract function doStart();

    /**
     * Executes the logic required to commit this unit of work.
     */
    protected abstract function doCommit();

    /**
     * Executes the logic required to rollback this unit of work.
     *
     * @param \Exception|null $ex
     */
    protected abstract function doRollback(\Exception $ex = null);

    private function performInnerCommit()
    {
        $exception = null;
        $this->logger->debug("Finalizing commit of inner Unit Of Work...");
        CurrentUnitOfWork::set($this);

        try {
            $this->doCommit();
        } catch (\RuntimeException $ex) {
            $this->doRollback($ex);
            $exception = $ex;
        }

        $this->clear();
        $this->stop();

        if (null !== $exception) {
            throw $exception;
        }
    }

    private function assertStarted()
    {
        if (!$this->isStarted) {
            throw new \RuntimeException("UnitOfWork is not started");
        }
    }

    private function stop()
    {
        $this->logger->debug("Stopping Unit Of Work");
        $this->isStarted = false;
    }

    private function clear()
    {
        CurrentUnitOfWork::clear($this);
    }

    /**
     * Commit all registered inner units of work. This should be invoked after events have been dispatched and before
     * any listeners are notified of the commit.
     */
    protected function commitInnerUnitOfWork()
    {
        foreach ($this->innerUnitsOfWork as $unitOfWork) {
            if ($unitOfWork->isStarted()) {
                $unitOfWork->performInnerCommit();
            }
        }
    }

    /**
     * @param NestableUnitOfWork $unitOfWork
     */
    private function registerInnerUnitOfWork(NestableUnitOfWork $unitOfWork)
    {
        $this->innerUnitsOfWork[] = $unitOfWork;
    }

    /**
     * Saves all registered aggregates by calling their respective callbacks.
     */
    protected abstract function saveAggregates();

    protected abstract function notifyListenersPrepareCommit();

    protected abstract function notifyListenersCleanup();

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

}

class CommitOnOuterCommitTask extends UnitOfWorkListenerAdapter
{
    /**
     * @var \Closure
     */
    private $commitClosure;

    /**
     * @var \Closure
     */
    private $cleanupClosure;

    /**
     * @var \Closure
     */
    private $rollbackClosure;

    /**
     * @param callable $commitClosure
     * @param callable $cleanupClosure
     * @param callable $rollbackClosure
     */
    function __construct(
        \Closure $commitClosure,
        \Closure $cleanupClosure,
        \Closure $rollbackClosure
    ) {
        $this->commitClosure = $commitClosure;
        $this->cleanupClosure = $cleanupClosure;
        $this->rollbackClosure = $rollbackClosure;
    }

    /**
     * @param UnitOfWorkInterface $unitOfWork
     */
    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        $cb = $this->commitClosure;
        $cb($unitOfWork);
    }

    /**
     * @param UnitOfWorkInterface $unitOfWork
     */
    public function onCleanup(UnitOfWorkInterface $unitOfWork)
    {
        $cb = $this->cleanupClosure;
        $cb($unitOfWork);
    }

    /**
     * @param UnitOfWorkInterface $unitOfWork
     * @param \Exception $failureCause
     */
    public function onRollback(
        UnitOfWorkInterface $unitOfWork,
        \Exception $failureCause = null
    ) {
        $cb = $this->rollbackClosure;
        $cb($unitOfWork, $failureCause);
    }

}
