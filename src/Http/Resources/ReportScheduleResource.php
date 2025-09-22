<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportScheduleResource extends JsonResource
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
            'frequency' => $this->frequency,
            'time' => $this->time,
            'day_of_week' => $this->when($this->frequency === 'weekly', $this->day_of_week),
            'day_of_month' => $this->when($this->frequency === 'monthly', $this->day_of_month),
            'recipients' => $this->recipients,
            'format' => $this->format,
            'is_active' => $this->is_active,
            'last_run_at' => $this->last_run_at,
            'next_run_at' => $this->getNextRunDate(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
