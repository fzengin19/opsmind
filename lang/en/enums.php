<?php

declare(strict_types=1);

return [
    'appointment_type' => [
        'meeting' => 'Meeting',
        'call' => 'Call',
        'focus' => 'Focus Time',
        'break' => 'Break',
    ],
    'attendee_status' => [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
    ],
    'contact_type' => [
        'customer' => 'Customer',
        'vendor' => 'Vendor',
        'partner' => 'Partner',
        'lead' => 'Lead',
    ],
    'task_priority' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ],
    'task_status' => [
        'backlog' => 'Backlog',
        'todo' => 'To Do',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'done' => 'Done',
    ],
    'calendar_type' => [
        'default' => 'Default',
        'team' => 'Team',
        'resource' => 'Resource',
        'personal' => 'Personal',
    ],
    'calendar_visibility' => [
        'company_wide' => 'Company Wide',
        'members_only' => 'Members Only',
        'private' => 'Private',
    ],
];
