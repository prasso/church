# CHM Member Dashboard Documentation

## Overview

The CHM Member Dashboard is a dedicated portal for church members to manage their volunteer commitments, view available opportunities, and maintain their contact information. It is accessible to all authenticated users who have an associated `Member` record in the CHM system.

**Route:** `/member` (named `church.member.dashboard`)  
**Authentication:** Required (via `auth` middleware)  
**Access:** Any authenticated user with a linked `Member` record  
**Location:** CHM Package (`Prasso\Church\Livewire\MemberDashboard`)  
**Documentation:** `packages/prasso/church/docs/CHM_MEMBER_DASHBOARD.md`

---

## Architecture

### Components

#### 1. **Livewire Component: `MemberDashboard`**
- **File:** `packages/prasso/church/src/Livewire/MemberDashboard.php`
- **Namespace:** `Prasso\Church\Livewire`
- **Responsibilities:**
  - Load member profile from `Member` model linked to authenticated user
  - Fetch available volunteer positions (open, active, within date range)
  - Fetch member's active volunteer assignments
  - Handle volunteer signup/cancellation
  - Manage profile editing

#### 2. **Blade View: `member-dashboard.blade.php`**
- **File:** `packages/prasso/church/resources/views/livewire/member-dashboard.blade.php`
- **Features:**
  - Tab-based navigation (Overview, Volunteer Opportunities, Profile)
  - Responsive grid layout (Tailwind CSS)
  - Real-time updates via Livewire
  - Warm, member-focused design (not admin-centric)

---

## Features

### Tab 1: Overview
Displays member summary and quick stats:
- **Member Info Card:** Name, membership status, member since date
- **Active Roles Card:** Count of current volunteer assignments
- **Open Roles Card:** Count of available volunteer opportunities
- **Current Assignments List:** Shows all active assignments with dates and notes
  - Cancel button to withdraw from a role

### Tab 2: Volunteer Opportunities
Lists all available volunteer positions:
- **Position Cards:** Title, location, description, time commitment, capacity
- **Sign Up Button:** Allows member to join an open position
- **Validation:**
  - Checks position is still open (capacity, date range)
  - Prevents duplicate assignments
  - Shows error if position is full

### Tab 3: Profile
Member contact information management:
- **View Mode:** Displays current contact details (read-only)
- **Edit Mode:** Form to update:
  - First/Last Name
  - Email, Phone
  - Address, City, State, Postal Code
- **Save Changes:** Updates `Member` record in database

---

## Data Model Integration

### Member Model
- **Relationship:** `User` → `Member` (via `user_id`)
- **Fillable Fields:**
  - `first_name`, `last_name`, `email`, `phone`
  - `address`, `city`, `state`, `postal_code`
  - `membership_status`, `membership_date`
  - `user_id` (link to login account)

### VolunteerPosition Model
- **Key Fields:**
  - `title`, `description`, `location`
  - `is_active`, `max_volunteers`
  - `start_date`, `end_date` (date window)
  - `time_commitment`, `skills_required`
- **Methods:**
  - `isOpen()` - Checks if position accepts new volunteers
  - `activeVolunteers()` - Returns current active assignments

### VolunteerAssignment Model
- **Key Fields:**
  - `member_id`, `position_id`
  - `start_date`, `end_date`
  - `status` (active, inactive, pending, completed)
  - `assigned_by`, `approved_by` (user IDs)
  - `notes`, `trained_on`
- **Scopes:**
  - `active()` - Returns active assignments within date range

---

## User Flows

### Flow 1: Member Signs Up for Volunteer Role
1. Member logs in → redirected to `/member`
2. Clicks "Volunteer Opportunities" tab
3. Sees list of open positions
4. Clicks "Sign Up" on desired position
5. System validates:
   - Position is still open
   - Member doesn't already have this role
6. Creates `VolunteerAssignment` with `status = active`
7. Shows success notification
8. Assignment appears in "Your Current Assignments"

### Flow 2: Member Cancels Assignment
1. Member views "Overview" tab
2. Sees "Your Current Assignments" list
3. Clicks "Cancel" on an assignment
4. System updates assignment:
   - `status = inactive`
   - `end_date = now()`
5. Shows success notification
6. Assignment removed from active list

### Flow 3: Member Updates Contact Info
1. Member clicks "Profile" tab
2. Clicks "Edit" button
3. Form becomes editable
4. Updates desired fields
5. Clicks "Save Changes"
6. System validates and saves to `Member` record
7. Shows success notification
8. Returns to view mode

---

## Dual Access: Member + Admin Functions

The member dashboard is **in addition to** any admin functions the user may have:

- **Regular User:** Only sees member dashboard at `/member`
- **User + Site Admin:** Sees both:
  - Member dashboard at `/member` (member-level access)
  - Member dashboard widget in admin dashboard (integrated view)
  - Site admin panel at `/site-admin` (admin-level access)
- **Super Admin:** Sees both:
  - Member dashboard at `/member` (if they have a Member record)
  - Member dashboard widget in admin dashboard (integrated view)
  - Admin panel at `/admin` (full system access)

### Admin Dashboard Integration

Admin users who are also church members see a **Member Dashboard Widget** directly in their main dashboard at `/dashboard` (the default landing page after login):

