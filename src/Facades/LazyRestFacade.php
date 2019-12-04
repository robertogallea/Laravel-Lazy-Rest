<?php

namespace robertogallea\LaravelLazyRest\Facades;

use Illuminate\Support\Facades\Facade;

class LazyRestFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function getFacadeAccessor()
    {
        return 'lazyrest';
    }
}