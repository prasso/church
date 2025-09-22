<?php

namespace Prasso\Church\Services;

use Prasso\Messaging\Models\MsgGuest;
use Prasso\Messaging\Models\MsgMessage;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Visitor;
use Illuminate\Support\Facades\Log;

class ChurchMessagingService
{
    /**
     * Send a message to a member
     *
     * @param  \Prasso\Church\Models\Member  $member
     * @param  string  $subject
     * @param  string  $body
     * @param  string  $type  Message type (e.g., 'email', 'sms')
     * @param  array  $metadata  Additional metadata for the message
     * @return \Prasso\Messaging\Models\MsgMessage|null
     */
    public function sendToMember(Member $member, string $subject, string $body, string $type = 'email', array $metadata = [])
    {
        try {
            // If member has a user account, use it to find or create a guest record
            if ($member->user) {
                $guest = MsgGuest::firstOrCreate(
                    ['user_id' => $member->user->id],
                    [
                        'name' => $member->full_name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                        'is_subscribed' => true,
                    ]
                );
            } else {
                // For members without user accounts, find or create by email or phone
                $guest = MsgGuest::firstOrCreate(
                    ['email' => $member->email],
                    [
                        'name' => $member->full_name,
                        'phone' => $member->phone,
                        'is_subscribed' => true,
                    ]
                );
            }

            return $this->sendToGuest($guest, $subject, $body, $type, array_merge($metadata, [
                'member_id' => $member->id,
                'is_member' => true,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to send message to member', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Send a message to a visitor
     *
     * @param  \Prasso\Church\Models\Visitor  $visitor
     * @param  string  $subject
     * @param  string  $body
     * @param  string  $type  Message type (e.g., 'email', 'sms')
     * @param  array  $metadata  Additional metadata for the message
     * @return \Prasso\Messaging\Models\MsgMessage|null
     */
    public function sendToVisitor(Visitor $visitor, string $subject, string $body, string $type = 'email', array $metadata = [])
    {
        try {
            $guest = MsgGuest::firstOrCreate(
                ['email' => $visitor->email],
                [
                    'name' => $visitor->full_name,
                    'phone' => $visitor->phone,
                    'is_subscribed' => true,
                ]
            );

            return $this->sendToGuest($guest, $subject, $body, $type, array_merge($metadata, [
                'visitor_id' => $visitor->id,
                'is_visitor' => true,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to send message to visitor', [
                'visitor_id' => $visitor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Send a message to a guest
     *
     * @param  \Prasso\Messaging\Models\MsgGuest  $guest
     * @param  string  $subject
     * @param  string  $body
     * @param  string  $type
     * @param  array  $metadata
     * @return \Prasso\Messaging\Models\MsgMessage
     */
    protected function sendToGuest(MsgGuest $guest, string $subject, string $body, string $type, array $metadata = [])
    {
        // Create the message
        $message = MsgMessage::create([
            'subject' => $subject,
            'body' => $body,
            'type' => $type,
            'metadata' => $metadata,
        ]);

        // Attach the guest to the message
        $guest->messages()->attach($message->id);

        // Update the last message timestamp on the guest
        $guest->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * Send a welcome message to a new member
     *
     * @param  \Prasso\Church\Models\Member  $member
     * @return \Prasso\Messaging\Models\MsgMessage|null
     */
    public function sendWelcomeMessage(Member $member)
    {
        $subject = 'Welcome to ' . config('app.name');
        $body = view('church::emails.welcome', [
            'member' => $member,
            'welcomeMessage' => 'Thank you for joining our church community!',
        ])->render();

        return $this->sendToMember(
            $member,
            $subject,
            $body,
            'email',
            ['template' => 'welcome', 'category' => 'onboarding']
        );
    }
}
