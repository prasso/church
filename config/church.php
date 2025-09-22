<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Church Management Settings
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration for the church management
    | system. You can customize various aspects of the system here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used for all database tables created by the
    | church management system.
    |
    */
    'table_prefix' => 'chm_',

    /*
    |--------------------------------------------------------------------------
    | Member Settings
    |--------------------------------------------------------------------------
    |
    | Configure various settings related to church members.
    |
    */
    'members' => [
        'allowed_membership_statuses' => [
            'visitor' => 'Visitor',
            'regular_attendee' => 'Regular Attendee',
            'member' => 'Member',
            'inactive' => 'Inactive',
            'removed' => 'Removed',
        ],
        'default_membership_status' => 'visitor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific features of the church management system.
    |
    */
    'features' => [
        'attendance_tracking' => true,
        'donation_management' => true,
        'event_management' => true,
        'volunteer_management' => true,
        'prayer_request_system' => true,
        'enable_pastoral_care' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pastoral Care Settings
    |--------------------------------------------------------------------------
    |
    | Configure settings for the Pastoral Care module.
    |
    */
    'pastoral_care' => [
        // Default status for new prayer requests
        'default_prayer_request_status' => 'active',
        
        // Default visibility for new prayer requests
        'default_prayer_request_visibility' => 'public',
        
        // Default status for new pastoral visits
        'default_visit_status' => 'scheduled',
        
        // Default duration for pastoral visits in minutes
        'default_visit_duration' => 60,
        
        // Allow members to request pastoral visits
        'allow_visit_requests' => true,
        
        // Require approval for visit requests
        'require_visit_approval' => true,
        
        // Notification settings
        'notifications' => [
            // Email notifications
            'email' => [
                'on_visit_scheduled' => true,
                'on_visit_started' => true,
                'on_visit_completed' => true,
                'on_prayer_request' => true,
                'on_prayer_answered' => true,
            ],
            
            // SMS notifications
            'sms' => [
                'on_visit_reminder' => true,
                'on_visit_confirmation' => true,
                'on_visit_assigned' => true,
            ],
            
            // Push notifications
            'push' => [
                'on_visit_reminder' => true,
                'on_visit_assigned' => true,
                'on_prayer_request' => true,
            ],
        ],
        
        // Reminder settings for pastoral visits
        'reminders' => [
            'enabled' => true,
            'lead_time' => [
                'value' => 24,
                'unit' => 'hours', // hours, days, weeks
            ],
            'method' => 'email', // email, sms, both
        ],
        
        // Follow-up settings
        'follow_up' => [
            'enabled' => true,
            'after_days' => 7,
            'method' => 'email', // email, sms, both
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Visitor Tracking
    |--------------------------------------------------------------------------
    |
    | Configure visitor tracking settings.
    |
    */
    'visitor_tracking' => [
        'auto_convert_to_member_after_visits' => 3,
        'follow_up_days' => 7,
        'follow_up_team_email' => 'visitors@example.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    |
    | Configure email settings for the church management system.
    |
    */
    'email' => [
        'from_address' => env('CHURCH_FROM_EMAIL', 'noreply@example.com'),
        'from_name' => env('CHURCH_FROM_NAME', 'Church Management System'),
        'reply_to' => env('CHURCH_REPLY_TO', 'info@example.com'),
    ],
];
