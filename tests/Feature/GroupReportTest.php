<?php

namespace Prasso\Church\Tests\Feature;

use Prasso\Church\Models\Group;
use Prasso\Church\Models\Member;
use Prasso\Church\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function admin_can_view_group_membership_stats()
    {
        $admin = $this->createUserWithRole('admin');
        $group = Group::factory()->create();
        
        // Add some members to the group
        $members = Member::factory()->count(5)->create();
        foreach ($members as $index => $member) {
            $group->members()->attach($member->id, [
                'status' => 'active',
                'role' => $index === 0 ? 'leader' : 'member',
                'join_date' => now()->subMonths($index + 1),
            ]);
        }
        
        $response = $this->actingAs($admin)->getJson('/api/reports/groups/membership-stats');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'group_id',
                    'group_name',
                    'total_members',
                    'active_members',
                    'leaders_count',
                ]
            ]);
    }
    
    /** @test */
    public function group_leader_can_view_their_group_report()
    {
        $leader = $this->createUserWithRole('member');
        $group = Group::factory()->create();
        
        // Make the user a leader of the group
        $group->members()->attach($leader->member->id, [
            'status' => 'active',
            'role' => 'leader',
            'join_date' => now()->subYear(),
        ]);
        
        $response = $this->actingAs($leader)->getJson("/api/reports/groups/{$group->id}/report");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'group' => [
                    'id',
                    'name',
                ],
                'period' => [
                    'start_date',
                    'end_date',
                ],
                'membership_stats' => [
                    'position_id',
                    'position_title',
                    'total_volunteers',
                    'active_volunteers',
                    'avg_months_serving',
                ],
                'growth',
                'engagement',
                'demographics',
            ]);
    }
    
    /** @test */
    public function regular_member_cannot_view_group_reports()
    {
        $member = $this->createUserWithRole('member');
        $group = Group::factory()->create();
        
        // Add member to the group but not as a leader
        $group->members()->attach($member->member->id, [
            'status' => 'active',
            'role' => 'member',
            'join_date' => now()->subYear(),
        ]);
        
        $response = $this->actingAs($member)->getJson("/api/reports/groups/{$group->id}/report");
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function can_filter_reports_by_date_range()
    {
        $admin = $this->createUserWithRole('admin');
        $startDate = now()->subYear()->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $response = $this->actingAs($admin)->getJson("/api/reports/groups/growth?start_date={$startDate}&end_date={$endDate}");
        
        $response->assertStatus(200);
    }
}
