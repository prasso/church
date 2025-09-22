# Prasso Church Management

A modular Laravel package that helps churches manage people, care, and events. This repository currently includes:

- Pastoral Care (Prayer Requests and Pastoral Visits)
- Events (Events, Recurring Occurrences, and Attendance)

For a complete, non-technical overview of expected capabilities, see `docs/REQUIREMENTS.md`.


## Who this is for

- Church staff and pastors coordinating care, follow‑ups, and visits
- Ministry leaders scheduling events and tracking attendance
- Volunteers helping with check‑ins and follow‑ups
- Administrators managing privacy, permissions, and integrations


## What you can do

### Pastoral Care
Tools that support caring well for people.

- Submit and manage prayer requests (public/private, anonymous)
- Track status (active, answered, inactive) and prayer counts
- Categorize/tag requests and record follow‑ups
- Schedule and track pastoral visits (home, hospital, phone, etc.)
- Assign visits, update statuses (scheduled, in‑progress, completed, canceled)
- Capture notes, outcomes, and follow‑up actions
- Calendar‑friendly feed for visit planning
- Optional notifications and reminders (email/SMS)

See details: `docs/PASTORAL_CARE.md`.

### Events & Attendance
Plan services and church events and record attendance.

- Create one‑time or recurring events (daily/weekly/monthly/yearly)
- Configure intervals and days (e.g., every Sunday)
- Manage event occurrences and statuses (scheduled, canceled, completed)
- Record attendance for members, families, and guests
- Track check‑in/out times and statuses (present, late, excused, absent)
- Associate events to ministries; optionally set capacity/registration

See details: `docs/EVENTS.md`.


## Requirements overview

A high‑level, user‑friendly feature set that churches typically expect is documented in:

- `docs/REQUIREMENTS.md`

This includes areas like member management, communications, finances, ministries/groups, attendance, reporting, integrations, security, and more. Not all listed features are implemented yet; the document serves as a north‑star for planning and prioritization.


## Getting started (Laravel)

1) Install the package via Composer in your Laravel app:

```bash
composer require prasso/church
```

2) Configure environment (optional defaults shown):

```env
# Enable/disable pastoral care features
PASTORAL_CARE_ENABLED=true

# Prayer request defaults
DEFAULT_PRAYER_REQUEST_STATUS=active
DEFAULT_PRAYER_REQUEST_VISIBILITY=public

# Pastoral visit defaults
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

3) Run migrations and set up queues/notifications as needed:

```bash
php artisan migrate
```

Laravel Horizon is recommended for queue management at scale.


## Permissions (examples)

- Prayer Requests: `view`, `create`, `update`, `delete`, `pray for requests`, `view private requests`
- Pastoral Visits: `view`, `create`, `update`, `delete`, `start`, `complete`, `view confidential visits`

Assign permissions to roles (e.g., Pastor, Staff, Volunteer) using your Laravel auth/permission setup.


## Security & privacy

- API endpoints are secured with Laravel Sanctum
- Role‑based access ensures people only see what they should
- Sensitive fields can be encrypted at rest
- Audit logging is recommended for prayer requests and visits

Partner with church leadership to define policies for confidential information.


## API quick view

- Pastoral Care
  - Prayer Requests: `GET/POST/PUT/DELETE /api/pastoral-care/prayer-requests`
  - Pray action: `POST /api/pastoral-care/prayer-requests/{id}/pray`
  - Visits: `GET/POST/PUT/DELETE /api/pastoral-care/visits`
  - Visit status: `POST /api/pastoral-care/visits/{id}/start` and `/complete`

- Events
  - Events: `GET/POST/PUT/DELETE /api/events`
  - Occurrences: `GET /api/events/{eventId}/occurrences`, `GET/PUT/DELETE /api/occurrences/{id}`
  - Attendance: `GET/POST /api/occurrences/{occurrenceId}/attendance`, `PUT/DELETE /api/attendance/{id}`

See module docs for complete details and usage examples.


## Docs

- Requirements overview: `docs/REQUIREMENTS.md`
- Pastoral Care module: `docs/PASTORAL_CARE.md`
- Events module: `docs/EVENTS.md`


## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m "Add some amazing feature"`
4. Push the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

License: MIT
