<?php

// Timezone
date_default_timezone_set('Pacific/Auckland');

$settings = [
    'production' => true,
    'db'         => [
        'GRAVITY' => '/etc/pihole/gravity.db',
        'FTL'     => '/etc/pihole/pihole-FTL.db',
        'USER'    => '/etc/pihole/users.db',
    ],
    'conf'       => [
        'PIHOLE_CONF' => '/etc/pihole/setupVars.conf',
        'FTL_CONF'    => '/etc/pihole/pihole-FTL.conf',
    ],
    'dns'        => [
        'DNSLIST_CONF'        => '/etc/pihole/custom.list',
        'CNAMELIST_CONF'      => '/etc/dnsmasq.d/05-pihole-custom-cname.conf',
        'STATIC_LEASES_CONF'  => '/etc/dnsmasq.d/04-pihole-static-dhcp.conf',
        'DYNAMIC_LEASES_CONF' => '/etc/pihole/dhcp.leases',
        'SERVERS_CONF'        => '/etc/pihole/dns-servers.conf',
    ],

    'pihole' => [],
    'ftl'    => []
];

if (file_exists(__DIR__ . '/settings.local.php')) {
    $local = (require __DIR__ . '/settings.local.php');
    $settings = array_merge($local, $settings);
}

if (file_exists($settings['conf']['PIHOLE_CONF'])) {
    $settings['pihole'] = parse_ini_file($settings['conf']['PIHOLE_CONF']);
}

if (file_exists($settings['conf']['FTL_CONF'])) {
    $settings['ftl'] = parse_ini_file($settings['conf']['FTL_CONF']);
}

return $settings;