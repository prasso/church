# Volunteer Signup Page Implementation Guide

This guide provides step-by-step instructions to create a volunteer signup page in the Church module, similar to the `church-cleaning-signup.html` template. This page allows members to sign up for volunteer positions (like "Clean the Church").

## Overview

The volunteer signup page will:
- Display available volunteer positions
- Allow members to select a position and provide their information
- Submit signup data via API to create a `VolunteerAssignment`
- Show real-time availability (positions with max volunteers)
- Send SMS reminders to volunteers

## Architecture

```
Member → Signup Page (HTML/Blade) → API Endpoint → VolunteerAssignment Model → Database
                                   ↓
                            Church Module API
                            (POST /api/volunteers/assignments)
```

## Step-by-Step Implementation

### Step 1: Create the Volunteer Signup Blade View

Create a new Blade view file in the church module resources:

**File:** `packages/prasso/church/resources/views/volunteer-signup.blade.php`

This view should:
- Display available volunteer positions (fetched via API)
- Show position details: title, description, time commitment, location
- Display current volunteer count vs max volunteers
- Include a form with:
  - Member name (required)
  - Member phone (required)
  - Member email (optional)
  - Selected position (required)
  - Notes/Message (optional)
- Submit via POST to the volunteer assignment API

**Key Features:**
- Use Alpine.js for form interactivity (like the cleaning signup)
- Fetch available positions from `/api/volunteers/positions?is_active=true`
- Disable positions that are full (`activeVolunteers().count() >= max_volunteers`)
- Show success/error messages
- Reset form after successful submission

### Step 2: Create a Web Route

Add a route to display the volunteer signup page in `packages/prasso/church/routes/web.php`:

```php
Route::middleware(['web'])->group(function () {
    // Public volunteer signup page
    Route::get('/volunteer-signup', function () {
        return view('church::volunteer-signup');
    })->name('church.volunteer.signup');
});
```

This makes the page accessible at `/volunteer-signup` (or with your site prefix).

### Step 3: Create an API Endpoint for Volunteer Signup

Create a new controller method in `VolunteerController` to handle volunteer signups:

**File:** `packages/prasso/church/src/Http/Controllers/VolunteerController.php`

Add this method:

```php
/**
 * Store a volunteer assignment (signup).
 * Can be called by authenticated members or guests.
 */
public function assignMember(Request $request, VolunteerPosition $position)
{
    $validated = $request->validate([
        'member_id' => 'required_if:guest_name,null|exists:chm_members,id',
        'guest_name' => 'required_if:member_id,null|string|max:255',
        'guest_email' => 'nullable|email|max:255',
        'guest_phone' => 'required_if:member_id,null|string|max:20',
        'notes' => 'nullable|string|max:1000',
    ]);

    // Check if position is open
    if (!$position->isOpen()) {
        return response()->json([
            'message' => 'This volunteer position is no longer available.',
        ], 422);
    }

    return DB::transaction(function () use ($validated, $position, $request) {
        // If guest signup, create a temporary member record or store as metadata
        $memberId = $validated['member_id'] ?? null;
        
        $assignment = VolunteerAssignment::create([
            'member_id' => $memberId,
            'position_id' => $position->id,
            'status' => 'pending', // Requires admin approval
            'notes' => $validated['notes'] ?? null,
            'metadata' => [
                'guest_name' => $validated['guest_name'] ?? null,
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'signup_date' => now()->toDateTimeString(),
            ],
        ]);

        // TODO: Send SMS reminder to guest_phone or member phone
        // TODO: Send email notification to admin

        return response()->json([
            'message' => 'Thank you for signing up! Your volunteer request has been submitted.',
            'assignment' => $assignment,
        ], 201);
    });
}
```

### Step 4: Add API Route

Update `packages/prasso/church/routes/api.php` to add the signup endpoint:

```php
// Public volunteer signup (no auth required)
Route::post('/volunteers/positions/{position}/signup', [VolunteerController::class, 'assignMember']);
```

