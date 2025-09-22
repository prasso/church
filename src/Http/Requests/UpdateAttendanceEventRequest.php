<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $event = $this->route('event');
        return $this->user()->can('update', $event);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'nullable|date|after:start_time',
            'location_id' => 'nullable|exists:chm_locations,id',
            'event_type_id' => 'sometimes|required|exists:chm_event_types,id',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_attendance_groups,id',
            'expected_attendance' => 'nullable|integer|min:0',
            'requires_check_in' => 'sometimes|boolean',
            'is_recurring' => 'sometimes|boolean',
            'recurrence_pattern' => [
                'required_if:is_recurring,true', 
                Rule::in(array_keys(config('attendance.recurring.patterns')))
            ],
            'recurrence_details' => 'nullable|array',
            'status' => [
                'sometimes',
                'required',
                Rule::in(array_keys(config('attendance.statuses')))
            ],
            'update_all_occurrences' => 'sometimes|boolean',
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
            'status.in' => 'The selected status is invalid.',
        ];
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if ($this->has('is_recurring')) {
            $this->merge([
                'is_recurring' => $this->boolean('is_recurring'),
            ]);
        }
        
        if ($this->has('requires_check_in')) {
            $this->merge([
                'requires_check_in' => $this->boolean('requires_check_in'),
            ]);
        }
        
        if ($this->has('update_all_occurrences')) {
            $this->merge([
                'update_all_occurrences' => $this->boolean('update_all_occurrences'),
            ]);
        }
    }
}
