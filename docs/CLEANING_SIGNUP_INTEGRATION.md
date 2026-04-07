# Church Cleaning Signup - Volunteer System Integration

## Overview

The church cleaning signup has been refactored to integrate with the existing volunteer management system instead of using a separate database table. This provides consistency, leverages existing admin tools, and enables integrated reporting and messaging.

## Architecture

```
Member Dashboard
    ↓
Cleaning Signup Form (Blade View)
    ↓
CleaningSignupController
    ↓
VolunteerAssignment Model
    ↓
chm_volunteer_assignments table
```

## How It Works

### 1. Volunteer Position Setup

First, create a "Clean the Church" volunteer position in the Filament admin:

**Path:** `/site-admin/church/volunteer-positions`

**Fields:**
- **Title:** "Clean the Church"
- **Description:** "Help keep our church clean and welcoming"
- **Time Commitment:** "2 hours per week"
- **Location:** "Church Building"
- **Max Volunteers:** 4 (or however many per week)
- **Is Active:** Yes

### 2. Cleaning Signup Flow

When a member visits `/cleaning-signup`:

1. **Show** - Controller fetches the "Clean the Church" position
2. **Display** - Blade view shows 6-week schedule with availability
3. **Submit** - Form submits name, phone, and preferred week
4. **Store** - Controller creates a `VolunteerAssignment` record with:
   - `position_id` → "Clean the Church" position
   - `member_id` → null (guest signup)
   - `status` → 'pending' (requires admin approval)
   - `metadata` → Contains guest info and preferred week

### 3. Data Storage

All signup data is stored in the existing `chm_volunteer_assignments` table:

```php
VolunteerAssignment::create([
    'position_id' => $position->id,
    'member_id' => null,
    'status' => 'pending',
    'notes' => "Guest signup via cleaning form",
    'metadata' => [
        'guest_name' => 'John Doe',
        'guest_phone' => '(555) 123-4567',
        'preferred_week' => 2,
        'data_key' => 'cleaning_signup_1712500800000',
        'signup_date' => '2025-04-07T14:30:00Z',
        'ip_address' => '192.168.1.1',
    ],
]);
```

### 4. Schedule Availability

The `getSchedule()` endpoint returns real-time availability:

```json
[
    {
        "weekNumber": 1,
        "taken": false,
        "count": 0,
        "maxVolunteers": 4
    },
    {
        "weekNumber": 2,
        "taken": true,
        "count": 4,
        "maxVolunteers": 4
    }
]
```

A week is marked "taken" when it reaches the position's `max_volunteers` limit.

## Benefits vs. Separate Table

| Aspect | Separate Table | Volunteer System |
|--------|---|---|
| **Admin Management** | Custom UI needed | Use existing Filament resources |
| **Reporting** | Separate reports | Integrated with volunteer reports |
| **Messaging** | Manual integration | Built-in SMS/email integration |
| **Scalability** | Hard to add positions | Easy to create new positions |
| **Data Consistency** | Duplicated logic | Single source of truth |
| **Authorization** | Custom checks | Existing permission system |
| **Audit Trail** | Manual logging | Automatic with model events |

## Admin Workflow

### Viewing Signups

1. Go to `/site-admin/church/volunteer-assignments`
2. Filter by position: "Clean the Church"
3. View all pending signups with guest info in metadata
4. Click on assignment to see full details

### Approving Signups

1. Click on pending assignment
2. Change status from 'pending' to 'active'
3. Optionally set start_date and end_date
4. Save

### Sending Reminders

Once approved, use the messaging system to send SMS reminders:

```php
// Example: Send SMS reminder to volunteer
\Prasso\Messaging\Facades\MessageService::sendSms(
    $assignment->metadata['guest_phone'],
    "You're scheduled to clean the church on Week {$assignment->metadata['preferred_week']}. Thank you for volunteering!"
);
```

## API Integration

### Get Cleaning Position

```php
$position = VolunteerPosition::where('title', 'Clean the Church')->first();
```

### Create Assignment

```php
VolunteerAssignment::create([
    'position_id' => $position->id,
    'member_id' => null,
    'status' => 'pending',
    'metadata' => [
        'guest_name' => $name,
        'guest_phone' => $phone,
        'preferred_week' => $week,
    ],
]);
```

### Get Availability

```php
$assignments = VolunteerAssignment::where('position_id', $position->id)
    ->where('status', 'active')
    ->get();
```

## Database Schema

Uses existing `chm_volunteer_assignments` table:

```sql
CREATE TABLE chm_volunteer_assignments (
    id INT PRIMARY KEY,
    member_id INT NULL,
    position_id INT NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    status VARCHAR(50),
    notes TEXT NULL,
    assigned_by INT NULL,
    approved_by INT NULL,
    trained_on DATE NULL,
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (position_id) REFERENCES chm_volunteer_positions(id)
);
```

## Metadata Structure

The `metadata` JSON field stores guest information:

```json
{
    "guest_name": "John Doe",
    "guest_phone": "(555) 123-4567",
    "preferred_week": 2,
    "data_key": "cleaning_signup_1712500800000",
    "signup_date": "2025-04-07T14:30:00Z",
    "ip_address": "192.168.1.1"
}
```

## Routes

All routes require authentication (middleware: web, auth):

- **GET** `/cleaning-signup` - Display signup form
  - Route name: `church.cleaning.signup.show`
  - Controller: `CleaningSignupController@show`

- **POST** `/cleaning-signup` - Submit signup
  - Route name: `church.cleaning.signup.store`
  - Controller: `CleaningSignupController@store`

- **GET** `/cleaning-signup/schedule` - Get availability
  - Route name: `church.cleaning.signup.schedule`
  - Controller: `CleaningSignupController@getSchedule`

## Setup Instructions

1. **Create the volunteer position** in admin:
   - Title: "Clean the Church"
   - Set max_volunteers to desired number per week
   - Mark as active

2. **Access the signup form:**
   - From member dashboard: Click "Church Cleaning" card
   - Direct URL: `/cleaning-signup`

3. **Approve signups:**
   - Go to `/site-admin/church/volunteer-assignments`
   - Filter by position
   - Review pending signups
   - Change status to 'active' to approve

4. **Send reminders:**
   - Use messaging system to send SMS/email
   - Reference guest_phone from metadata

## Future Enhancements

- [ ] Automated SMS reminders when signup is approved
- [ ] Email confirmation to guest email (if captured)
- [ ] Calendar view of cleaning schedule
- [ ] Volunteer hours tracking
- [ ] Recurring assignments (weekly/monthly)
- [ ] Skill matching for specialized cleaning tasks
- [ ] Team assignments (multiple people per week)

## Troubleshooting

### "Cleaning position not found" error

The "Clean the Church" volunteer position doesn't exist. Create it in the admin:
- Go to `/site-admin/church/volunteer-positions`
- Create new position with title "Clean the Church"

### Schedule not loading

Check that:
- Position exists and is active
- API endpoint `/cleaning-signup/schedule` is accessible
- Browser console for JavaScript errors

### Signups not appearing

Verify:
- Position ID is correct
- Assignments are being created (check database)
- Status is set to 'active' to show in availability

