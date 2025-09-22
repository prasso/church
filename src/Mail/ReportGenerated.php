<?php

namespace Prasso\Church\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ReportGenerated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The report data.
     *
     * @var array
     */
    public $data;

    /**
     * The file path of the generated report.
     *
     * @var string
     */
    public $filePath;

    /**
     * Create a new message instance.
     *
     * @param  array  $data
     * @param  string  $filePath
     * @return void
     */
    public function __construct(array $data, string $filePath)
    {
        $this->data = $data;
        $this->filePath = $filePath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $filename = basename($this->filePath);
        $report = $this->data['report'];
        
        $email = $this->subject("Report Generated: {$report->name}")
            ->markdown('church::emails.reports.generated')
            ->with($this->data);
            
        // Attach the report file
        if (Storage::exists($this->filePath)) {
            $email->attachFromStorage($this->filePath, $filename, [
                'mime' => $this->getMimeType($filename)
            ]);
        }
        
        return $email;
    }
    
    /**
     * Get the MIME type for the file.
     *
     * @param  string  $filename
     * @return string
     */
    protected function getMimeType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
