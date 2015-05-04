<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Interface describing a mechanism that creates implementations of a Saga.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface SagaFactoryInterface
{

    /**
     * Create a new instance of a Saga of given type. The Saga must be fully initialized and resources it depends on
     * must have been provided (injected or otherwise).
     *
     * @param string $sagaType The type of saga to create an instance for     
     * @return SagaInterface A fully initialized instance of a saga of given type
     */
    public function createSaga($sagaType);

    /**
     * Indicates whether or not this factory can create instances of the given <code>sagaType</code>.
     *
     * @param string $sagaType The type of Saga
     * @return boolean <code>true</code> if this factory can create instance of the given <code>sagaType</code>,
     *         <code>false</code> otherwise.
     */
    public function supports($sagaType);
}
