<?php

namespace Prasso\Church\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Prasso\Church\Models\Report;
use Prasso\Church\Models\ReportRun;
use Prasso\Church\Models\ReportSchedule;
use Prasso\Church\Services\ReportService;
use Prasso\Church\Http\Controllers\Controller;
use Prasso\Church\Http\Resources\ReportResource;
use Prasso\Church\Http\Resources\ReportRunResource;
use Prasso\Church\Http\Resources\ReportScheduleResource;

class ReportController extends Controller
{
    /**
     * The report service instance.
     *
     * @var \Prasso\Church\Services\ReportService
     */
    protected $reportService;

    /**
     * Create a new controller instance.
     *
     * @param  \Prasso\Church\Services\ReportService  $reportService
     * @return void
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth:api');
    }

    /**
     * Get dashboard statistics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request)
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        $data = [
            'attendance' => $this->reportService->getAttendanceStats($startDate, $endDate),
            'member_growth' => $this->reportService->getMemberGrowth($startDate->copy()->subYear(), $endDate),
            'engagement' => $this->reportService->getMemberEngagement($startDate, $endDate),
            'recent_events' => $this->reportService->getEventAttendance($startDate, $endDate, null, 5)
        ];

        return response()->json($data);
    }

    /**
     * Get all reports
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $reports = Report::forUser(auth()->id())
            ->with(['creator', 'updater', 'lastRun'])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return ReportResource::collection($reports);
    }

    /**
     * Store a newly created report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'report_type' => 'required|string|in:attendance,membership,giving,other',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'settings' => 'nullable|array',
            'is_public' => 'sometimes|boolean'
        ]);

        $report = Report::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'report_type' => $validated['report_type'],
            'filters' => $validated['filters'] ?? [],
            'columns' => $validated['columns'] ?? [],
            'settings' => $validated['settings'] ?? [],
            'is_public' => $validated['is_public'] ?? false,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return new ReportResource($report->load(['creator', 'updater']));
    }

    /**
     * Get a specific report
     *
     * @param  \Prasso\Church\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Report $report)
    {
        $this->authorize('view', $report);
        
        return new ReportResource($report->load([
            'creator', 
            'updater', 
            'schedules', 
            'runs' => function($query) {
                $query->latest()->limit(5);
            }
        ]));
    }

    /**
     * Update the specified report
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'filters' => 'sometimes|array',
            'columns' => 'sometimes|array',
            'settings' => 'sometimes|array',
            'is_public' => 'sometimes|boolean'
        ]);

        $report->update([
            'name' => $validated['name'] ?? $report->name,
            'description' => $validated['description'] ?? $report->description,
            'filters' => $validated['filters'] ?? $report->filters,
            'columns' => $validated['columns'] ?? $report->columns,
            'settings' => $validated['settings'] ?? $report->settings,
            'is_public' => $validated['is_public'] ?? $report->is_public,
            'updated_by' => auth()->id(),
        ]);

        return new ReportResource($report->load(['creator', 'updater']));
    }

    /**
     * Remove the specified report
     *
     * @param  \Prasso\Church\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Report $report)
    {
        $this->authorize('delete', $report);
        
        $report->delete();
        
        return response()->json(['message' => 'Report deleted successfully']);
    }

    /**
     * Generate a custom report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateCustomReport(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|string|in:attendance_trends,member_engagement,event_attendance',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'sometimes|array',
            'group_by' => 'sometimes|string|in:day,week,month,year',
            'event_id' => 'required_if:report_type,event_attendance|integer|exists:aph_attendance_events,id'
        ]);

        try {
            $report = $this->reportService->generateCustomReport($validated);
            return response()->json($report);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get available report types
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReportTypes()
    {
        return response()->json([
            'types' => [
                [
                    'id' => 'attendance_trends',
                    'name' => 'Attendance Trends',
                    'description' => 'Track attendance patterns over time',
                    'filters' => [
                        'group_by' => ['day', 'week', 'month', 'year'],
                        'ministry_id' => 'number',
                        'group_id' => 'number'
                    ]
                ],
                [
                    'id' => 'member_engagement',
                    'name' => 'Member Engagement',
                    'description' => 'Analyze member participation and engagement',
                    'filters' => [
                        'ministry_id' => 'number',
                        'group_id' => 'number'
                    ]
                ],
                [
                    'id' => 'event_attendance',
                    'name' => 'Event Attendance',
                    'description' => 'Detailed attendance for specific events',
                    'filters' => [
                        'event_id' => 'number|required'
                    ]
                ]
            ]
        ]);
    }

    /**
     * Run a report
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse
     */
    public function runReport(Request $request, Report $report)
    {
        $this->authorize('run', $report);

        $validated = $request->validate([
            'format' => 'required|string|in:csv,xlsx,pdf',
            'parameters' => 'sometimes|array'
        ]);

        $run = $report->runs()->create([
            'status' => ReportRun::STATUS_PENDING,
            'parameters' => $validated['parameters'] ?? [],
            'started_at' => now(),
        ]);

        // Dispatch job to process the report
        ProcessReportJob::dispatch($run, $validated['format']);

        return new ReportRunResource($run);
    }

    /**
     * Get report run status
     *
     * @param  \Prasso\Church\Models\ReportRun  $run
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRunStatus(ReportRun $run)
    {
        $this->authorize('view', $run->report);
        
        return new ReportRunResource($run);
    }

    /**
     * Download a generated report
     *
     * @param  \Prasso\Church\Models\ReportRun  $run
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadReport(ReportRun $run)
    {
        $this->authorize('download', $run);

        if (!$run->isCompleted() || !$run->file_path) {
            abort(404, 'Report not found or not ready for download');
        }

        if (!Storage::exists($run->file_path)) {
            abort(404, 'Report file not found');
        }

        return Storage::download($run->file_path, $this->generateFilename($run));
    }

    /**
     * Create or update a report schedule
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\Report  $report
     * @return \Illuminate\Http\JsonResponse
     */
    public function scheduleReport(Request $request, Report $report)
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'frequency' => 'required|string|in:daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'day_of_week' => 'required_if:frequency,weekly|string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'day_of_month' => 'required_if:frequency,monthly|integer|min:1|max:31',
            'recipients' => 'required|array',
            'recipients.*' => 'required|email',
            'format' => 'required|string|in:csv,xlsx,pdf',
            'is_active' => 'sometimes|boolean'
        ]);

        $schedule = $report->schedules()->updateOrCreate(
            ['id' => $request->input('schedule_id')],
            array_merge($validated, ['is_active' => $validated['is_active'] ?? true])
        );

        return new ReportScheduleResource($schedule);
    }

    /**
     * Delete a report schedule
     *
     * @param  \Prasso\Church\Models\ReportSchedule  $schedule
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSchedule(ReportSchedule $schedule)
    {
        $this->authorize('delete', $schedule);
        
        $schedule->delete();
        
        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    /**
     * Generate a filename for the report
     *
     * @param  \Prasso\Church\Models\ReportRun  $run
     * @return string
     */
    protected function generateFilename(ReportRun $run): string
    {
        $extension = pathinfo($run->file_path, PATHINFO_EXTENSION);
        $reportName = Str::slug($run->report->name);
        $timestamp = $run->completed_at->format('Y-m-d_His');
        
        return "report_{$reportName}_{$timestamp}.{$extension}";
    }
}
