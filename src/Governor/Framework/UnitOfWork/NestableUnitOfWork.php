<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\UnitOfWork;

/**
 * Description of DummyUnitOfWork
 *
 * @author david
 */
abstract class NestableUnitOfWork implements UnitOfWorkInterface
{

    private $outerUnitOfWork;
    private $innerUnitsOfWork = array();
    private $isStarted;

    /**
     * @var \Psr\Log\LoggerInterface 
     */
    protected $logger;

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
                $listener = new CommitOnOuterCommitTask(function(UnitOfWorkInterface $uow ) {
                    $this->performInnerCommit();
                },
                        function (UnitOfWorkInterface $uow) {
                    $this->performCleanup();
                },
                        function (UnitOfWorkInterface $uow, \Exception $ex = null) {
                    CurrentUnitOfWork::set($this);
                    $this->rollback($ex);
                });

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
            $this->logger->debug("Rollback requested for Unit Of Work due to exception. {} ",
                    $ex->getMessage());
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
     * Executes the logic required to commit this unit of work.
     *
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
}

class CommitOnOuterCommitTask extends UnitOfWorkListenerAdapter
{

    private $commitClosure;
    private $cleanupClosure;
    private $rollbackClosure;

    function __construct(\Closure $commitClosure, \Closure $cleanupClosure,
            \Closure $rollbackClosure)
    {
        $this->commitClosure = $commitClosure;
        $this->cleanupClosure = $cleanupClosure;
        $this->rollbackClosure = $rollbackClosure;
    }

    public function afterCommit(UnitOfWorkInterface $unitOfWork)
    {
        $cb = $this->commitClosure;
        $cb($unitOfWork);
    }

    public function onCleanup(UnitOfWorkInterface $unitOfWork)
    {
        $cb = $this->cleanupClosure;
        $cb($unitOfWork);
    }

    public function onRollback(UnitOfWorkInterface $unitOfWork,
            \Exception $failureCause = null)
    {
        $cb = $this->rollbackClosure;
        $cb($unitOfWork, $failureCause);
    }

}
