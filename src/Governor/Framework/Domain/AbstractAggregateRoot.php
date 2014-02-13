<?php

namespace Governor\Framework\Domain;

abstract class AbstractAggregateRoot implements AggregateRootInterface
{

    /**
     * @var integer
     */
    protected $version;

    /**
     * @var EventContainer
     */
    private $eventContainer;

    /**
     *
     * @var boolean
     */
    private $deleted;

    /**
     *
     * @var integer
     */
    private $lastEventScn;

    protected function registerEvent($payload, MetaData $metaData = null)
    {
        $meta = (null === $metaData) ? new MetaData() : $metaData;

        return $this->getEventContainer()->addEvent($meta, $payload);
    }

    protected function markDeleted()
    {
        $this->deleted = true;
    }

    public function commitEvents()
    {
        if (null !== $this->eventContainer) {
            $this->lastEventScn = $this->eventContainer->getLastScn();
            $this->eventContainer->commit();
        }
    }

    public function getUncommittedEventCount()
    {
        return (null === $this->eventContainer) ? 0 : $this->eventContainer->size();
    }

    public function getUncommittedEvents()
    {
        if (null === $this->eventContainer) {
            return SimpleDomainEventStream::emptyStream();
        }

        return $this->eventContainer->getEventStream();
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function isDeleted()
    {
        return $this->deleted;
    }

    protected function getLastCommittedEventScn()
    {
        if (null === $this->eventContainer) {
            return $this->lastEventScn;
        }

        return $this->eventContainer->getLastCommitedScn();
    }

    /**
     * @return Governor\Framework\EventContainer
     */
    private function getEventContainer()
    {
        if (null === $this->eventContainer) {
            if (null === $this->getIdentifier()) {
                throw new AggregateRootIdNotInitialized("Aggregate Id unknown in [" .
                get_class($this) .
                "] Make sure the Aggregate Id is initialized before registering events.");
            }

            $this->eventContainer = new EventContainer($this->getIdentifier());
            $this->eventContainer->initializeSequenceNumber($this->lastEventScn);
        }

        return $this->eventContainer;
    }

    protected function initializeEventStream($lastScn)
    {
        $this->getEventContainer()->initializeSequenceNumber($lastScn);
        $this->lastEventScn = $lastScn >= 0 ? $lastScn : null;
    }

}
