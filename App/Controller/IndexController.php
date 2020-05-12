<?php
namespace App\Controller;

class IndexController
{
    public function index($request, $response)
    {
        $response->end('Hello.');
    }

    public function test($request, $response, $data)
    {
        $response->end(json_encode($data));
    }

    public function favicon($request, $response)
    {
        $response->end('');
    }
}
