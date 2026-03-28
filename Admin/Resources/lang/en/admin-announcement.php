<?php

return [
    'title' => 'Announcements',
    'description' => 'Manage site announcements',
    'table' => [
        'content' => 'Content',
        'type' => 'Type',
        'status' => 'Status',
        'actions' => 'Actions',
    ],
    'sections' => [
        'list' => [
            'title' => 'Announcements list',
            'description' => 'Announcements are displayed at the top of the site for all visitors. Drag to reorder.',
        ],
    ],
    'types' => [
        'info' => 'Info',
        'success' => 'Success',
        'warning' => 'Warning',
        'error' => 'Error',
    ],
    'targets' => [
        'all' => 'Everyone',
        'guests' => 'Guests only',
        'auth' => 'Authorized only',
    ],
    'modal' => [
        'create_title' => 'Create announcement',
        'edit_title' => 'Edit announcement',
        'fields' => [
            'content' => [
                'label' => 'Announcement text',
                'placeholder' => 'Enter announcement text...',
                'help' => 'Main text that will be displayed in the announcement',
            ],
            'type' => [
                'label' => 'Announcement type',
                'help' => 'Determines the color scheme of the announcement',
            ],
            'icon' => [
                'label' => 'Icon',
                'placeholder' => 'ph.bold.megaphone-bold',
                'help' => 'Custom icon (defaults to type icon if empty)',
            ],
            'url' => [
                'label' => 'Bar link',
                'placeholder' => '/page or https://...',
                'help' => 'Makes the entire bar clickable (optional)',
            ],
            'target' => [
                'label' => 'Audience',
                'help' => 'Who will see this announcement',
            ],
            'button_text' => [
                'label' => 'Button text',
                'placeholder' => 'Learn more',
                'help' => 'Text for the button (optional)',
            ],
            'button_url' => [
                'label' => 'Button URL',
                'placeholder' => '/page or https://...',
                'help' => 'URL where the button leads',
            ],
            'button_icon' => [
                'label' => 'Button icon',
                'placeholder' => 'ph.bold.arrow-right-bold',
                'help' => 'Icon for the button (optional)',
            ],
            'button_new_tab' => [
                'label' => 'Open in new tab',
                'help' => 'If enabled, the link will open in a new tab',
            ],
            'start_at' => [
                'label' => 'Show from',
                'help' => 'Start date and time (optional)',
            ],
            'end_at' => [
                'label' => 'Show until',
                'help' => 'End date and time (optional)',
            ],
            'closable' => [
                'label' => 'Can be closed',
                'help' => 'Allows the user to close the announcement',
            ],
            'is_active' => [
                'label' => 'Active',
                'help' => 'Whether to show the announcement on the site',
            ],
        ],
    ],
    'confirms' => [
        'delete' => 'Are you sure you want to delete this announcement?',
    ],
    'messages' => [
        'invalid_sort_data' => 'Invalid sorting data.',
        'created' => 'Announcement created successfully.',
        'updated' => 'Announcement updated successfully.',
        'deleted' => 'Announcement deleted successfully.',
        'not_found' => 'Announcement not found.',
    ],
];
