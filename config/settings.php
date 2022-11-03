<?php

// Timezone
date_default_timezone_set('Pacific/Auckland');

return [
    'production' => false,
    'db' => [
        'GRAVITYDB' => '/etc/pihole/gravity.db',
        'FTLDB'     => '/etc/pihole/pihole-FTL.db',
        'USERDB'    => '/etc/pihole/users.db',
    ]
];

