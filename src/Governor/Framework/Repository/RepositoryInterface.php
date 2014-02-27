<?php

namespace Governor\Framework\Repository;

use Governor\Framework\Domain\AggregateRootInterface;

interface RepositoryInterface
{

    public function add(AggregateRootInterface $object);

    /**
     * @param \Rhumsaa\Uuid\Uuid $id
     * @param integer $expectedVersion
     *
     * @return \Governor\Framework\Domain\AggregateRootInterface
     */
    public function load($aggregateId, $expectedVersion = null);

    /**
     * @param string $class Class name
     * @return boolean
     */
    public function supportsClass($class);
}
