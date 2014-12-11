<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\Plugin\SymfonyBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Description of AbstractHandlerPass
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class AbstractHandlerPass implements CompilerPassInterface
{

    protected function getHandlerIdentifier($prefix)
    {
        return sprintf("%s.%s", $prefix,
                hash('crc32', openssl_random_pseudo_bytes(8)));
    }

}
