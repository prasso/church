# Church Management - Events Module

## Overview
The Events module provides functionality for managing church events, services, and attendance tracking. It supports both one-time and recurring events with various recurrence patterns.

## Models

### Event
- Represents a church event or service
- Can be one-time or recurring
- Contains event details like title, description, location, etc.
- Related to a Ministry

### EventOccurrence
- Represents a specific instance of a recurring event
- Contains date/time and status information
- Linked to the parent Event

### Attendance
- Tracks member/guest attendance for event occurrences
- Records check-in/out times and status
- Links to Member, Family, and User (who recorded the attendance)

## API Endpoints

### Events
- `GET /api/events` - List events (with filtering)
- `POST /api/events` - Create a new event
- `GET /api/events/{id}` - Get event details
- `PUT /api/events/{id}` - Update an event
- `DELETE /api/events/{id}` - Delete an event

### Event Occurrences
- `GET /api/events/{eventId}/occurrences` - List occurrences for an event
- `GET /api/occurrences/{id}` - Get occurrence details
- `PUT /api/occurrences/{id}` - Update an occurrence
- `DELETE /api/occurrences/{id}` - Delete an occurrence

### Attendance
- `GET /api/occurrences/{occurrenceId}/attendance` - List attendance for an occurrence
- `POST /api/occurrences/{occurrenceId}/attendance` - Record attendance
- `PUT /api/attendance/{id}` - Update attendance record
- `DELETE /api/attendance/{id}` - Delete attendance record

## Usage Examples

### Creating a Recurring Event
```php
$event = new Event([
    'title' => 'Sunday Service',
    'description' => 'Weekly worship service',
    'start_date' => '2025-09-15',
    'start_time' => '10:00:00',
    'end_time' => '11:30:00',
    'location' => 'Main Sanctuary',
    'type' => 'service',
    'recurrence_pattern' => 'weekly',
    'recurrence_interval' => 1,
    'recurrence_days' => [0], // Sunday
    'end_date' => '2025-12-31',
]);
$event->save();
```

### Recording Attendance
```php
$attendance = new Attendance([
    'event_occurrence_id' => $occurrence->id,
    'member_id' => $member->id,
    'status' => 'present',
    'check_in_time' => now(),
]);
$attendance->save();
```

## Database Schema

### chm_events
- id (bigint)
- title (string)
- description (text, nullable)
- start_date (date)
- end_date (date, nullable)
- start_time (time)
- end_time (time, nullable)
- location (string, nullable)
- type (enum: service, meeting, event, other)
- status (enum: draft, published, cancelled)
- recurrence_pattern (enum: none, daily, weekly, monthly, yearly)
- recurrence_interval (integer)
- recurrence_days (json, nullable)
- requires_registration (boolean)
- max_attendees (integer, nullable)
- created_by (foreign key to users)
- ministry_id (foreign key to chm_ministries, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)

### chm_event_occurrences
- id (bigint)
- event_id (foreign key to chm_events)
- date (date)
- start_time (time)
- end_time (time, nullable)
- status (enum: scheduled, cancelled, completed)
- notes (text, nullable)
- created_at (timestamp)
- updated_at (timestamp)

### chm_attendances
- id (bigint)
- event_occurrence_id (foreign key to chm_event_occurrences)
- member_id (foreign key to chm_members, nullable)
- family_id (foreign key to chm_families, nullable)
- guest_name (string, nullable)
- check_in_time (timestamp, nullable)
- check_out_time (timestamp, nullable)
- status (enum: present, late, excused, absent)
- notes (text, nullable)
- recorded_by (foreign key to users)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable)
