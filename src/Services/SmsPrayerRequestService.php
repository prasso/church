<?php

namespace Prasso\Church\Services;

use Illuminate\Support\Facades\Log;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Member;
use Prasso\Messaging\Models\MsgInboundMessage;
use Prasso\Messaging\Models\MsgGuest;
use Prasso\Messaging\Models\MsgMessage;

class SmsPrayerRequestService
{
    /**
     * Process an inbound message as a prayer request.
     *
     * @param  \Prasso\Messaging\Models\MsgInboundMessage  $inboundMessage
     * @param  array  $campaignData  Optional campaign data if this is from a specific campaign
     * @return \Prasso\Church\Models\PrayerRequest
     */
    public function processPrayerRequestMessage(MsgInboundMessage $inboundMessage, array $campaignData = [])
    {
        $smsData = [
            'inbound_message_id' => $inboundMessage->id,
            'guest_id' => $inboundMessage->msg_guest_id,
            'body' => $inboundMessage->body,
            'from' => $inboundMessage->from,
            'received_at' => $inboundMessage->received_at,
            'raw' => $inboundMessage->raw,
        ];
        
        if (!empty($campaignData['campaign_id'])) {
            $smsData['campaign_id'] = $campaignData['campaign_id'];
            $smsData['campaign'] = $campaignData;
        }

        // Create title from the first line or first few words
        $title = $this->generateTitleFromContent($inboundMessage->body);
        
        // Create a new prayer request record
        $prayerRequest = PrayerRequest::createFromSms($smsData, [
            'title' => $title,
            'status' => 'pending',
        ]);

        // If we have a guest, try to get their name and find a member
        if ($inboundMessage->guest) {
            $memberInfo = $this->tryLinkToMember($inboundMessage->guest);
            
            if ($memberInfo) {
                $prayerRequest->update([
                    'member_id' => $memberInfo['member_id'],
                    'requested_by' => $memberInfo['member_id'],
                    'metadata->sender_name' => $memberInfo['name'],
                ]);
            } else {
                // Just store the guest name in metadata
                $prayerRequest->update([
                    'metadata->sender_name' => $inboundMessage->guest->name,
                ]);
            }
        }

        Log::info('SMS Prayer Request processed', [
            'prayer_request_id' => $prayerRequest->id,
            'from' => $inboundMessage->from,
        ]);

        return $prayerRequest;
    }

    /**
     * Try to link a prayer request to a member based on phone number.
     *
     * @param  \Prasso\Messaging\Models\MsgGuest  $guest
     * @return array|null
     */
    protected function tryLinkToMember(MsgGuest $guest)
    {
        // Try to find a member with this phone number
        $phone = $guest->phone;
        if (!$phone) {
            return null;
        }

        // Normalize the phone number for comparison
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Find members with matching phone numbers
        $member = Member::where(function($query) use ($normalizedPhone) {
            $query->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$normalizedPhone])
                  ->orWhereRaw("REGEXP_REPLACE(mobile_phone, '[^0-9]', '') = ?", [$normalizedPhone]);
        })->first();

        if ($member) {
            return [
                'member_id' => $member->id,
                'name' => $member->full_name,
            ];
        }

        return null;
    }

    /**
     * Get all SMS prayer requests for a specific campaign.
     *
     * @param  int  $campaignId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPrayerRequestsByCampaign($campaignId)
    {
        return PrayerRequest::fromSms()
            ->whereJsonContains('metadata->campaign_id', $campaignId)
            ->get();
    }

    /**
     * Get all unprocessed SMS prayer requests.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnprocessedPrayerRequests()
    {
        return PrayerRequest::fromSms()
            ->where('status', 'pending')
            ->get();
    }
    
    /**
     * Generate a title from the content of a prayer request.
     *
     * @param  string  $content
     * @return string
     */
    protected function generateTitleFromContent($content)
    {
        // If content is empty, return a default title
        if (empty($content)) {
            return 'Prayer Request from SMS';
        }
        
        // Get the first line or first 50 characters
        $firstLine = strtok($content, "\n");
        if (strlen($firstLine) <= 50) {
            return $firstLine;
        }
        
        // If first line is too long, get first few words
        $words = explode(' ', $content);
        $title = '';
        foreach ($words as $word) {
            if (strlen($title . ' ' . $word) > 50) {
                break;
            }
            $title .= ($title ? ' ' : '') . $word;
        }
        
        return $title . '...';
    }

    /**
     * Determine if an inbound message is likely a prayer request.
     * 
     * @param \Prasso\Messaging\Models\MsgInboundMessage $inboundMessage
     * @param array $campaignData
     * @return bool
     */
    public function isPrayerRequest(MsgInboundMessage $inboundMessage, array $campaignData = [])
    {
        // If this is a reply to a prayer request campaign, it's a prayer request
        if (!empty($campaignData['type']) && $campaignData['type'] === 'prayer_request') {
            return true;
        }

        // Check if the message contains prayer request keywords
        $body = strtolower($inboundMessage->body);
        $prayerKeywords = [
            'pray', 'prayer', 'please pray', 'pray for', 'prayer request',
            'need prayer', 'praying', 'prayers', 'pray that', 'prayer for'
        ];

        foreach ($prayerKeywords as $keyword) {
            if (strpos($body, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send a confirmation message to the sender of a prayer request.
     *
     * @param  \Prasso\Church\Models\PrayerRequest  $prayerRequest
     * @return \Prasso\Messaging\Models\MsgMessage|null
     */
    public function sendConfirmation(PrayerRequest $prayerRequest)
    {
        $guestId = $prayerRequest->metadata['msg_guest_id'] ?? null;
        if (!$guestId) {
            return null;
        }

        $guest = MsgGuest::find($guestId);
        if (!$guest) {
            return null;
        }

        $messagingService = new ChurchMessagingService();
        
        $subject = 'Prayer Request Received';
        $body = "Thank you for sharing your prayer request. Our prayer team has received it and will be praying for you. Reply STOP to unsubscribe.";
        
        $message = MsgMessage::create([
            'team_id' => $guest->team_id,
            'subject' => $subject,
            'body' => $body,
            'type' => 'sms',
            'metadata' => [
                'prayer_request_id' => $prayerRequest->id,
                'confirmation' => true,
            ],
        ]);

        // Attach the guest to the message
        $guest->messages()->attach($message->id);

        return $message;
    }
}
