<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Governor\Framework\Domain\EventMessageInterface;
use Governor\Framework\Common\ReflectionUtils;

/**
 * Description of SagaMethodMessageHandlerInspector
 *
 * @author 255196
 */
class SagaMethodMessageHandlerInspector
{

    const SAGA_EVENT_HANDLER_ANNOTATION = 'Governor\Framework\Annotations\SagaEventHandler';

    private $targetSaga;    
    private $reader;    

    public function __construct($targetSaga)
    {
        $this->targetSaga = $targetSaga;
        $this->reader = new AnnotationReader();        
    }

    public function getMessageHandlers(EventMessageInterface $event)
    {
        $found = array();
        $reflectionClass = ReflectionUtils::getClass($this->targetSaga);
        foreach (ReflectionUtils::getMethods($reflectionClass) as $method) {
            $annot = $this->reader->getMethodAnnotation($method,
                self::SAGA_EVENT_HANDLER_ANNOTATION);

            if (null !== $annot) {
                $parameter = current($method->getParameters());               

                if (null !== $parameter->getClass() &&
                    $parameter->getClass()->name === $event->getPayloadType()) {
                    $found[] = SagaMethodMessageHandler::getInstance($event,
                            $method, $annot);
                }
            }
        }
        
        return $found;
    }

    public function findHandlerMethod(AbstractAnnotatedSaga $target,
        EventMessageInterface $event)
    {
        foreach ($this->getMessageHandlers($event) as $handler) {
            $associationValue = $handler->getAssociationValue($event);
            if ($target->getAssociationValues()->contains($associationValue)) {                
                return $handler;
            }
        }

        return SagaMethodMessageHandler::noHandlers();
        /*   for (SagaMethodMessageHandler handler : getMessageHandlers(event)) {
          final AssociationValue associationValue = handler.getAssociationValue(event);
          if (target.getAssociationValues().contains(associationValue)) {
          return handler;
          } else if (logger.isDebugEnabled()) {
          logger.debug(
          "Skipping handler [{}], it requires an association value [{}:{}] that this Saga is not associated with",
          handler.getName(),
          associationValue.getKey(),
          associationValue.getValue());
          }
          }
          if (logger.isDebugEnabled()) {
          logger.debug("No suitable handler was found for event of type", event.getPayloadType().getName());
          }
          return SagaMethodMessageHandler.noHandler(); */
    }

    public function getSagaType()
    {
        return $this->sagaType;
    }

}
