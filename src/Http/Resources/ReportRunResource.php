<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportRunResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'report_id' => $this->report_id,
            'schedule_id' => $this->schedule_id,
            'status' => $this->status,
            'error_message' => $this->when($this->status === 'failed', $this->error_message),
            'file_path' => $this->when($this->status === 'completed', $this->file_path),
            'parameters' => $this->parameters,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'duration' => $this->when($this->started_at && $this->completed_at, 
                $this->started_at->diffInSeconds($this->completed_at)
            ),
            'download_url' => $this->when($this->status === 'completed' && $this->file_path, 
                route('reports.download', $this->id)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
