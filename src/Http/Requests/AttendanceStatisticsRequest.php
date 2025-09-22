<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceStatisticsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('view_attendance_reports');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'event_id' => 'nullable|exists:chm_attendance_events,id',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'group_id' => 'nullable|exists:chm_attendance_groups,id',
            'member_id' => 'nullable|exists:chm_members,id',
            'family_id' => 'nullable|exists:chm_families,id',
            'start_date' => 'required_without:event_id|date',
            'end_date' => 'required_with:start_date|date|after_or_equal:start_date',
            'group_by' => [
                'nullable',
                Rule::in(array_keys(config('attendance.reporting.group_by')))
            ],
            'include_demographics' => 'boolean',
            'include_trends' => 'boolean',
            'include_top_attendees' => 'boolean',
            'include_event_types' => 'boolean',
            'limit' => 'nullable|integer|min:1|max:100',
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
            'include_demographics' => $this->boolean('include_demographics', true),
            'include_trends' => $this->boolean('include_trends', true),
            'include_top_attendees' => $this->boolean('include_top_attendees', true),
            'include_event_types' => $this->boolean('include_event_types', true),
            'limit' => $this->limit ?? 10,
            'group_by' => $this->group_by ?? 'month',
        ]);
        
        // If no date range is provided, default to the last 30 days
        if (!$this->has('start_date') && !$this->has('event_id')) {
            $this->merge([
                'start_date' => now()->subDays(30)->toDateString(),
                'end_date' => now()->toDateString(),
            ]);
        }
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'start_date.required_without' => 'A start date is required when not filtering by event.',
            'end_date.required_with' => 'An end date is required when specifying a start date.',
            'group_by.in' => 'The selected group by value is invalid.',
        ];
    }
}
