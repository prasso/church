<?php

namespace Prasso\Church\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Prasso\Church\Services\VisitorService visitors()
 * @method static \Prasso\Church\Models\Member|null member()
 * @method static bool isMember()
 * @method static bool isGuest()
 * 
 * @see \Prasso\Church\Church
 */
class Church extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'church';
    }
}
