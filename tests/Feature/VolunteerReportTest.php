<?php

namespace Prasso\Church\Tests\Feature;

use Prasso\Church\Models\Member;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;
use Prasso\Church\Models\VolunteerHours;
use Prasso\Church\Tests\TestCase;

class VolunteerReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function admin_can_view_volunteer_assignment_stats()
    {
        $admin = $this->createUserWithRole('admin');
        
        // Create a volunteer position with assignments
        $position = VolunteerPosition::factory()->create();
        $members = Member::factory()->count(3)->create();
        
        foreach ($members as $index => $member) {
            $assignment = VolunteerAssignment::create([
                'position_id' => $position->id,
                'member_id' => $member->id,
                'status' => 'active',
                'start_date' => now()->subMonths($index + 1),
            ]);
            
            // Log some hours for each assignment
            VolunteerHours::create([
                'assignment_id' => $assignment->id,
                'volunteer_id' => $member->id,
                'hours' => ($index + 1) * 2,
                'date' => now()->subDays($index),
                'notes' => 'Test hours',
            ]);
        }
        
        $response = $this->actingAs($admin)->getJson('/api/reports/volunteers/assignment-stats');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'position_id',
                    'position_title',
                    'total_volunteers',
                    'active_volunteers',
                    'avg_months_serving',
                ]
            ]);
    }
    
    /** @test */
    public function volunteer_coordinator_can_view_volunteer_reports()
    {
        $coordinator = $this->createUserWithRole('volunteer_coordinator');
        $position = VolunteerPosition::factory()->create();
        
        $response = $this->actingAs($coordinator)->getJson("/api/reports/volunteers/positions/{$position->id}/report");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'position' => [
                    'id',
                    'title',
                ],
                'period' => [
                    'start_date',
                    'end_date',
                ],
                'assignment_stats',
                'hours_by_position',
                'hours_over_time',
                'demographics',
                'top_volunteers',
            ]);
    }
    
    /** @test */
    public function regular_member_cannot_view_volunteer_reports()
    {
        $member = $this->createUserWithRole('member');
        $position = VolunteerPosition::factory()->create();
        
        $response = $this->actingAs($member)->getJson("/api/reports/volunteers/positions/{$position->id}/report");
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function can_view_top_volunteers()
    {
        $admin = $this->createUserWithRole('admin');
        
        // Create test data
        $position = VolunteerPosition::factory()->create();
        $members = Member::factory()->count(5)->create();
        
        foreach ($members as $index => $member) {
            $assignment = VolunteerAssignment::create([
                'position_id' => $position->id,
                'member_id' => $member->id,
                'status' => 'active',
                'start_date' => now()->subMonths($index + 1),
            ]);
            
            // Log hours (more hours for higher indexes)
            for ($i = 0; $i <= $index; $i++) {
                VolunteerHours::create([
                    'assignment_id' => $assignment->id,
                    'volunteer_id' => $member->id,
                    'hours' => ($index + 1) * 2,
                    'date' => now()->subDays($i),
                    'notes' => 'Test hours',
                ]);
            }
        }
        
        $response = $this->actingAs($admin)->getJson('/api/reports/volunteers/top-volunteers?limit=3');
        
        $response->assertStatus(200)
            ->assertJsonCount(3) // Should return top 3 volunteers
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                    'total_hours',
                    'days_served',
                ]
            ]);
    }
    
    /** @test */
    public function can_filter_volunteer_reports_by_date_range()
    {
        $admin = $this->createUserWithRole('admin');
        $startDate = now()->subYear()->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->getJson("/api/reports/volunteers/hours-over-time?start_date={$startDate}&end_date={$endDate}");
        
        $response->assertStatus(200);
    }
}
