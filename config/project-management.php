<?php

return [
    'priorities' => ['low', 'medium', 'high', 'critical'],
    'task_statuses' => ['todo', 'in_progress', 'in_review', 'done', 'cancelled'],
    'project_statuses' => ['planning', 'active', 'on_hold', 'completed', 'archived'],
    'team_roles' => ['owner', 'admin', 'member', 'viewer'],
    'project_roles' => ['manager', 'developer', 'viewer'],
    'max_upload_size_kb' => 10240,
    'items_per_page' => 15,
    'dashboard_cache_ttl' => 300,
    'default_columns' => [
        ['name' => 'To Do',       'color' => '#94a3b8', 'is_default' => true,  'is_done' => false],
        ['name' => 'In Progress', 'color' => '#6366f1', 'is_default' => false, 'is_done' => false],
        ['name' => 'In Review',   'color' => '#f59e0b', 'is_default' => false, 'is_done' => false],
        ['name' => 'Done',        'color' => '#22c55e', 'is_default' => false, 'is_done' => true],
    ],
];
