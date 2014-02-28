<?php

namespace Governor\Framework\EventStore;

class EventStreamNotFoundException extends EventStoreException
{

    public function __construct($type, $identifier)
    {
        parent::__construct(sprintf("Aggregate of type [%s] with identifier [%s] cannot be found.",
                        $type, $identifier), 0, null);
    }

}
