<?php 

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Request\Request;

class Controller
{
    public function home(): void
    {
        echo 'Hello!';
    }

    public function test(Request $request): void
    {
        echo "Here is your number: {$request->getRouteParam('num')}";
    }

    public function params(Request $request): void
    {
        echo "Params: {$request->getRouteParams()}";
    }

    public function something(Request $request): void
    {
        echo "Category: {$request->getRouteParam('cat')}, ID: {$request->getRouteParam('id')}";
    }
}