Or keep it authenticated if you want only logged-in members:

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/volunteers/positions/{position}/assign', [VolunteerController::class, 'assignMember']);
});
```

### Step 5: Create the Blade View Template

**File:** `packages/prasso/church/resources/views/volunteer-signup.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div x-data="volunteerSignup()" class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Volunteer Signup</h1>
            <p class="text-lg text-gray-600">Sign up to volunteer and serve our church community</p>
        </div>

        <!-- Alert Messages -->
        <template x-if="successMessage">
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800" x-text="successMessage"></p>
            </div>
        </template>

        <template x-if="errorMessage">
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800" x-text="errorMessage"></p>
            </div>
        </template>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Positions Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Available Positions</h2>
                    <p class="text-gray-600 mb-6">Select a volunteer position to sign up:</p>

                    <!-- Positions Grid -->
                    <div class="space-y-4">
                        <template x-for="(position, index) in positions" :key="position.id">
                            <button
                                @click="selectPosition(position)"
                                :disabled="!position.is_open"
                                :class="{
                                    'bg-blue-50 border-2 border-blue-500': selectedPosition?.id === position.id,
                                    'bg-gray-50 border-2 border-gray-300 hover:border-blue-300': selectedPosition?.id !== position.id && position.is_open,
                                    'bg-gray-100 border-2 border-gray-300 cursor-not-allowed opacity-50': !position.is_open
                                }"
                                class="w-full p-4 rounded-lg text-left transition-all"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-semibold text-gray-900" x-text="position.title"></div>
                                        <div class="text-sm text-gray-600 mt-1" x-text="position.description"></div>
                                        <div class="text-sm text-gray-500 mt-2">
                                            <span x-show="position.time_commitment">
                                                <strong>Time:</strong> <span x-text="position.time_commitment"></span>
                                            </span>
                                            <span x-show="position.location" class="ml-4">
                                                <strong>Location:</strong> <span x-text="position.location"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <template x-if="!position.is_open">
                                            <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-semibold">Full</span>
                                        </template>
                                        <template x-if="position.is_open && selectedPosition?.id === position.id">
                                            <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">Selected</span>
                                        </template>
                                        <template x-if="position.is_open && selectedPosition?.id !== position.id">
                                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">Available</span>
                                        </template>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 mt-2" x-show="position.max_volunteers">
                                    <span x-text="`${position.active_volunteers || 0} of ${position.max_volunteers} volunteers`"></span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- Selected Position Details -->
                    <template x-if="selectedPosition">
                        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-blue-900">
                                <strong>Selected Position:</strong> <span x-text="selectedPosition.title"></span>
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Signup Form Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Information</h2>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- Name Field -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                x-model="form.name"
                                required
                                placeholder="John Doe"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <!-- Phone Field -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                x-model="form.phone"
                                required
                                placeholder="(555) 123-4567"
                                pattern="[0-9\-\+\(\)\s]+"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <p class="text-xs text-gray-500 mt-1">We'll send SMS reminders to this number</p>
                        </div>

                        <!-- Email Field -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                x-model="form.email"
                                placeholder="john@example.com"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>

                        <!-- Notes Field -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                Additional Notes
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                x-model="form.notes"
                                rows="3"
                                placeholder="Any special skills or availability information..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                        </div>

                        <!-- Info Box -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-900">
                                <strong>Note:</strong> You will receive SMS text message reminders for your volunteer assignment.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="!isFormValid()"
                            :class="{
                                'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer': isFormValid(),
                                'bg-gray-400 text-gray-200 cursor-not-allowed': !isFormValid()
                            }"
                            class="w-full py-2 px-4 rounded-lg font-semibold transition-colors mt-6"
                        >
                            <template x-if="isSubmitting">
                                <span>Submitting...</span>
                            </template>
                            <template x-if="!isSubmitting">
                                <span>Sign Up to Volunteer</span>
                            </template>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function volunteerSignup() {
        return {
            form: {
                name: '',
                phone: '',
                email: '',
                notes: ''
            },
            selectedPosition: null,
            positions: [],
            isSubmitting: false,
            successMessage: '',
            errorMessage: '',

            async init() {
                await this.loadPositions();
            },

            async loadPositions() {
                try {
                    const response = await fetch('/api/volunteers/positions?is_active=true');
                    if (!response.ok) throw new Error('Failed to load positions');
                    
                    const data = await response.json();
                    this.positions = data.data || data;
                } catch (error) {
                    console.error('Error loading positions:', error);
                    this.errorMessage = 'Failed to load volunteer positions. Please refresh the page.';
                }
            },

            selectPosition(position) {
                if (position.is_open) {
                    this.selectedPosition = this.selectedPosition?.id === position.id ? null : position;
                }
            },

            isFormValid() {
                return this.form.name.trim() !== '' && 
                       this.form.phone.trim() !== '' && 
                       this.selectedPosition !== null;
            },

            async submitForm() {
                if (!this.isFormValid()) {
                    this.errorMessage = 'Please fill in all required fields and select a position.';
                    return;
                }

                this.isSubmitting = true;
                this.errorMessage = '';
                this.successMessage = '';

                try {
                    const response = await fetch(`/api/volunteers/positions/${this.selectedPosition.id}/signup`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        body: JSON.stringify({
                            guest_name: this.form.name,
                            guest_phone: this.form.phone,
                            guest_email: this.form.email,
                            notes: this.form.notes,
                        })
                    });

                    if (!response.ok) {
                        const error = await response.json();
                        throw new Error(error.message || 'Failed to submit signup');
                    }

                    // Show success message
                    this.successMessage = `Success! You've signed up for ${this.selectedPosition.title}. You'll receive SMS reminders at ${this.form.phone}.`;

                    // Reset form
                    this.form = { name: '', phone: '', email: '', notes: '' };
                    this.selectedPosition = null;
                    await this.loadPositions();

                    // Clear success message after 5 seconds
                    setTimeout(() => {
                        this.successMessage = '';
                    }, 5000);

                } catch (error) {
                    console.error('Error:', error);
                    this.errorMessage = error.message || 'An error occurred while submitting your signup. Please try again.';
                } finally {
                    this.isSubmitting = false;
                }
            }
        };
    }
