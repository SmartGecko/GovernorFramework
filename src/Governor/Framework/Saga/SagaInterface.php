<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Interface describing an implementation of a Saga. Sagas are instances that handle events and may possibly produce
 * new commands or have other side effects. Typically, Sagas are used to manage long running business transactions.
 * <p/>
 * Multiple instances of a single type of Saga may exist. In that case, each Saga will be managing a different
 * transaction. Sagas need to be associated with concepts in order to receive specific events. These associations are
 * managed through AssociationValues. For example, to associate a saga with an Order with ID 1234, this saga needs an
 * association value with key <code>"orderId"</code> and value <code>"1234"</code>.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface SagaInterface {

    /**
     * Returns the unique identifier of this saga.
     *
     * @return string the unique identifier of this saga
     */
    public function getSagaIdentifier();

    /**
     * Returns a view on the Association Values for this saga instance. The returned instance is mutable.
     *
     * @return AssociationValuesInterface a view on the Association Values for this saga instance
     */
    public function getAssociationValues();

    /**
     * Handle the given event. The actual result of processing depends on the implementation of the saga.
     * <p/>
     * Implementations are highly discouraged from throwing exceptions.
     *
     * @param EventMessageInterface $event the event to handle
     */
    public function handle(EventMessageInterface $event);

    /**
     * Indicates whether or not this saga is active. A Saga is active when its life cycle has not been ended.
     *
     * @return boolean <code>true</code> if this saga is active, <code>false</code> otherwise.
     */
    public function isActive();
}
