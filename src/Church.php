<?php

namespace Prasso\Church;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Prasso\Church\Services\VisitorService visitors()
 * @method static \Prasso\Church\Models\Member|null member()
 * @method static bool isMember()
 * @method static bool isGuest()
 */
class Church
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new Church instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get the visitor service instance.
     *
     * @return \Prasso\Church\Services\VisitorService
     */
    public function visitors()
    {
        return new Services\VisitorService();
    }

    /**
     * Get the current authenticated member.
     *
     * @return \Prasso\Church\Models\Member|null
     */
    public function member()
    {
        $user = $this->app['auth']->user();
        
        if (! $user) {
            return null;
        }
        
        return $user->member ?? null;
    }

    /**
     * Check if the current user is a member.
     *
     * @return bool
     */
    public function isMember()
    {
        return $this->member() !== null;
    }

    /**
     * Check if the current user is a guest.
     *
     * @return bool
     */
    public function isGuest()
    {
        return ! $this->isMember();
    }

    /**
     * Handle dynamic method calls into the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Handle service accessors
        if (in_array($method, ['visitors'])) {
            return $this->$method();
        }

        // Handle property accessors
        if (in_array($method, ['member', 'isMember', 'isGuest'])) {
            return $this->$method();
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist on [".static::class.'].');
    }
}

/**
 * @see \Prasso\Church\Church
 */
class ChurchFacade extends Facade
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
