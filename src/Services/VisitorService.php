<?php

namespace Prasso\Church\Services;

use Prasso\Church\Models\Visitor;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Family;
use Illuminate\Support\Facades\DB;

class VisitorService
{
    /**
     * Convert a visitor to a member.
     *
     * @param  \Prasso\Church\Models\Visitor  $visitor
     * @param  array  $memberData
     * @param  bool  $createFamily
     * @return \Prasso\Church\Models\Member
     */
    public function convertToMember(Visitor $visitor, array $memberData, bool $createFamily = true)
    {
        return DB::transaction(function () use ($visitor, $memberData, $createFamily) {
            // Create or find family
            $family = null;
            if ($createFamily) {
                $family = Family::create([
                    'name' => $visitor->last_name . ' Family',
                    'address' => $visitor->address,
                    'city' => $visitor->city,
                    'state' => $visitor->state,
                    'postal_code' => $visitor->postal_code,
                    'country' => $visitor->country,
                    'phone' => $visitor->phone,
                    'email' => $visitor->email,
                ]);
            }

            // Create member
            $member = new Member([
                'first_name' => $visitor->first_name,
                'last_name' => $visitor->last_name,
                'email' => $visitor->email,
                'phone' => $visitor->phone,
                'address' => $visitor->address,
                'city' => $visitor->city,
                'state' => $visitor->state,
                'postal_code' => $visitor->postal_code,
                'country' => $visitor->country,
                'membership_status' => 'visitor',
                'is_head_of_household' => true,
                'family_id' => $family ? $family->id : null,
            ]);

            // Merge in any additional member data
            $member->fill($memberData);
            $member->save();

            // Update visitor record
            $visitor->update([
                'converted_to_member' => true,
                'converted_to_member_id' => $member->id,
                'converted_at' => now(),
                'status' => 'converted',
            ]);

            return $member;
        });
    }

    /**
     * Schedule a follow-up for a visitor.
     *
     * @param  \Prasso\Church\Models\Visitor  $visitor
     * @param  \Carbon\Carbon|string  $followUpDate
     * @param  string|null  $notes
     * @param  int|null  $assignedTo
     * @return \Prasso\Church\Models\Visitor
     */
    public function scheduleFollowUp(Visitor $visitor, $followUpDate, ?string $notes = null, ?int $assignedTo = null)
    {
        $visitor->update([
            'follow_up_date' => $followUpDate,
            'follow_up_notes' => $notes,
            'assigned_to' => $assignedTo,
            'status' => 'needs_follow_up',
        ]);

        return $visitor->fresh();
    }
}
