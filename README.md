# Prasso Church Management

A comprehensive Laravel package for church management that handles member care, events, finances, and ministry operations. This package provides the core backend functionality for managing all aspects of church life and integrates with other Prasso packages for a complete solution.

## Current Features

- **Member Management**: Complete member database with profiles, family relationships, and visitor tracking
- **Event Management**: Service planning, recurring events, attendance tracking, and resource booking
- **Financial Management**: Donation tracking, pledge management, and financial reporting
- **Ministry & Groups**: Small group management, volunteer coordination with skills matching
- **Pastoral Care**: Prayer requests, pastoral visits, and care coordination
- **Communication**: Multi-channel messaging integration (via Prasso_Messaging)
- **Reporting & Analytics**: Member engagement, growth analytics, and custom reports
- **Attendance Tracking**: Service attendance, trends analysis, and follow-up workflows

For a complete overview of capabilities and ecosystem architecture, see `docs/REQUIREMENTS.md`.


## Who this is for

- Church staff and pastors coordinating care, follow-ups, and visits
- Ministry leaders scheduling events and tracking attendance
- Volunteers helping with check-ins and follow-ups
- Administrators managing privacy, permissions, and integrations
- Finance teams tracking donations and pledges
- Small group leaders managing membership and activities


## What you can do

### Core Member Management
Comprehensive tools for managing church members and relationships.

- **Member Directory**: Searchable database with photos, contact info, family relationships
- **Family Management**: Link family members, track households, family-level communications
- **Visitor Tracking**: Capture visitor info, follow-up workflows, conversion tracking
- **Member Lifecycle**: Track visitor → attendee → member → leader progression
- **Custom Fields**: Track church-specific data points and member attributes

### Event & Service Management
Complete event planning and attendance tracking.

- **Service Planning**: Schedule regular services and special events
- **Recurring Events**: Daily/weekly/monthly/yearly recurrence patterns
- **Event Registration**: RSVPs, capacity limits, meal planning
- **Attendance Tracking**: Check-in/out, real-time attendance recording
- **Resource Booking**: Room and equipment reservations with conflict detection
- **Volunteer Coordination**: Assign volunteers to events and track participation

### Financial Management
Comprehensive giving and pledge tracking.

- **Donation Recording**: Record and categorize tithes, offerings, special funds
- **Giving History**: Complete member giving records with search and filtering
- **Pledge Management**: Track commitments with progress monitoring
- **Financial Reporting**: Tax statements, giving summaries, trend analysis
- **Online Giving**: Integration ready for Prasso_API payment processing

### Ministry & Group Management
Organize ministries and coordinate volunteers.

- **Small Groups**: Create and manage study groups, cells, ministry teams
- **Group Membership**: Assign members with roles and track participation
- **Volunteer Management**: Comprehensive system with skills and availability matching
- **Position Management**: Define volunteer positions with skill requirements
- **Volunteer Assignment**: Schedule volunteers with automated matching
- **Hours Tracking**: Record and report volunteer hours by position

### Pastoral Care & Discipleship
Support systems for member care and spiritual growth.

- **Prayer Requests**: Submit and manage requests with confidentiality levels
- **Prayer Tracking**: Count prayers, track status (active/answered/inactive)
- **Pastoral Visits**: Schedule and document visits with outcomes and follow-ups
- **Care Coordination**: Assign care teams, meal trains, support networks
- **Visit Reminders**: Automated notifications and follow-up scheduling
- **Spiritual Milestones**: Track baptisms, membership classes, growth markers

### Communication & Engagement
Multi-channel messaging and automation (via Prasso_Messaging).

- **Message Templates**: Pre-built templates for common church communications
- **Automated Workflows**: Birthday reminders, visitor follow-up sequences
- **Multi-Channel Delivery**: Email, SMS, phone, and in-app messaging
- **Communication History**: Track delivery, open rates, and responses
- **Member Preferences**: Configurable communication preferences

### Reporting & Analytics
Data-driven insights for church leadership.

- **Member Engagement**: Participation levels and at-risk member identification
- **Growth Analytics**: Membership trends, demographics, outreach opportunities
- **Attendance Reports**: Service attendance, trends, and follow-up recommendations
- **Financial Dashboards**: Real-time giving analysis and budget performance
- **Volunteer Reports**: Hours by position, top volunteers, engagement metrics
- **Custom Reports**: Drag-and-drop report builder with export capabilities

### Administration & Settings
System management and configuration.