</script>
@endsection
```

### Step 6: Create Volunteer Positions in Admin

In the Filament admin panel, create volunteer positions:

1. Go to `/site-admin/church/volunteer-positions`
2. Create a new position with:
   - **Title:** "Clean the Church"
   - **Description:** "Help keep our church clean and welcoming"
   - **Time Commitment:** "2 hours per week"
   - **Location:** "Church Building"
   - **Max Volunteers:** 4 (or your desired number)
   - **Is Active:** Yes

### Step 7: Link to the Signup Page

Add a link to the volunteer signup page from your site:

- In site pages/navigation: `/volunteer-signup`
- In email communications
- In SMS messages
- In church announcements

### Step 8: Handle Notifications (Optional)

Add SMS and email notifications when someone signs up:

**File:** `packages/prasso/church/src/Http/Controllers/VolunteerController.php`

In the `assignMember` method, add:

```php
// Send SMS reminder to volunteer
if ($validated['guest_phone'] ?? null) {
    // Use Prasso Messaging to send SMS
    // \Prasso\Messaging\Facades\MessageService::sendSms(
    //     $validated['guest_phone'],
    //     "Thank you for signing up to volunteer! We'll send you details soon."
    // );
}

// Send email notification to admin
// Mail::to(config('church.admin_email'))->send(
//     new VolunteerSignupNotification($assignment)
// );
```

## Testing the Implementation

1. **Create a volunteer position** in the admin panel
2. **Visit the signup page:** `/volunteer-signup`
3. **Fill out the form** and submit
4. **Check the database:** Verify `chm_volunteer_assignments` table has the new record
5. **Check admin panel:** View the assignment in `/site-admin/church/volunteer-assignments`

## API Endpoints Reference

### Get Available Positions
```
GET /api/volunteers/positions?is_active=true
```

### Submit Volunteer Signup
```
POST /api/volunteers/positions/{position_id}/signup
Content-Type: application/json

{
    "guest_name": "John Doe",
    "guest_phone": "(555) 123-4567",
    "guest_email": "john@example.com",
    "notes": "Available weekends"
}
```

### Response
```json
{
    "message": "Thank you for signing up!",
    "assignment": {
        "id": 1,
        "position_id": 1,
        "status": "pending",
        "metadata": {
            "guest_name": "John Doe",
            "guest_phone": "(555) 123-4567",
            "guest_email": "john@example.com",
            "signup_date": "2025-04-07T14:30:00Z"
        }
    }
}
```

## Database Schema

The signup data is stored in the `chm_volunteer_assignments` table:

| Column | Type | Notes |
|--------|------|-------|
| id | int | Primary key |
| member_id | int | NULL for guest signups |
| position_id | int | Foreign key to chm_volunteer_positions |
| status | string | 'pending', 'active', 'inactive' |
| notes | text | Volunteer notes |
| metadata | json | Guest info (name, phone, email) |
| created_at | timestamp | Signup date |
| updated_at | timestamp | Last update |

## Next Steps

After implementation:

1. **Test the signup flow** with different positions
2. **Add SMS notifications** when volunteers sign up
3. **Create admin dashboard** to view and manage signups
4. **Add email confirmations** to volunteers
5. **Implement volunteer scheduling** based on availability
6. **Track volunteer hours** for reporting

## Troubleshooting

### Positions not loading
- Check that positions exist in database with `is_active = true`
- Verify API endpoint is accessible: `/api/volunteers/positions`
- Check browser console for errors

### Form submission fails
- Verify CSRF token is present in page
- Check that API endpoint is correct
- Verify position ID is valid

### Guest signups not saving
- Ensure `metadata` column is JSON type in database
- Check that `guest_name` and `guest_phone` are provided
- Verify database migrations have run

