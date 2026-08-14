<?php

return [
    'fields' => ['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'source' => 'Source'],
    'mail' => ['subject' => 'New enquiry from :name', 'heading' => 'New enquiry received'],
    'confirmation' => [
        'subject' => 'We received your enquiry at :site',
        'heading' => 'Thank you for contacting us, :name',
        'body' => 'Your enquiry has been recorded. An adviser will review it and contact you.',
        'closing' => 'Regards',
    ],

];
