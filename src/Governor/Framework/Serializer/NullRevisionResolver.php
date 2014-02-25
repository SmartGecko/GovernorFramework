<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Serializer;

/**
 * Description of NullRevisionResolver
 *
 * @author david
 */
class NullRevisionResolver implements RevisionResolverInterface
{

    public function revisionOf($payloadType)
    {
        return null;
    }

}
