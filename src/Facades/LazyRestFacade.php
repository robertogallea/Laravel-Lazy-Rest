<?php

namespace robertogallea\LaravelLazyRest\Facades;

use Illuminate\Support\Facades\Facade;

class LazyRestFacade extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'lazyrest';
    }
}