<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\CommandHandling\Gateway;

use Governor\Framework\CommandHandling\CommandCallback;
/**
 *
 * @author david
 */
interface CommandGatewayInterface
{

    public function send($command, CommandCallback $callback = null);
}
