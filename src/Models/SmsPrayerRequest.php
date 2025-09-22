<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Prasso\Messaging\Models\MsgInboundMessage;
use Prasso\Messaging\Models\MsgGuest;
use Prasso\Messaging\Models\MsgMessage;

class SmsPrayerRequest extends ChurchModel
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'chm_sms_prayer_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'msg_inbound_message_id',
        'msg_guest_id',
        'prayer_request_id',
        'content',
        'sender_name',
        'sender_phone',
        'status',
        'is_processed',
        'processed_at',
        'processed_by',
        'campaign_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'new',
        'is_processed' => false,
    ];

    /**
     * Get the inbound message that contains this prayer request.
     */
    public function inboundMessage()
    {
        return $this->belongsTo(MsgInboundMessage::class, 'msg_inbound_message_id');
    }

    /**
     * Get the guest who sent this prayer request.
     */
    public function guest()
    {
        return $this->belongsTo(MsgGuest::class, 'msg_guest_id');
    }

    /**
     * Get the prayer request if it has been converted to a formal prayer request.
     */
    public function prayerRequest()
    {
        return $this->belongsTo(PrayerRequest::class, 'prayer_request_id');
    }

    /**
     * Scope a query to only include unprocessed prayer requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('is_processed', false);
    }

    /**
     * Scope a query to only include prayer requests with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Convert this SMS prayer request to a formal prayer request.
     *
     * @param  array  $attributes
     * @return \Prasso\Church\Models\PrayerRequest
     */
    public function convertToPrayerRequest(array $attributes = [])
    {
        // Create a new prayer request
        $prayerRequest = PrayerRequest::create(array_merge([
            'title' => 'Prayer Request from SMS',
            'description' => $this->content,
            'is_anonymous' => false,
            'is_public' => true,
            'status' => 'active',
            'metadata' => [
                'source' => 'sms',
                'phone' => $this->sender_phone,
                'original_sms_prayer_request_id' => $this->id,
            ],
        ], $attributes));

        // Link this SMS prayer request to the formal prayer request
        $this->update([
            'prayer_request_id' => $prayerRequest->id,
            'is_processed' => true,
            'processed_at' => now(),
            'status' => 'converted',
        ]);

        return $prayerRequest;
    }

    /**
     * Mark this SMS prayer request as processed.
     *
     * @param  int|null  $processedBy
     * @param  string|null  $status
     * @return bool
     */
    public function markAsProcessed($processedBy = null, $status = 'processed')
    {
        return $this->update([
            'is_processed' => true,
            'processed_at' => now(),
            'processed_by' => $processedBy,
            'status' => $status,
        ]);
    }
}
