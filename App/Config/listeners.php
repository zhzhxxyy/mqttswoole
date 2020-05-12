<?php
return [
    //Server::onStart
    'start' => [
    ],
    //Server::onWorkerStart
    'workerStart' => [
        [\App\Listens\Pool::class, 'workerStart'],
    ],
];
