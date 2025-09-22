<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', \Prasso\Church\Models\AttendanceRecord::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'attendees' => 'required|array|min:1',
            'attendees.*.member_id' => [
                'required_without:attendees.*.family_id',
                'exists:chm_members,id',
            ],
            'attendees.*.family_id' => [
                'required_without:attendees.*.member_id',
                'exists:chm_families,id',
            ],
            'attendees.*.status' => [
                'required',
                Rule::in(array_keys(config('attendance.statuses')))
            ],
            'attendees.*.guest_count' => 'nullable|integer|min:0|max:20',
            'attendees.*.notes' => 'nullable|string|max:1000',
            'check_in_time' => 'nullable|date',
            'source' => 'nullable|string|max:50',
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'attendees.required' => 'At least one attendee is required.',
            'attendees.*.member_id.required_without' => 'Either member_id or family_id is required for each attendee.',
            'attendees.*.family_id.required_without' => 'Either member_id or family_id is required for each attendee.',
            'attendees.*.status.in' => 'The selected status is invalid for one or more attendees.',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Set default values for each attendee
        $attendees = collect($this->attendees)->map(function ($attendee) {
            return [
                'member_id' => $attendee['member_id'] ?? null,
                'family_id' => $attendee['family_id'] ?? null,
                'status' => $attendee['status'] ?? 'present',
                'guest_count' => $attendee['guest_count'] ?? 0,
                'notes' => $attendee['notes'] ?? null,
            ];
        })->toArray();
        
        $this->merge([
            'attendees' => $attendees,
            'check_in_time' => $this->check_in_time ?? now(),
            'source' => $this->source ?? 'bulk_import',
        ]);
    }
}
