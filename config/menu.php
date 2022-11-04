<?php

$menu = [
    'Main'             => [
        [
            'Header' => 'Main',
            'Title'  => 'Dashboard',
            'Icon'   => 'fa-home',
            'URL'    => '/'
        ]
    ],
    'Analysis'         => [
        [
            'Header' => 'Analysis',
            'Title'  => 'Queries',
            'URL'    => '/queries',
            'Icon'   => 'fa-file-alt',
        ],
        'Longterm' => [
            'Title'    => 'Long-term Data',
            'Icon'     => 'fa-history',
            'URL'      => '#',
            'Children' => [
                'Graphics' => [
                    'Title' => 'Graphics',
                    'URL'   => 'longterm/graph',
                    'Icon'  => 'fa-chart-bar'
                ],
                'QueryLog' => [
                    'Title' => 'Query Log',
                    'Icon'  => 'fa-file-alt',
                    'URL'   => 'longterm/queries',
                ],
                'TopList'  => [
                    'Title' => 'Top Lists',
                    'Icon'  => 'fa-list',
                    'URL'   => 'longterm/lists',
                ],
            ],
        ],
    ],
    'Group Management' => [
        [
            'Title' => 'Groups',
            'URL'   => 'groups',
            'Icon'  => 'fa-user-friends',
        ],
        [
            'Title' => 'Clients',
            'URL'   => 'groups/clients',
            'Icon'  => 'fa-laptop',
        ],
        [
            'Title' => 'Domains',
            'URL'   => 'groups/domains',
            'Icon'  => 'fa-list',
        ],
        [
            'Title' => 'Adlists',
            'URL'   => 'groups/adlists',
            'Icon'  => 'fa-shield-alt',
        ],
    ],
    'DNS Control'      => [
        [
            'Title'    => 'Disable Blocking',
            'URL'      => '#',
            'Icon'     => 'fa-stop',
            'StatusLT' => true,
            'ID'       => 'pihole-disable',
            'Children' => [
                'Indefinitely' => [
                    'URL'   => '#',
                    'ID'    => 'pihole-disable-indefinitely',
                    'Title' => 'Indefinitely',
                    'Icon'  => 'fa-infinity',
                ],
                '10s'          => [
                    'URL'   => '#',
                    'ID'    => 'pihole-disable-10s',
                    'Icon'  => 'fa-clock',
                    'Title' => 'For 10 seconds',
                ],
                '30s'          => [
                    'URL'   => '#',
                    'ID'    => 'pihole-disable-30s',
                    'Icon'  => 'fa-clock',
                    'Title' => 'For 30 seconds',
                ],
                '5m'           => [
                    'URL'   => '#',
                    'ID'    => 'pihole-disable-5m',
                    'Icon'  => 'fa-clock',
                    'Title' => 'For 5 minutes',
                ],
                'custom'       => [
                    'URL'         => '#',
                    'ID'          => 'pihole-disable-cst',
                    'Title'       => 'Custom time',
                    'Modal'       => 'modal',
                    'TargetModal' => 'customDisableModal',
                    'Icon'        => 'fa-user-clock'
                ],
            ]
        ],
        'Enable blocking' => [
            'ID'        => 'pihole-enable',
            'URL'       => '#',
            'Title'     => 'Enable blocking',
            'Icon'      => 'fa-play',
            'StatusGT'  => true,
            'SpanLabel' => 'enableLabel',
            'ExtraSpan' => 'flip-status-enable'
        ],
        'DNS'             => [
            'URL'      => '#',
            'Icon'     => 'fa-address-book',
            'Title'    => 'Local DNS',
            'Children' => [
                'DNSRecords'   => [
                    'URL'   => 'dns/dns',
                    'Title' => 'DNS Records',
                    'Icon'  => 'fa-address-card',
                ],
                'CNAMERecords' => [
                    'URL'   => 'dns/cname',
                    'Title' => 'CNAME Records',
                    'Icon'  => 'fa-location-arrow',
                ],
            ]
        ]
    ],
    'System'           => [
        'Tools'    => [
            'URL'        => '#',
            'Title'      => 'Tools',
            'Icon'       => 'fa-tools',
            'ExtraSpan'  => 'warning',
            'ExtraClass' => 'warning-count hidden',
            'Children'   => [
                'Messages' => [
                    'ExtraSpan'  => 'warning',
                    'ExtraClass' => 'pull-right-container warning-count hidden',
                    'Title'      => 'Pi-hole diagnosis',
                    'URL'        => 'tools/messages',
                    'Icon'       => 'fa-file-medical-alt'
                ],
                'Gravity'  => [
                    'Title' => 'Update Gravity',
                    'URL'   => 'tools/gravity',
                    'Icon'  => 'fa-arrow-circle-down',
                ],
                'Adlists'  => [
                    'Title' => 'Search Adlists',
                    'URL'   => 'tools/search',
                    'Icon'  => 'fa-search',
                ],
                'Audit'    => [
                    'Title' => 'Audit log',
                    'URL'   => 'tools/auditlog',
                    'Icon'  => 'fa-balance-scale',
                ],
                'Taillog'  => [
                    'Title'      => 'Tail pihole.log',
                    'URL'        => 'tools/taillog',
                    'Icon'       => '',
                    'InlineIcon' => '<svg class="svg-inline--fa fa-fw menu-icon" style="height: 1.25em">
                                <use xlink:href="img/pihole_icon.svg#pihole-svg-logo"/>
                            </svg>',
                ],
                'FTLLog'   => [
                    'Title'      => 'Tail FTL.log',
                    'URL'        => 'tools/taillog?FTL',
                    'Icon'       => '',
                    'InlineIcon' => '<svg class="svg-inline--fa fa-fw menu-icon" style="height: 1.25em">
                                <use xlink:href="img/pihole_icon.svg#pihole-svg-logo"/>
                            </svg>',
                ],
                'Debug'    => [
                    'Title' => 'Generate debug log',
                    'URL'   => 'tools/debug',
                    'Icon'  => 'fa-ambulance',
                ],
                'Network'  => [
                    'Title' => 'Network',
                    'URL'   => 'tools/network',
                    'Icon'  => 'fa-network-wired',
                ]
            ]
        ],
        'Settings' => [
            'URL'   => 'settings',
            'Icon'  => 'fa-cog',
            'Title' => 'Settings',
        ],
    ],
];

return $menu;