<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Church\Models\ChurchModel;

class PrayerRequest extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_prayer_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'member_id',
        'requested_by',
        'is_anonymous',
        'is_public',
        'status',
        'prayer_count',
        'answer',
        'answered_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_anonymous' => 'boolean',
        'is_public' => 'boolean',
        'prayer_count' => 'integer',
        'answered_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_anonymous' => false,
        'is_public' => true,
        'status' => 'active',
        'prayer_count' => 0,
    ];

    /**
     * Get the member who made the prayer request.
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the member who submitted the request (if different from the member).
     */
    public function requestedBy()
    {
        return $this->belongsTo(Member::class, 'requested_by');
    }

    /**
     * Get the prayer groups this request belongs to.
     */
    public function prayerGroups()
    {
        return $this->belongsToMany(Group::class, 'chm_prayer_group_requests', 'prayer_request_id', 'group_id')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include public prayer requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include active prayer requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Mark the prayer request as answered.
     *
     * @param  string  $answer
     * @return bool
     */
    public function markAsAnswered($answer = null)
    {
        return $this->update([
            'status' => 'answered',
            'answer' => $answer,
            'answered_at' => now(),
        ]);
    }

    /**
     * Increment the prayer count.
     *
     * @param  int  $amount
     * @return int
     */
    public function incrementPrayerCount($amount = 1)
    {
        return $this->increment('prayer_count', $amount);
    }
}
