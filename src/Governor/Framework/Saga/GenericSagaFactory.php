<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga;

/**
 * Description of GenericSagaFactory
 *
 * @author david
 */
class GenericSagaFactory implements SagaFactoryInterface
{

    /**
     * @var ResourceInjectorInterface
     */
    private $resourceInjector;

    public function __construct(ResourceInjectorInterface $resourceInjector = null)
    {
        $this->resourceInjector = (null === $resourceInjector) ? new NullResourceInjector() : $resourceInjector;
    }

    public function createSaga($sagaType)
    {        
        $reflectionClass = new \ReflectionClass($sagaType);

        if (!$this->supports($sagaType)) {
            throw new \InvalidArgumentException("The given sagaType must be a subtype of SagaInterface");
        }

        /** @var SagaInterface $instance */
        $instance = $reflectionClass->newInstanceArgs();
        $this->resourceInjector->injectResources($instance);

        return $instance;
    }

    public function supports($sagaType)
    {
        $reflectionClass = new \ReflectionClass($sagaType);
        if ($reflectionClass->implementsInterface(SagaInterface::class)) {
            return true;
        }

        return false;
    }

}
