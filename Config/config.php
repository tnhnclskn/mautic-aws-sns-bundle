<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Mtalkz SMS',
    'description' => 'Mtalkz SMS integration',
    'author'      => 'gaurav.agrawal@mtalkz.com',
    'version'     => '1.1.6',
    'services' => [
        'events'  => [],
        'forms'   => [
        ],
        'helpers' => [],
        'other'   => [
            'mautic.sms.transport.mtalkz' => [
                'class'     => \MauticPlugin\MauticMtalkzBundle\Transport\MtalkzTransport::class,
                'arguments' => [
                    'mautic.helper.integration',
                    'monolog.logger.mautic',
                ],
                'alias'        => 'mautic.sms.config.mtalkz.transport',
                'tag'          => 'mautic.sms_transport',
                'tagArguments' => [
                    'integrationAlias' => 'Mtalkz',
                ],
            ],
        ],
        'models'       => [],
        'integrations' => [
            'mautic.integration.mtalkz' => [
                'class' => \MauticPlugin\MauticMtalkzBundle\Integration\MtalkzIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
    'routes'     => [],
    'menu'       => [
        'main' => [
            'items' => [
                'mautic.sms.smses' => [
                    'route'    => 'mautic_sms_index',
                    'access'   => ['sms:smses:viewown', 'sms:smses:viewother'],
                    'parent'   => 'mautic.core.channels',
                    'checks'   => [
                        'integration' => [
                            'Mtalkz' => [
                                'enabled' => true,
                            ],
                        ],
                    ],
                    'priority' => 70,
                ],
            ],
        ],
    ],
    'parameters' => [],
];
