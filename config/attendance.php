<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the attendance module.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // Default status for new attendance records
        'status' => 'present',
        
        // Whether to require check-in for attendance
        'require_check_in' => true,
        
        // Default time window for being marked as 'on time' (in minutes)
        'on_time_threshold' => 15,
        
        // Default time window for being marked as 'tardy' (in minutes)
        'tardy_threshold' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance Statuses
    |--------------------------------------------------------------------------
    |
    | Define the available attendance statuses and their display names.
    |
    */
    'statuses' => [
        'present' => [
            'name' => 'Present',
            'description' => 'Attended the event',
            'counts_as_present' => true,
            'color' => 'green',
        ],
        'late' => [
            'name' => 'Late',
            'description' => 'Arrived after the event started',
            'counts_as_present' => true,
            'color' => 'orange',
        ],
        'excused' => [
            'name' => 'Excused',
            'description' => 'Absent with a valid excuse',
            'counts_as_present' => false,
            'color' => 'blue',
        ],
        'absent' => [
            'name' => 'Absent',
            'description' => 'Did not attend without an excuse',
            'counts_as_present' => false,
            'color' => 'red',
        ],
        'tardy' => [
            'name' => 'Tardy',
            'description' => 'Arrived significantly late',
            'counts_as_present' => true,
            'color' => 'yellow',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Recurring Events
    |--------------------------------------------------------------------------
    |
    | Configuration for recurring attendance events.
    |
    */
    'recurring' => [
        // Maximum number of future events to generate
        'max_future_events' => 52, // 1 year of weekly events
        
        // Default recurrence patterns
        'patterns' => [
            'daily' => [
                'name' => 'Daily',
                'description' => 'Occurs every day',
                'interval' => 1,
                'unit' => 'day',
            ],
            'weekly' => [
                'name' => 'Weekly',
                'description' => 'Occurs every week',
                'interval' => 1,
                'unit' => 'week',
            ],
            'biweekly' => [
                'name' => 'Bi-weekly',
                'description' => 'Occurs every 2 weeks',
                'interval' => 2,
                'unit' => 'week',
            ],
            'monthly' => [
                'name' => 'Monthly',
                'description' => 'Occurs once per month',
                'interval' => 1,
                'unit' => 'month',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Check-in Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for attendance check-in functionality.
    |
    */
    'check_in' => [
        // Whether to allow self check-in
        'allow_self_check_in' => true,
        
        // Whether to allow family check-in (checking in multiple family members at once)
        'allow_family_check_in' => true,
        
        // Default check-in window before event start (in minutes)
        'check_in_window_before' => 30,
        
        // Default check-in window after event start (in minutes)
        'check_in_window_after' => 60,
        
        // Whether to require a reason for late check-ins
        'require_reason_for_late_check_in' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configuration for attendance-related notifications.
    |
    */
    'notifications' => [
        // Notify when a member is marked absent
        'notify_on_absent' => true,
        
        // Notify when attendance falls below a threshold
        'attendance_threshold' => [
            'enabled' => true,
            'percentage' => 70, // Notify if attendance drops below this percentage
            'recipients' => ['admin@example.com'],
        ],
        
        // Reminder notifications
        'reminders' => [
            'enabled' => true,
            'lead_time' => 24, // Hours before the event
            'channels' => ['email', 'sms'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting
    |--------------------------------------------------------------------------
    |
    | Configuration for attendance reporting.
    |
    */
    'reporting' => [
        // Default date range for reports
        'default_date_range' => 30, // days
        
        // Available date range options
        'date_ranges' => [
            7 => 'Last 7 days',
            30 => 'Last 30 days',
            90 => 'Last 90 days',
            180 => 'Last 6 months',
            365 => 'Last year',
            'custom' => 'Custom range',
        ],
        
        // Grouping options for reports
        'group_by' => [
            'day' => 'Day',
            'week' => 'Week',
            'month' => 'Month',
            'quarter' => 'Quarter',
            'year' => 'Year',
            'event' => 'Event',
            'ministry' => 'Ministry',
            'group' => 'Group',
            'member' => 'Member',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for third-party integrations.
    |
    */
    'integrations' => [
        // Church Management System integration
        'cms' => [
            'enabled' => true,
            'sync_attendance' => true,
            'sync_direction' => 'both', // 'to_cms', 'from_cms', 'both'
        ],
        
        // Calendar integration
        'calendar' => [
            'enabled' => true,
            'providers' => ['google', 'outlook', 'apple'],
        ],
        
        // Check-in kiosk settings
        'kiosk' => [
            'enabled' => true,
            'timeout' => 60, // seconds of inactivity before returning to home screen
            'allowed_ips' => [], // empty array allows all IPs
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for the attendance module.
    |
    */
    'security' => [
        // IP restrictions for check-in kiosks
        'restrict_check_in_by_ip' => false,
        'allowed_check_in_ips' => [],
        
        // Require authentication for check-in
        'require_authentication' => true,
        
        // Roles that can manage attendance
        'admin_roles' => ['admin', 'pastor', 'elder'],
        
        // Roles that can record attendance
        'attendance_recorder_roles' => ['leader', 'teacher', 'volunteer'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Fields
    |--------------------------------------------------------------------------
    |
    | Define custom fields for attendance records.
    |
    */
    'custom_fields' => [
        'events' => [
            // Example:
            // 'location_notes' => [
            //     'type' => 'text',
            //     'label' => 'Location Notes',
            //     'required' => false,
            //     'validation' => 'string|max:255',
            // ],
        ],
        'records' => [
            // Example:
            // 'check_in_method' => [
            //     'type' => 'select',
            //     'label' => 'Check-in Method',
            //     'options' => ['kiosk', 'mobile', 'manual'],
            //     'required' => true,
            //     'default' => 'manual',
            // ],
        ],
    ],
];
