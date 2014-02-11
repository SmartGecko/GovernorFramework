<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Annotations;

/**
 * @Annotation
 * @Target("METHOD")
 */
final class CommandHandler
{

    public $commandName;

}
