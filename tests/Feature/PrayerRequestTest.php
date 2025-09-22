<?php

namespace Prasso\Church\Tests\Feature;

use Prasso\Church\Models\Member;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PrayerRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** @test */
    public function user_can_create_prayer_request()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        $response = $this->actingAs($user)->postJson('/api/prayer-requests', [
            'title' => 'Test Prayer Request',
            'description' => 'Please pray for my family',
            'is_public' => true,
        ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Test Prayer Request',
                'is_public' => true,
                'requested_by' => $member->id,
                'member_id' => $member->id,
            ]);
            
        $this->assertDatabaseHas('chm_prayer_requests', [
            'title' => 'Test Prayer Request',
            'member_id' => $member->id,
            'requested_by' => $member->id,
        ]);
    }
    
    /** @test */
    public function user_can_view_public_prayer_requests()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        // Create a public prayer request
        $prayerRequest = PrayerRequest::factory()->create([
            'is_public' => true,
            'member_id' => $member->id,
            'requested_by' => $member->id,
        ]);
        
        $response = $this->actingAs($user)->getJson('/api/prayer-requests');
        
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $prayerRequest->id,
                'title' => $prayerRequest->title,
            ]);
    }
    
    /** @test */
    public function user_can_view_own_private_prayer_requests()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        // Create a private prayer request
        $prayerRequest = PrayerRequest::factory()->create([
            'is_public' => false,
            'member_id' => $member->id,
            'requested_by' => $member->id,
        ]);
        
        $response = $this->actingAs($user)->getJson("/api/prayer-requests/{$prayerRequest->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'id' => $prayerRequest->id,
                'title' => $prayerRequest->title,
                'is_public' => false,
            ]);
    }
    
    /** @test */
    public function user_cannot_view_others_private_prayer_requests()
    {
        $user1 = $this->createUserWithRole('member');
        $user2 = $this->createUserWithRole('member');
        
        // Create a private prayer request for user1
        $prayerRequest = PrayerRequest::factory()->create([
            'is_public' => false,
            'member_id' => $user1->member->id,
            'requested_by' => $user1->member->id,
        ]);
        
        // User2 tries to view user1's private prayer request
        $response = $this->actingAs($user2)->getJson("/api/prayer-requests/{$prayerRequest->id}");
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_view_all_prayer_requests()
    {
        $admin = $this->createUserWithRole('admin');
        $member = $this->createUserWithRole('member')->member;
        
        // Create a private prayer request
        $prayerRequest = PrayerRequest::factory()->create([
            'is_public' => false,
            'member_id' => $member->id,
            'requested_by' => $member->id,
        ]);
        
        $response = $this->actingAs($admin)->getJson("/api/prayer-requests/{$prayerRequest->id}");
        
        $response->assertStatus(200)
            ->assertJson([
                'id' => $prayerRequest->id,
                'title' => $prayerRequest->title,
            ]);
    }
    
    /** @test */
    public function user_can_update_own_prayer_request()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        $prayerRequest = PrayerRequest::factory()->create([
            'member_id' => $member->id,
            'requested_by' => $member->id,
        ]);
        
        $response = $this->actingAs($user)->putJson("/api/prayer-requests/{$prayerRequest->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'is_public' => false,
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'is_public' => false,
            ]);
    }
    
    /** @test */
    public function user_can_delete_own_prayer_request()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        $prayerRequest = PrayerRequest::factory()->create([
            'member_id' => $member->id,
            'requested_by' => $member->id,
        ]);
        
        $response = $this->actingAs($user)->deleteJson("/api/prayer-requests/{$prayerRequest->id}");
        
        $response->assertStatus(200);
        $this->assertSoftDeleted('chm_prayer_requests', ['id' => $prayerRequest->id]);
    }
    
    /** @test */
    public function user_can_increment_prayer_count()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        $prayerRequest = PrayerRequest::factory()->create([
            'member_id' => $member->id,
            'requested_by' => $member->id,
            'prayer_count' => 0,
        ]);
        
        $response = $this->actingAs($user)->postJson("/api/prayer-requests/{$prayerRequest->id}/pray");
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Prayer count incremented',
                'prayer_count' => 1,
            ]);
            
        $this->assertEquals(1, $prayerRequest->fresh()->prayer_count);
    }
    
    /** @test */
    public function user_can_view_group_prayer_requests()
    {
        $user = $this->createUserWithRole('member');
        $member = $user->member;
        
        $group = Group::factory()->create();
        $group->members()->attach($member->id, ['role' => 'member', 'status' => 'active']);
        
        $prayerRequest = PrayerRequest::factory()->create([
            'member_id' => $member->id,
            'requested_by' => $member->id,
            'is_public' => true,
        ]);
        
        $group->prayerRequests()->attach($prayerRequest->id);
        
        $response = $this->actingAs($user)->getJson("/api/groups/{$group->id}/prayer-requests");
        
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $prayerRequest->id,
                'title' => $prayerRequest->title,
            ]);
    }
}
