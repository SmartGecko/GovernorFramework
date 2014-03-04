<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Interface describing a mechanism to inject resources into Saga instances.
 */
interface ResourceInjectorInterface {

    /**
     * Inject required resources into the given <code>saga</code>.
     *
     * @param SagaInterface $saga The saga to inject resources into
     */
    public function injectResources(SagaInterface $saga);

}