**Features:**
- **Member Info Card:** Name, status, active roles, open opportunities
- **Current Assignments:** Shows active volunteer assignments with status
- **Available Opportunities:** Lists up to 5 open volunteer positions
- **Quick Actions:** Sign up for volunteer roles directly from admin dashboard
- **Full Dashboard Link:** Button to navigate to complete member dashboard

**Smart Visibility:**
- Only shows for users with both admin role AND member record
- Hidden from regular admins without member profiles
- Hidden from members without admin access

**Navigation:** Users can navigate between roles via:
- Logout and re-login with different role
- Dashboard switcher (if implemented)
- Direct URL access to appropriate role
- Integrated widget in admin dashboard (for admin+members)

---

## API Endpoints Used

The member dashboard uses CHM API endpoints (Sanctum-protected):

### Volunteer Positions
- `GET /api/volunteers/positions` - List available positions
- `POST /api/volunteers/positions/{position}/assign` - Sign up for role
- `DELETE /api/volunteers/positions/{position}/unassign/{member}` - Cancel role

### Volunteer Assignments
- `GET /api/volunteers/assignments` - List member's assignments
- `PUT /api/volunteers/assignments/{assignment}` - Update assignment

---

## Styling & Design

### Design Philosophy
- **Warm & Welcoming:** Church-focused, not corporate
- **Member-Centric:** Focuses on member needs, not admin functions
- **Accessible:** Clear navigation, readable text, good contrast
- **Responsive:** Works on mobile, tablet, desktop

### Color Scheme
- **Primary:** Blue (`blue-600`) for actions
- **Success:** Green for confirmations
- **Warning:** Yellow for alerts
- **Error:** Red for cancellations
- **Neutral:** Slate grays for backgrounds and text

### Components
- **Cards:** White background, subtle shadow, slate borders
- **Buttons:** Rounded, hover effects, clear intent
- **Forms:** Clean inputs with focus states
- **Tabs:** Underline style, active indicator

---

## Member Record Creation

**Important:** The member dashboard requires a `Member` record linked to the user's account.

### Current Status
- CHM auth register (`/api/auth/register`) creates `User` only
- **Does NOT automatically create `Member` record**

### Solution for First Batch Import
When importing members:
1. Create `User` records (or use existing)
2. Create corresponding `Member` records
3. Link via `member.user_id = user.id`
4. Set `membership_status` (visitor, regular_attendee, member, etc.)
5. Set `membership_date` if known

**Example:**
```php
$user = User::find($userId);
Member::create([
    'user_id' => $user->id,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => $user->email,
    'phone' => '555-1234',
    'membership_status' => 'member',
    'membership_date' => now(),
]);
```

---

## Error Handling

The component handles common errors gracefully:

- **No Member Record:** Shows alert, suggests contacting church office
- **Position No Longer Open:** Shows error notification
- **Duplicate Assignment:** Shows warning notification
- **Update Failures:** Shows error with exception message
- **Unauthorized Actions:** Prevents cross-member access

---

## Future Enhancements

Potential additions to the member dashboard:

1. **Prayer Requests:** Submit and view prayer requests
2. **Giving History:** View donations and pledges
3. **Attendance:** View attendance at events/services
4. **Family Members:** Manage family profile information
5. **Event Calendar:** View upcoming events and services
6. **Notifications:** Push/email notifications for assignments
7. **Skills Management:** Members can list their skills
8. **Availability Calendar:** Members can set availability windows

---

## Testing Checklist

- [ ] User without Member record sees alert
- [ ] User with Member record loads dashboard
- [ ] Overview tab shows correct stats
- [ ] Can sign up for available position
- [ ] Cannot sign up for full position
- [ ] Cannot sign up twice for same position
- [ ] Can cancel active assignment
- [ ] Can edit and save profile
- [ ] Profile changes persist after reload
- [ ] Responsive on mobile/tablet/desktop
- [ ] Admin user can access both `/member` and `/site-admin`

---

## Files Created/Modified

### New Files (CHM Package)
- `packages/prasso/church/src/Livewire/MemberDashboard.php` - Livewire component
- `packages/prasso/church/resources/views/livewire/member-dashboard.blade.php` - Blade view
- `packages/prasso/church/src/Livewire/MemberDashboardWidget.php` - Dashboard widget component
- `packages/prasso/church/resources/views/widgets/member-dashboard-widget.blade.php` - Widget view
- `packages/prasso/church/resources/views/widgets/member-dashboard-widget-empty.blade.php` - Empty view

### Modified Files
- `packages/prasso/church/routes/web.php` - Added `/member` route
- `packages/prasso/church/src/ChurchServiceProvider.php` - Updated views path
- `resources/views/components/dashboard.blade.php` - Added member dashboard widget

---

## Configuration

No additional configuration required. The dashboard uses:
- Existing CHM models and relationships
- Existing Sanctum authentication
- Existing Livewire setup
- Tailwind CSS (already in project)

---

## Support

For issues or questions:
1. Check member has a linked `Member` record
2. Verify `user_id` is set on Member model
3. Check volunteer positions are marked `is_active = true`
4. Verify date ranges on positions (if set)
5. Check browser console for JavaScript errors
