<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\Skill;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\VolunteerPosition;
use Illuminate\Support\Facades\DB;

class VolunteerSkillController extends Controller
{
    /**
     * Get all available skills.
     */
    public function index(Request $request)
    {
        $query = Skill::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Get categories if requested
        if ($request->boolean('with_categories')) {
            $categories = Skill::select('category')
                ->distinct()
                ->whereNotNull('category')
                ->orderBy('category')
                ->pluck('category');

            return response()->json([
                'skills' => $query->orderBy('name')->get(),
                'categories' => $categories,
            ]);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Store a newly created skill in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $skill = Skill::create($validated);
        return response()->json($skill, 201);
    }

    /**
     * Update the specified skill in storage.
     */
    public function update(Request $request, Skill $skill)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $skill->update($validated);
        return $skill;
    }

    /**
     * Remove the specified skill from storage.
     */
    public function destroy(Skill $skill)
    {
        $skill->delete();
        return response()->json(null, 204);
    }

    /**
     * Add a skill to a member.
     */
    public function addMemberSkill(Request $request, Member $member)
    {
        $validated = $request->validate([
            'skill_id' => 'required|exists:chm_skills,id',
            'proficiency_level' => 'nullable|string|max:50',
            'years_experience' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        // Check if member already has this skill
        if ($member->skills()->where('skill_id', $validated['skill_id'])->exists()) {
            return response()->json(['message' => 'Member already has this skill'], 422);
        }

        $member->skills()->attach($validated['skill_id'], [
            'proficiency_level' => $validated['proficiency_level'] ?? null,
            'years_experience' => $validated['years_experience'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['message' => 'Skill added to member'], 201);
    }

    /**
     * Update a member's skill.
     */
    public function updateMemberSkill(Request $request, Member $member, Skill $skill)
    {
        $validated = $request->validate([
            'proficiency_level' => 'nullable|string|max:50',
            'years_experience' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $member->skills()->updateExistingPivot($skill->id, $validated);
        
        return response()->json(['message' => 'Member skill updated']);
    }

    /**
     * Remove a skill from a member.
     */
    public function removeMemberSkill(Member $member, Skill $skill)
    {
        $member->skills()->detach($skill->id);
        return response()->json(['message' => 'Skill removed from member']);
    }

    /**
     * Add a required skill to a volunteer position.
     */
    public function addPositionSkill(Request $request, VolunteerPosition $position)
    {
        $validated = $request->validate([
            'skill_id' => 'required|exists:chm_skills,id',
            'is_required' => 'boolean',
            'proficiency_required' => 'nullable|string|max:50',
        ]);

        // Check if position already requires this skill
        if ($position->skills()->where('skill_id', $validated['skill_id'])->exists()) {
            return response()->json(['message' => 'Position already requires this skill'], 422);
        }

        $position->skills()->attach($validated['skill_id'], [
            'is_required' => $validated['is_required'] ?? false,
            'proficiency_required' => $validated['proficiency_required'] ?? null,
        ]);

        return response()->json(['message' => 'Skill added to position'], 201);
    }

    /**
     * Update a position's required skill.
     */
    public function updatePositionSkill(Request $request, VolunteerPosition $position, Skill $skill)
    {
        $validated = $request->validate([
            'is_required' => 'boolean',
            'proficiency_required' => 'nullable|string|max:50',
        ]);

        $position->skills()->updateExistingPivot($skill->id, $validated);
        
        return response()->json(['message' => 'Position skill requirement updated']);
    }

    /**
     * Remove a required skill from a position.
     */
    public function removePositionSkill(VolunteerPosition $position, Skill $skill)
    {
        $position->skills()->detach($skill->id);
        return response()->json(['message' => 'Skill requirement removed from position']);
    }

    /**
     * Get members with specific skills.
     */
    public function findMembersWithSkills(Request $request)
    {
        $validated = $request->validate([
            'skill_ids' => 'required|array',
            'skill_ids.*' => 'exists:chm_skills,id',
            'proficiency_min' => 'nullable|string',
        ]);

        $query = Member::whereHas('skills', function($q) use ($validated) {
            $q->whereIn('skill_id', $validated['skill_ids']);
            
            if (isset($validated['proficiency_min'])) {
                $q->where('proficiency_level', '>=', $validated['proficiency_min']);
            }
        })->with(['skills' => function($q) use ($validated) {
            $q->whereIn('skill_id', $validated['skill_ids']);
        }]);

        return $query->get();
    }
}
