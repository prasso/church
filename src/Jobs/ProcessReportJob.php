<?php

namespace Prasso\Church\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Prasso\Church\Models\ReportRun;
use Prasso\Church\Exports\ReportExport;
use PDF;

class ProcessReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The report run instance.
     *
     * @var \Prasso\Church\Models\ReportRun
     */
    protected $reportRun;

    /**
     * The export format.
     *
     * @var string
     */
    protected $format;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @param  \Prasso\Church\Models\ReportRun  $reportRun
     * @param  string  $format
     * @return void
     */
    public function __construct(ReportRun $reportRun, string $format = 'csv')
    {
        $this->reportRun = $reportRun->withoutRelations();
        $this->format = strtolower($format);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Reload the report run to ensure we have the latest data
        $reportRun = ReportRun::findOrFail($this->reportRun->id);
        $report = $reportRun->report;

        try {
            // Mark the run as processing
            $reportRun->markAsStarted();

            // Generate the report data
            $data = $this->generateReportData($report);

            // Generate the file based on the requested format
            $filePath = $this->generateFile($report, $data);

            // Update the run with the file path and mark as completed
            $reportRun->markAsCompleted($filePath);

            // If this was triggered by a schedule, update the last run time
            if ($reportRun->schedule) {
                $reportRun->schedule->markAsRun();
                
                // Send email to recipients if configured
                $this->sendEmailNotifications($reportRun, $filePath);
            }

        } catch (\Exception $e) {
            // Mark the run as failed
            $reportRun->markAsFailed($e->getMessage());
            
            // Re-throw the exception to trigger job retries if needed
            throw $e;
        }
    }

    /**
     * Generate the report data.
     *
     * @param  \Prasso\Church\Models\Report  $report
     * @return array
     */
    protected function generateReportData($report)
    {
        // Get the report service
        $reportService = app(\Prasso\Church\Services\ReportService::class);
        
        // Get the report parameters
        $parameters = $this->reportRun->parameters ?? [];
        $startDate = $parameters['start_date'] ?? now()->subMonth()->format('Y-m-d');
        $endDate = $parameters['end_date'] ?? now()->format('Y-m-d');
        $filters = $parameters['filters'] ?? [];

        // Generate the report based on the report type
        switch ($report->report_type) {
            case 'attendance':
                return $reportService->getAttendanceStats(
                    Carbon::parse($startDate),
                    Carbon::parse($endDate),
                    $filters
                );

            case 'membership':
                return $reportService->getMemberGrowth(
                    Carbon::parse($startDate),
                    Carbon::parse($endDate),
                    $filters
                );

            case 'giving':
                return $reportService->getGivingReport(
                    Carbon::parse($startDate),
                    Carbon::parse($endDate),
                    $filters
                );

            default:
                throw new \InvalidArgumentException("Unsupported report type: {$report->report_type}");
        }
    }

    /**
     * Generate the report file.
     *
     * @param  \Prasso\Church\Models\Report  $report
     * @param  array  $data
     * @return string
     */
    protected function generateFile($report, $data)
    {
        $filename = 'reports/' . uniqid("report_{$report->id}_") . '.' . $this->format;
        
        switch ($this->format) {
            case 'csv':
            case 'xlsx':
                $export = new ReportExport($data, $report->columns ?? []);
                Excel::store($export, $filename, 'local');
                break;
                
            case 'pdf':
                $pdf = PDF::loadView('church::reports.pdf', [
                    'report' => $report,
                    'data' => $data,
                    'generated_at' => now(),
                ]);
                
                Storage::put($filename, $pdf->output());
                break;
                
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$this->format}");
        }
        
        return $filename;
    }

    /**
     * Send email notifications to recipients.
     *
     * @param  \Prasso\Church\Models\ReportRun  $reportRun
     * @param  string  $filePath
     * @return void
     */
    protected function sendEmailNotifications($reportRun, $filePath)
    {
        $report = $reportRun->report;
        $schedule = $reportRun->schedule;
        
        if (empty($schedule->recipients)) {
            return;
        }
        
        $data = [
            'report' => $report,
            'schedule' => $schedule,
            'run' => $reportRun,
            'downloadUrl' => route('reports.download', $reportRun->id),
        ];
        
        foreach ($schedule->recipients as $recipient) {
            \Illuminate\Support\Facades\Mail::to($recipient)
                ->send(new \Prasso\Church\Mail\ReportGenerated($data, $filePath));
        }
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // Notify the admin about the failed job
        if ($this->reportRun->schedule) {
            $this->reportRun->schedule->notify(
                new \Prasso\Church\Notifications\ReportFailed($this->reportRun, $exception)
            );
        }
    }
}
