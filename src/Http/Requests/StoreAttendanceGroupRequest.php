<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', \Prasso\Church\Models\AttendanceGroup::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:chm_attendance_groups,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'members' => 'nullable|array',
            'members.*.id' => 'required_with:members|exists:chm_members,id',
            'members.*.start_date' => 'nullable|date',
            'members.*.end_date' => 'nullable|date|after:members.*.start_date',
            'members.*.notes' => 'nullable|string|max:500',
            'families' => 'nullable|array',
            'families.*.id' => 'required_with:families|exists:chm_families,id',
            'families.*.start_date' => 'nullable|date',
            'families.*.end_date' => 'nullable|date|after:families.*.start_date',
            'families.*.notes' => 'nullable|string|max:500',
            'groups' => 'nullable|array',
            'groups.*.id' => 'required_with:groups|exists:chm_groups,id',
            'groups.*.start_date' => 'nullable|date',
            'groups.*.end_date' => 'nullable|date|after:groups.*.start_date',
            'groups.*.notes' => 'nullable|string|max:500',
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
            'is_active' => $this->boolean('is_active', true),
            'members' => $this->members ?? [],
            'families' => $this->families ?? [],
            'groups' => $this->groups ?? [],
        ]);
    }
    
    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.unique' => 'An attendance group with this name already exists.',
            'members.*.id.required_with' => 'Member ID is required.',
            'families.*.id.required_with' => 'Family ID is required.',
            'groups.*.id.required_with' => 'Group ID is required.',
        ];
    }
}
