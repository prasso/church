<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
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
            'member_id' => [
                'required_without:family_id',
                'exists:chm_members,id',
                Rule::unique('chm_attendance_records')
                    ->where('event_id', $this->route('eventId'))
                    ->whereNull('deleted_at')
            ],
            'family_id' => [
                'required_without:member_id',
                'exists:chm_families,id',
                Rule::unique('chm_attendance_records')
                    ->where('event_id', $this->route('eventId'))
                    ->whereNull('deleted_at')
            ],
            'status' => [
                'required',
                Rule::in(array_keys(config('attendance.statuses')))
            ],
            'guest_count' => 'nullable|integer|min:0|max:20',
            'notes' => 'nullable|string|max:1000',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date|after:check_in_time',
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
            'member_id.required_without' => 'Either member_id or family_id is required.',
            'family_id.required_without' => 'Either member_id or family_id is required.',
            'member_id.unique' => 'This member is already checked in to this event.',
            'family_id.unique' => 'This family is already checked in to this event.',
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
        $this->merge([
            'guest_count' => $this->guest_count ?? 0,
            'check_in_time' => $this->check_in_time ?? now(),
            'source' => $this->source ?? 'manual',
        ]);
    }
}
