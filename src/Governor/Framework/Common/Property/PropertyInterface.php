<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Common\Property;

/**
 *
 * @author david
 */
interface PropertyInterface
{

    /**
     * @param $target Target class instance
     * @return mixed Property value
     */
    public function getValue($target);
}
