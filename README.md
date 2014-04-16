mail
====
Parameters when using the event listener.
```php
[
    'to' => [
        'email' => 'you@domain.com',
        'name' => 'You'
    ],
    'cc' => [
        [
            'email' => 'me@domain.com',
            'name' => 'Me'
        ]
    ],
    'bcc' => [
        [
            'email' => 'me@domain.com',
            'name' => 'Me'
        ]
    ],
    'from' => [
        'email' => 'me@domain.com',
        'name' => 'Me'
    ],
    'subject' => 'Application rejected',
    'body' => [
        'html' => '<h3>Your application has been rejected.</h3>',
        'text' => 'Your application has been rejected.'
    ],
    'attachments' => [
        'path/to/my/file.file'
    ]
]
```