- **User Management**: Role-based access control with granular permissions
- **System Settings**: Global configuration and feature toggles
- **Integration Settings**: API connections, webhooks, external services
- **Audit Logging**: Complete activity logs for security and compliance
- **Data Import/Export**: Bulk operations with validation and mapping
- **Notification Settings**: Email/SMS configuration and delivery preferences


## Requirements overview

The Prasso Church Management package is part of a modular ecosystem designed to provide comprehensive church management functionality:

### Prasso_Church Package (This Package)
- **Core Data Management**: Member profiles, family relationships, visitor tracking, attendance records
- **Event Management**: Service planning, event scheduling, attendance tracking
- **Ministry Operations**: Group management, volunteer coordination, pastoral care workflows
- **Reporting & Analytics**: Member engagement reports, growth analytics, financial dashboards
- **Backend API**: RESTful endpoints for all core operations, event-driven architecture

### Prasso_API Package
- **Online Giving Platform**: Secure payment processing, recurring donations, multiple payment methods
- **Newsletter & Bulletin Management**: Content creation, distribution, digital/print communications
- **Mobile App Integration**: Native and web mobile access, push notifications
- **Website Integration**: Public APIs for member portals, event registration
- **Frontend Components**: User interface components and JavaScript functionality

### Prasso_Messaging Package
- **Multi-Channel Communication**: Email, SMS, phone, and in-app messaging transport
- **Message Templates**: Church-specific templates for welcome messages, event reminders, pastoral follow-ups
- **Automated Communications**: Birthday/anniversary reminders, visitor follow-up sequences, missed service outreach
- **Communication Workflows**: Automated messaging sequences and triggers

### Laravel Framework (Base)
- **Authentication & Authorization**: User management, role-based access control
- **Database Management**: Migrations, models, relationships, and query builders
- **API Infrastructure**: Routing, middleware, request validation
- **Security Features**: CSRF protection, encryption, secure headers
- **Testing Framework**: Unit and feature testing capabilities

This modular approach allows churches to deploy a complete church management solution by combining these packages. The Prasso_Church package provides the core business logic and data management, while Prasso_API handles user-facing features and Prasso_Messaging manages communications.

The package is production-ready for churches that use the Prasso ecosystem and provides an excellent foundation for building complete church management solutions. The modular design allows for seamless integration with Prasso_API and Prasso_Messaging to deliver a complete feature set.

For detailed requirements and capabilities, see `docs/REQUIREMENTS.md`.


## Getting started (Laravel)

1) Install the package via Composer in your Laravel app:

```bash
composer require prasso/church
```

2) Configure environment (optional defaults shown):

```env
# Package settings
PRASSO_CHURCH_TABLE_PREFIX=chm_

# Feature toggles
ATTENDANCE_TRACKING_ENABLED=true
DONATION_MANAGEMENT_ENABLED=true
EVENT_MANAGEMENT_ENABLED=true
VOLUNTEER_MANAGEMENT_ENABLED=true
PRAYER_REQUEST_SYSTEM_ENABLED=true
ENABLE_PASTORAL_CARE=true

# Member settings
DEFAULT_MEMBERSHIP_STATUS=visitor

# Pastoral care defaults
DEFAULT_PRAYER_REQUEST_STATUS=active
DEFAULT_PRAYER_REQUEST_VISIBILITY=public
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

# Email settings
CHURCH_FROM_EMAIL=noreply@example.com
CHURCH_FROM_NAME="Church Management System"
CHURCH_REPLY_TO=info@example.com
```

3) Run migrations and set up queues/notifications as needed:

```bash
php artisan migrate
```

Laravel Horizon is recommended for queue management at scale.


## Permissions (examples)

### Member Management
- Members: `view`, `create`, `update`, `delete`, `view_private_profiles`
- Families: `view`, `create`, `update`, `delete`, `manage_households`
- Visitors: `view`, `create`, `update`, `delete`, `convert_to_member`

### Events & Attendance
- Events: `view`, `create`, `update`, `delete`, `manage_occurrences`
- Attendance: `view`, `record`, `update`, `delete`, `check_in`, `check_out`

### Financial Management
- Transactions: `view`, `create`, `update`, `delete`, `view_giving_history`
- Pledges: `view`, `create`, `update`, `delete`, `record_payments`

### Ministry & Groups
- Groups: `view`, `create`, `update`, `delete`, `manage_membership`
- Volunteer Positions: `view`, `create`, `update`, `delete`, `assign_volunteers`
- Skills: `view`, `create`, `update`, `delete`, `manage_member_skills`

