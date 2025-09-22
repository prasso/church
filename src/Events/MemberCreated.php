<?php

namespace Prasso\Church\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Prasso\Church\Models\Member;

class MemberCreated
{
    use Dispatchable;

    /**
     * The member instance.
     *
     * @var \Prasso\Church\Models\Member
     */
    public $member;

    /**
     * Create a new event instance.
     *
     * @param  \Prasso\Church\Models\Member  $member
     * @return void
     */
    public function __construct(Member $member)
    {
        $this->member = $member;
    }
}
