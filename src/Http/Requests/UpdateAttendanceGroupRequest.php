<?php

namespace Prasso\Church\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceGroupRequest extends StoreAttendanceGroupRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $group = $this->route('group');
        return $this->user()->can('update', $group);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $groupId = $this->route('group');
        
        return array_merge(parent::rules(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('chm_attendance_groups', 'name')->ignore($groupId)
            ],
            'update_members' => 'sometimes|boolean',
            'update_families' => 'sometimes|boolean',
            'update_groups' => 'sometimes|boolean',
        ]);
    }
    
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        parent::prepareForValidation();
        
        $this->merge([
            'update_members' => $this->boolean('update_members', false),
            'update_families' => $this->boolean('update_families', false),
            'update_groups' => $this->boolean('update_groups', false),
        ]);
        
        // If specific update flags aren't set, clear the corresponding arrays
        if (!$this->update_members) {
            $this->merge(['members' => []]);
        }
        
        if (!$this->update_families) {
            $this->merge(['families' => []]);
        }
        
        if (!$this->update_groups) {
            $this->merge(['groups' => []]);
        }
    }
}
