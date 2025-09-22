<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', \Prasso\Church\Models\AttendanceEvent::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location_id' => 'nullable|exists:chm_locations,id',
            'event_type_id' => 'required|exists:chm_event_types,id',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_attendance_groups,id',
            'expected_attendance' => 'nullable|integer|min:0',
            'requires_check_in' => 'boolean',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => [
                'required_if:is_recurring,true', 
                Rule::in(array_keys(config('attendance.recurring.patterns')))
            ],
            'recurrence_details' => 'nullable|array',
            'recurrence_end_date' => 'nullable|date|after:start_time',
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
            'recurrence_pattern.required_if' => 'The recurrence pattern is required when creating a recurring event.',
            'recurrence_pattern.in' => 'The selected recurrence pattern is invalid.',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_recurring' => $this->boolean('is_recurring'),
            'requires_check_in' => $this->boolean('requires_check_in'),
        ]);
    }
}