### Pastoral Care
- Prayer Requests: `view`, `create`, `update`, `delete`, `pray_for_requests`, `view_private_requests`
- Pastoral Visits: `view`, `create`, `update`, `delete`, `start`, `complete`, `view_confidential_visits`

### Communication
- Messages: `view`, `create`, `update`, `delete`, `send_to_groups`
- Templates: `view`, `create`, `update`, `delete`, `manage_templates`

### Reports & Analytics
- Reports: `view`, `create`, `update`, `delete`, `view_all_reports`
- Analytics: `view_member_analytics`, `view_financial_analytics`, `view_engagement_metrics`

### Administration
- Users: `view`, `create`, `update`, `delete`, `manage_roles`
- System Settings: `view`, `update`, `manage_integrations`
- Audit Logs: `view`, `export`, `manage_retention`

Assign permissions to roles (e.g., Admin, Pastor, Staff, Volunteer, Member) using your Laravel auth/permission setup.


## Security & privacy

- **API Security**: All endpoints are secured with Laravel Sanctum authentication
- **Role-Based Access**: Granular permissions ensure users only see appropriate data
- **Data Encryption**: Sensitive fields are encrypted at rest
- **Audit Logging**: Comprehensive activity logging for compliance
- **Privacy Controls**: Configurable privacy settings for member data
- **GDPR Compliance**: Built-in privacy features and data export capabilities

Partner with church leadership to define policies for confidential information and data retention.


## API Overview

The package provides comprehensive RESTful APIs across all modules:

### Member Management
- Members: `GET/POST/PUT/DELETE /api/members`
- Families: `GET/POST/PUT/DELETE /api/families`
- Visitors: `GET/POST/PUT/DELETE /api/visitors`
- Member Skills: `GET/POST/PUT/DELETE /api/members/{id}/skills`

### Event Management
- Events: `GET/POST/PUT/DELETE /api/events`
- Occurrences: `GET/POST/PUT/DELETE /api/events/{id}/occurrences`
- Attendance: `GET/POST/PUT/DELETE /api/occurrences/{id}/attendance`

### Financial Management
- Transactions: `GET/POST/PUT/DELETE /api/financial/transactions`
- Pledges: `GET/POST/PUT/DELETE /api/financial/pledges`
- Giving History: `GET /api/financial/history`
- Financial Reports: `GET /api/financial/reports`

### Ministry & Groups
- Groups: `GET/POST/PUT/DELETE /api/groups`
- Group Members: `GET/POST/PUT/DELETE /api/groups/{id}/members`
- Volunteer Positions: `GET/POST/PUT/DELETE /api/volunteers/positions`
- Volunteer Assignments: `GET/POST/PUT/DELETE /api/volunteers/assignments`
- Skills: `GET/POST/PUT/DELETE /api/volunteers/skills`

### Pastoral Care
- Prayer Requests: `GET/POST/PUT/DELETE /api/pastoral-care/prayer-requests`
- Prayer Actions: `POST /api/pastoral-care/prayer-requests/{id}/pray`
- Visits: `GET/POST/PUT/DELETE /api/pastoral-care/visits`
- Visit Status: `POST /api/pastoral-care/visits/{id}/start`, `/complete`

### Communication
- Messages: `GET/POST/PUT/DELETE /api/communication/messages`
- Templates: `GET/POST/PUT/DELETE /api/communication/templates`
- Communication History: `GET /api/communication/history`

### Reports & Analytics
- Reports: `GET/POST/PUT/DELETE /api/reports`
- Analytics: `GET /api/analytics/members`, `/financial`, `/engagement`
- Custom Reports: `POST /api/reports/custom`

### Administration
- Users: `GET/POST/PUT/DELETE /api/admin/users`
- Settings: `GET/PUT /api/admin/settings`
- Audit Logs: `GET /api/admin/audit-logs`
- Integrations: `GET/POST/PUT/DELETE /api/admin/integrations`

All endpoints support standard RESTful operations with filtering, sorting, and pagination. See module-specific documentation for complete details and usage examples.


## Documentation

- **Requirements Overview**: `docs/REQUIREMENTS.md` - Complete feature set and ecosystem architecture
- **Pastoral Care Module**: `docs/PASTORAL_CARE.md` - Prayer requests and pastoral visits
- **Events Module**: `docs/EVENTS.md` - Event planning and attendance tracking

For API documentation and usage examples, see the inline code comments and test files.


## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m "Add some amazing feature"`
4. Push the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

Please ensure all tests pass and follow the existing code style and documentation patterns.

## License

MIT
