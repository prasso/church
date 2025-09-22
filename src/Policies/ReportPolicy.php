<?php

namespace Prasso\Church\Policies;

use Prasso\Church\Models\Report;
use Prasso\Church\Models\ReportRun;
use Prasso\Church\Models\ReportSchedule;
use Prasso\Church\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view reports');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Report  $report
     * @return bool
     */
    public function view(User $user, Report $report): bool
    {
        if ($report->is_public) {
            return true;
        }

        return $user->can('view reports') && 
               ($report->created_by === $user->id || $user->can('view all reports'));
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->can('create reports');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Report  $report
     * @return bool
     */
    public function update(User $user, Report $report): bool
    {
        return $user->can('edit reports') && 
               ($report->created_by === $user->id || $user->can('edit all reports'));
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Report  $report
     * @return bool
     */
    public function delete(User $user, Report $report): bool
    {
        return $user->can('delete reports') && 
               ($report->created_by === $user->id || $user->can('delete all reports'));
    }

    /**
     * Determine whether the user can run the report.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\Report  $report
     * @return bool
     */
    public function run(User $user, Report $report): bool
    {
        return $this->view($user, $report) && $user->can('run reports');
    }

    /**
     * Determine whether the user can download the report.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\ReportRun  $run
     * @return bool
     */
    public function download(User $user, ReportRun $run): bool
    {
        return $this->view($user, $run->report) && 
               ($run->report->created_by === $user->id || $user->can('download all reports'));
    }

    /**
     * Determine whether the user can manage the report schedule.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\ReportSchedule  $schedule
     * @return bool
     */
    public function manageSchedule(User $user, ReportSchedule $schedule): bool
    {
        return $this->update($user, $schedule->report);
    }

    /**
     * Determine whether the user can delete the report schedule.
     *
     * @param  \Prasso\Church\Models\User  $user
     * @param  \Prasso\Church\Models\ReportSchedule  $schedule
     * @return bool
     */
    public function deleteSchedule(User $user, ReportSchedule $schedule): bool
    {
        return $this->update($user, $schedule->report);
    }
}
