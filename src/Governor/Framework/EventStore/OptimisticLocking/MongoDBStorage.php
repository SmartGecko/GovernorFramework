<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Governor\Framework\EventStore\OptimisticLocking;

use Doctrine\MongoDB\Connection;

/**
 * Description of MongoDBStorage
 *
 * @author david
 */
class MongoDBStorage implements Storage
{

    private $connection;
    private $database;
    private $collection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function contains($id)
    {
        
    }

    public function load($id)
    {
        
    }

    public function store($id, $className, $eventData, $nextVersion,
        $currentVersion)
    {
     //   print_r($id);
       // print_r($className);
       //print_r($eventData);
        print_r($nextVersion);
        echo "\n -> \n";
        print_r($currentVersion);
        die();
    }

}
