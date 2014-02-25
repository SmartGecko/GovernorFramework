<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Interface towards a mechanism that resolves the revision of a given payload type. Based on this revision, a
 * component is able to recognize whether a serialized version of the payload is compatible with the
 * currently known version of the payload.
 */
interface RevisionResolverInterface
{

    /**
     * Returns the revision for the given <code>payloadType</code>.
     * <p/>
     * The revision is used by upcasters to decide whether they need to process a certain serialized event.
     * Generally, the revision needs to be modified each time the structure of an event has been changed in an
     * incompatible manner.
     *
     * @param payloadType The type for which to return the revision
     * @return the revision for the given <code>payloadType</code>
     */
    public function revisionOf($payloadType);
}
