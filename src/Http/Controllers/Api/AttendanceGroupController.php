<?php

namespace Prasso\Church\Http\Controllers\Api;

use Prasso\Church\Http\Controllers\Controller;
use Prasso\Church\Models\AttendanceGroup;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Family;
use Prasso\Church\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AttendanceGroupController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        
        $this->middleware('can:view_attendance_groups')->only(['index', 'show']);
        $this->middleware('can:create_attendance_groups')->only(['store']);
        $this->middleware('can:update_attendance_groups')->only(['update']);
        $this->middleware('can:delete_attendance_groups')->only(['destroy']);
    }

    /**
     * Display a listing of attendance groups.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = AttendanceGroup::with(['ministry']);
        
        // Filter by ministry
        if ($request->has('ministry_id')) {
            $query->where('ministry_id', $request->input('ministry_id'));
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        // Search by name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $groups = $query->orderBy('name')->paginate($perPage);
        
        return response()->json($groups);
    }

    /**
     * Store a newly created attendance group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'members' => 'nullable|array',
            'members.*.id' => 'required_with:members|exists:chm_members,id',
            'members.*.start_date' => 'nullable|date',
            'members.*.end_date' => 'nullable|date|after:members.*.start_date',
            'members.*.notes' => 'nullable|string',
            'families' => 'nullable|array',
            'families.*.id' => 'required_with:families|exists:chm_families,id',
            'families.*.start_date' => 'nullable|date',
            'families.*.end_date' => 'nullable|date|after:families.*.start_date',
            'families.*.notes' => 'nullable|string',
            'groups' => 'nullable|array',
            'groups.*.id' => 'required_with:groups|exists:chm_groups,id',
            'groups.*.start_date' => 'nullable|date',
            'groups.*.end_date' => 'nullable|date|after:groups.*.start_date',
            'groups.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Create the group
            $group = new AttendanceGroup([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true),
                'ministry_id' => $request->input('ministry_id'),
                'created_by' => $request->user()->id,
            ]);
            
            $group->save();
            
            // Attach members
            if ($request->has('members')) {
                $members = [];
                foreach ($request->input('members') as $member) {
                    $members[$member['id']] = [
                        'start_date' => $member['start_date'] ?? null,
                        'end_date' => $member['end_date'] ?? null,
                        'notes' => $member['notes'] ?? null,
                    ];
                }
                $group->members()->attach($members);
            }
            
            // Attach families
            if ($request->has('families')) {
                $families = [];
                foreach ($request->input('families') as $family) {
                    $families[$family['id']] = [
                        'start_date' => $family['start_date'] ?? null,
                        'end_date' => $family['end_date'] ?? null,
                        'notes' => $family['notes'] ?? null,
                    ];
                }
                $group->families()->attach($families);
            }
            
            // Attach groups
            if ($request->has('groups')) {
                $subgroups = [];
                foreach ($request->input('groups') as $subgroup) {
                    $subgroups[$subgroup['id']] = [
                        'start_date' => $subgroup['start_date'] ?? null,
                        'end_date' => $subgroup['end_date'] ?? null,
                        'notes' => $subgroup['notes'] ?? null,
                    ];
                }
                $group->groups()->attach($subgroups);
            }
            
            DB::commit();
            
            return response()->json($group->load(['members', 'families', 'groups']), 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create attendance group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified attendance group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $group = AttendanceGroup::with([
            'ministry', 
            'members', 
            'families', 
            'groups',
            'activeMembers',
            'activeFamilies',
            'activeGroups'
        ])->findOrFail($id);
        
        // Calculate total members including those in subgroups
        $allMembers = $group->getAllMembers();
        $group->total_members = $allMembers->count();
        
        // Get recent attendance stats
        $recentEvents = $group->events()
            ->where('start_time', '>=', now()->subDays(30))
            ->withCount('attendanceRecords')
            ->orderBy('start_time', 'desc')
            ->limit(5)
            ->get();
        
        $group->recent_events = $recentEvents;
        
        return response()->json($group);
    }

    /**
     * Update the specified attendance group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $group = AttendanceGroup::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ministry_id' => 'nullable|exists:chm_ministries,id',
            'members' => 'nullable|array',
            'members.*.id' => 'required_with:members|exists:chm_members,id',
            'members.*.start_date' => 'nullable|date',
            'members.*.end_date' => 'nullable|date|after:members.*.start_date',
            'members.*.notes' => 'nullable|string',
            'families' => 'nullable|array',
            'families.*.id' => 'required_with:families|exists:chm_families,id',
            'families.*.start_date' => 'nullable|date',
            'families.*.end_date' => 'nullable|date|after:families.*.start_date',
            'families.*.notes' => 'nullable|string',
            'groups' => 'nullable|array',
            'groups.*.id' => 'required_with:groups|exists:chm_groups,id',
            'groups.*.start_date' => 'nullable|date',
            'groups.*.end_date' => 'nullable|date|after:groups.*.start_date',
            'groups.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Update the group
            $group->fill($request->only(['name', 'description', 'is_active', 'ministry_id']));
            $group->save();
            
            // Sync members
            if ($request->has('members')) {
                $members = [];
                foreach ($request->input('members') as $member) {
                    $members[$member['id']] = [
                        'start_date' => $member['start_date'] ?? null,
                        'end_date' => $member['end_date'] ?? null,
                        'notes' => $member['notes'] ?? null,
                    ];
                }
                $group->members()->sync($members);
            }
            
            // Sync families
            if ($request->has('families')) {
                $families = [];
                foreach ($request->input('families') as $family) {
                    $families[$family['id']] = [
                        'start_date' => $family['start_date'] ?? null,
                        'end_date' => $family['end_date'] ?? null,
                        'notes' => $family['notes'] ?? null,
                    ];
                }
                $group->families()->sync($families);
            }
            
            // Sync groups
            if ($request->has('groups')) {
                $subgroups = [];
                foreach ($request->input('groups') as $subgroup) {
                    $subgroups[$subgroup['id']] = [
                        'start_date' => $subgroup['start_date'] ?? null,
                        'end_date' => $subgroup['end_date'] ?? null,
                        'notes' => $subgroup['notes'] ?? null,
                    ];
                }
                $group->groups()->sync($subgroups);
            }
            
            DB::commit();
            
            return response()->json($group->load(['members', 'families', 'groups']));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to update attendance group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified attendance group.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $group = AttendanceGroup::findOrFail($id);
        
        // Check if the group is being used by any events
        $eventCount = $group->events()->count();
        
        if ($eventCount > 0) {
            return response()->json([
                'message' => 'Cannot delete group because it is associated with ' . $eventCount . ' events',
            ], 422);
        }
        
        // Detach all relationships
        $group->members()->detach();
        $group->families()->detach();
        $group->groups()->detach();
        
        // Delete the group
        $group->delete();
        
        return response()->json([
            'message' => 'Attendance group deleted successfully',
        ]);
    }
    
    /**
     * Get attendance statistics for the group.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getStatistics($id, Request $request)
    {
        $group = AttendanceGroup::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|in:day,week,month,quarter,year',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $groupBy = $request->input('group_by', 'month');
        
        // Get all member IDs in this group (including subgroups)
        $memberIds = $group->getAllMembers()->pluck('id');
        
        if ($memberIds->isEmpty()) {
            return response()->json([
                'message' => 'No members found in this group',
                'data' => [
                    'total_events' => 0,
                    'total_attendance' => 0,
                    'average_attendance' => 0,
                    'attendance_trend' => [],
                    'member_attendance' => [],
                    'event_types' => [],
                ]
            ]);
        }
        
        // Get total events in date range
        $totalEvents = $group->events()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->count();
        
        // Get total attendance
        $totalAttendance = AttendanceRecord::whereIn('member_id', $memberIds)
            ->whereHas('event', function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_time', [$startDate, $endDate]);
            })
            ->count();
        
        // Get attendance trend
        $attendanceTrend = AttendanceRecord::select(
                DB::raw('DATE(start_time) as date'),
                DB::raw('COUNT(DISTINCT member_id) as count')
            )
            ->whereIn('member_id', $memberIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Get member attendance stats
        $memberAttendance = AttendanceRecord::select(
                'chm_members.id',
                'chm_members.first_name',
                'chm_members.last_name',
                DB::raw('COUNT(*) as attendance_count'),
                DB::raw('ROUND(COUNT(*) * 100.0 / ' . max(1, $totalEvents) . ', 2) as attendance_rate')
            )
            ->join('chm_members', 'chm_attendance_records.member_id', '=', 'chm_members.id')
            ->whereIn('chm_attendance_records.member_id', $memberIds)
            ->whereBetween('chm_attendance_records.start_time', [$startDate, $endDate])
            ->groupBy('chm_members.id', 'chm_members.first_name', 'chm_members.last_name')
            ->orderBy('attendance_count', 'desc')
            ->get();
        
        // Get attendance by event type
        $eventTypes = AttendanceRecord::select(
                'chm_event_types.id',
                'chm_event_types.name',
                DB::raw('COUNT(DISTINCT chm_attendance_records.id) as attendance_count')
            )
            ->join('chm_attendance_events', 'chm_attendance_records.event_id', '=', 'chm_attendance_events.id')
            ->join('chm_event_types', 'chm_attendance_events.event_type_id', '=', 'chm_event_types.id')
            ->whereIn('chm_attendance_records.member_id', $memberIds)
            ->whereBetween('chm_attendance_records.start_time', [$startDate, $endDate])
            ->groupBy('chm_event_types.id', 'chm_event_types.name')
            ->orderBy('attendance_count', 'desc')
            ->get();
        
        return response()->json([
            'total_events' => $totalEvents,
            'total_attendance' => $totalAttendance,
            'average_attendance' => $totalEvents > 0 ? round($totalAttendance / $totalEvents, 2) : 0,
            'attendance_trend' => $attendanceTrend,
            'member_attendance' => $memberAttendance,
            'event_types' => $eventTypes,
        ]);
    }
}
