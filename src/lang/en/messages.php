<?php

return [
    'audit' => [
        'fetch_success' => 'Audit logs retrieved successfully.',
        'fetch_failed' => 'Failed to retrieve audit logs.',
    ],
    'event_registry' => [
        'fetch_success' => 'Audit event definitions retrieved successfully.',
    ],
    'coverage' => [
        'fetch_success' => 'Audit event coverage retrieved successfully.',
    ],
    'validation' => [
        'log_type' => 'Select a valid activity log type.',
        'action_max' => 'The action must not exceed 50 characters.',
        'source_type' => 'Select a valid source type.',
        'source_identifier' => 'The source identifier format is invalid.',
        'date_range' => 'The end date must not be earlier than the start date.',
        'per_page_max' => 'The page size must not exceed 100.',
    ],
];
