<?php

namespace Prasso\Church\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'report_type' => $this->report_type,
            'filters' => $this->filters,
            'columns' => $this->columns,
            'settings' => $this->settings,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ] : null;
            }),
            'updated_by' => $this->whenLoaded('updater', function () {
                return $this->updater ? [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                    'email' => $this->updater->email,
                ] : null;
            }),
            'last_run' => $this->whenLoaded('lastRun', function () {
                return $this->lastRun ? [
                    'id' => $this->lastRun->id,
                    'status' => $this->lastRun->status,
                    'started_at' => $this->lastRun->started_at,
                    'completed_at' => $this->lastRun->completed_at,
                ] : null;
            }),
            'schedules' => ReportScheduleResource::collection($this->whenLoaded('schedules')),
            'runs' => ReportRunResource::collection($this->whenLoaded('runs')),
        ];
    }
}
