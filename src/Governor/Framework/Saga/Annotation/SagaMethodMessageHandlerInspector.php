<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Saga\Annotation;

use Governor\Framework\Domain\EventMessageInterface;

/**
 * Description of SagaMethodMessageHandlerInspector
 *
 * @author 255196
 */
class SagaMethodMessageHandlerInspector
{

    private $sagaType;

    public function __construct($sagaType)
    {
        $this->sagaType = $sagaType;
    }

    public function getMessageHandlers(EventMessageInterface $event)
    {
        /* List<SagaMethodMessageHandler> found = new ArrayList<SagaMethodMessageHandler>(1);
          for (SagaMethodMessageHandler handler : handlers) {
          if (handler.matches(event)) {
          found.add(handler);
          }
          }
          return found; */
    }

    public function findHandlerMethod(AbstractAnnotatedSaga $target,
            EventMessageInterface $event)
    {
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
