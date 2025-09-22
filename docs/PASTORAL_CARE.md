# Pastoral Care Module

## Overview
The Pastoral Care module provides tools for managing prayer requests and pastoral visits within the church community. It enables church staff to track and respond to the spiritual needs of members and visitors.

## Features

### Prayer Requests
- Members can submit prayer requests
- Support for public and private prayer requests
- Anonymous prayer requests
- Prayer request status tracking (active, answered, inactive)
- Prayer counter for each request
- Categorization and tagging of prayer requests
- Prayer request follow-up and updates

### Pastoral Visits
- Schedule and track pastoral visits
- Assign visits to staff members
- Visit status tracking (scheduled, in-progress, completed, canceled)
- Visit notes and follow-up actions
- Spiritual needs tracking
- Visit history and reporting
- Calendar integration

### Notifications
- Email notifications for prayer requests and visit updates
- SMS reminders for upcoming visits
- Follow-up reminders
- Staff assignment notifications

## Database Schema

### Tables
- `chm_prayer_requests` - Stores prayer request information
- `chm_pastoral_visits` - Tracks pastoral visits
- `chm_prayer_request_groups` - Maps prayer requests to groups (many-to-many)

## API Endpoints

### Prayer Requests
- `GET /api/pastoral-care/prayer-requests` - List prayer requests
- `POST /api/pastoral-care/prayer-requests` - Create a new prayer request
- `GET /api/pastoral-care/prayer-requests/{id}` - Get a specific prayer request
- `PUT /api/pastoral-care/prayer-requests/{id}` - Update a prayer request
- `DELETE /api/pastoral-care/prayer-requests/{id}` - Delete a prayer request
- `POST /api/pastoral-care/prayer-requests/{id}/pray` - Record that someone prayed for a request

### Pastoral Visits
- `GET /api/pastoral-care/visits` - List pastoral visits
- `POST /api/pastoral-care/visits` - Schedule a new visit
- `GET /api/pastoral-care/visits/{id}` - Get a specific visit
- `PUT /api/pastoral-care/visits/{id}` - Update a visit
- `DELETE /api/pastoral-care/visits/{id}` - Cancel a visit
- `POST /api/pastoral-care/visits/{id}/start` - Mark a visit as in-progress
- `POST /api/pastoral-care/visits/{id}/complete` - Complete a visit
- `GET /api/pastoral-care/visits/calendar/events` - Get visits formatted for a calendar

### Member-Specific Routes
- `GET /api/pastoral-care/members/{memberId}/prayer-requests` - Get prayer requests for a member
- `GET /api/pastoral-care/members/{memberId}/visits` - Get visits for a member

### Family-Specific Routes
- `GET /api/pastoral-care/families/{familyId}/prayer-requests` - Get prayer requests for a family
- `GET /api/pastoral-care/families/{familyId}/visits` - Get visits for a family

## Configuration

### Environment Variables
```
# Enable/disable pastoral care features
PASTORAL_CARE_ENABLED=true

# Default settings for prayer requests
DEFAULT_PRAYER_REQUEST_STATUS=active
DEFAULT_PRAYER_REQUEST_VISIBILITY=public

# Default settings for pastoral visits
DEFAULT_VISIT_STATUS=scheduled
DEFAULT_VISIT_DURATION=60  # minutes

# Notification settings
NOTIFY_ON_VISIT_SCHEDULED=true
NOTIFY_ON_VISIT_STARTED=true
NOTIFY_ON_VISIT_COMPLETED=true
NOTIFY_ON_PRAYER_REQUEST=true
NOTIFY_ON_PRAYER_ANSWERED=true

# Reminder settings
SEND_VISIT_REMINDERS=true
VISIT_REMINDER_LEAD_TIME=24  # hours
VISIT_REMINDER_METHOD=email  # email, sms, both

# Follow-up settings
SEND_FOLLOW_UPS=true
FOLLOW_UP_AFTER_DAYS=7
FOLLOW_UP_METHOD=email  # email, sms, both
```

## Usage Examples

### Creating a Prayer Request
```http
POST /api/pastoral-care/prayer-requests
Content-Type: application/json
Authorization: Bearer {token}

{
    "title": "Prayer for Healing",
    "description": "Please pray for my mother who is recovering from surgery.",
    "is_anonymous": false,
    "is_public": true,
    "category_ids": [1, 3]
}
```

### Scheduling a Pastoral Visit
```http
POST /api/pastoral-care/visits
Content-Type: application/json
Authorization: Bearer {token}

{
    "title": "Hospital Visit",
    "purpose": "Visit with John Doe who is recovering from surgery",
    "scheduled_for": "2023-06-15 14:00:00",
    "location_type": "hospital",
    "location_details": "St. Mary's Hospital, Room 302",
    "member_id": 123,
    "assigned_to": 456,
    "notes": "John prefers morning visits if possible"
}
```

### Updating a Visit Status
```http
POST /api/pastoral-care/visits/789/complete
Content-Type: application/json
Authorization: Bearer {token}

{
    "notes": "John is in good spirits and recovering well.",
    "outcome_summary": "Prayed with John and his family. He's responding well to treatment.",
    "follow_up_actions": "Follow up in one week to check on recovery progress"
}
```

## Permissions

### Prayer Requests
- `view prayer requests` - View prayer requests
- `create prayer requests` - Create new prayer requests
- `update prayer requests` - Update existing prayer requests
- `delete prayer requests` - Delete prayer requests
- `pray for requests` - Record prayers for requests
- `view private requests` - View prayer requests marked as private

### Pastoral Visits
- `view visits` - View pastoral visits
- `create visits` - Schedule new visits
- `update visits` - Update visit details
- `delete visits` - Cancel visits
- `start visits` - Mark visits as in-progress
- `complete visits` - Mark visits as completed
- `view confidential visits` - View visits marked as confidential

## Events

The module dispatches the following events:

- `Prasso\Church\Events\PrayerRequestCreated` - When a new prayer request is created
- `Prasso\Church\Events\PastoralVisitAssigned` - When a visit is assigned to a staff member
- `Prasso\Church\Events\PastoralVisitCompleted` - When a visit is marked as completed

## Notifications

The module sends the following notifications:

- `PastoralVisitScheduledNotification` - When a visit is scheduled
- `PastoralVisitAssignedNotification` - When a visit is assigned to a staff member
- `PastoralVisitCompletedNotification` - When a visit is completed
- `PastoralVisitFollowUpNotification` - Follow-up after a visit
- `PrayerRequestReceivedNotification` - When a new prayer request is received
- `PrayerRequestUpdatedNotification` - When a prayer request is updated
- `PrayerRequestAnsweredNotification` - When a prayer request is marked as answered

## Testing

Run the tests with:

```bash
php vendor/bin/phpunit --filter=PastoralCareTest
```

## Security

- All endpoints are protected by Sanctum authentication
- Users can only view their own prayer requests and visits unless they have appropriate permissions
- Sensitive information is encrypted at rest
- Audit logging for all changes to prayer requests and visits

## Dependencies

- Laravel Framework
- Laravel Sanctum for API authentication
- Laravel Notifications for email and SMS
- Laravel Horizon for queue management (recommended)

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This module is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
