<?php

namespace Prasso\Church\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Prasso\Church\Models\ReportRun;

class ReportFailed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The report run instance.
     *
     * @var \Prasso\Church\Models\ReportRun
     */
    public $reportRun;

    /**
     * The exception that caused the failure.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new notification instance.
     *
     * @param  \Prasso\Church\Models\ReportRun  $reportRun
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct(ReportRun $reportRun, \Exception $exception)
    {
        $this->reportRun = $reportRun;
        $this->exception = $exception;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $report = $this->reportRun->report;
        
        return (new MailMessage)
            ->error()
            ->subject("Report Generation Failed: {$report->name}")
            ->line("The report '{$report->name}' failed to generate.")
            ->line("Error: {$this->exception->getMessage()}")
            ->action('View Report', route('reports.show', $report->id))
            ->line('Please check the logs for more details.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'report_id' => $this->reportRun->report_id,
            'run_id' => $this->reportRun->id,
            'error' => $this->exception->getMessage(),
            'time' => now(),
        ];
    }
}
