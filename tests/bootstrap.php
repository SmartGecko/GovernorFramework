<?php

$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->add('Governor\\Tests', __DIR__);

Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
