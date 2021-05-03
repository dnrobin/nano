# nano

A tiny API framework written in PHP for quick project prototypes.

# Getting Started

'''
<?php

require_once __DIR__ . '/vendor/autoload.php';

$api = new nano\Server();

$api->get('/', function () {
    echo "Hello, World!";
})

$api.run();
'''
