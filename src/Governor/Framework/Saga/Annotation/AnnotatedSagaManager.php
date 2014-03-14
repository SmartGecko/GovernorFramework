<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use Governor\Framework\Saga\SagaInitializationPolicy;
use Governor\Framework\Saga\SagaCreationPolicy;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Saga\AbstractSagaManager;
use Governor\Framework\Saga\SagaInterface;

/**
 * Description of AnnotatedSagaManager
 *
 * @author david
 */

/**
 * Implementation of the SagaManager that uses annotations on the Sagas to describe the lifecycle management. Unlike
 * the SimpleSagaManager, this implementation can manage several types of Saga in a single AnnotatedSagaManager.
 *
 * @author Allard Buijze
 * @since 0.7
 */
class AnnotatedSagaManager extends AbstractSagaManager
{

    private $parameterResolverFactory;

    protected function getSagaCreationPolicy($sagaType,
            EventMessageInterface $event)
    {
        $inspector = new SagaMethodMessageHandlerInspector($sagaType);
        $handlers = $inspector->getMessageHandlers($event);

        foreach ($handlers as $handler) {
            if ($handler->getCreationPolicy() !== SagaCreationPolicy::NONE) {
                return new SagaInitializationPolicy($handler->getCreationPolicy(),
                        $handler->getAssociationValue($event));
            }
        }

        return new SagaInitializationPolicy(SagaCreationPolicy::NONE, null);
    }

    protected function extractAssociationValues($sagaType,
            EventMessageInterface $event)
    {
        $inspector = new SagaMethodMessageHandlerInspector($sagaType);
        $handlers = $inspector->getMessageHandlers($event);
        $values = array();

        foreach ($handlers as $handler) {
            $values[] = $handler->getAssociationValue($event);
        }

        print_r($values);
        return $values;
    }

    protected function preProcessSaga(SagaInterface $saga)
    {
        if (null !== $this->parameterResolverFactory) {
            $saga->registerParameterResolverFactory($this->parameterResolverFactory);
        }
    }

    public function getTargetType()
    {
        //return getManagedSagaTypes().iterator().next();
    }

}
