<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Log\Handlers\FileHandler;
use CodeIgniter\Log\Handlers\HandlerInterface;

class Logger extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Error Logging Threshold
     * --------------------------------------------------------------------------
     *
     * You can enable error logging by setting a threshold over zero. The
     * threshold determines what gets logged. Any values below or equal to the
     * threshold will be logged.
     *
     * Threshold options:
     *
     * - 0 = Disables logging
     * - 1 = Emergency
     * - 2 = Alert
     * - 3 = Critical
     * - 4 = Error
     * - 5 = Warning
     * - 6 = Notice
     * - 7 = Info
     * - 8 = Debug
     * - 9 = All Messages
     *
     * @var int|list<int>
     */
    public $threshold = (ENVIRONMENT === 'production') ? 7 : 9;

    /**
     * Date Format for Logs
     */
    public string $dateFormat = 'Y-m-d H:i:s';

    /**
     * Log Handlers
     */
    public array $handlers = [
        FileHandler::class => [
            'handles' => [
                'critical',
                'alert',
                'emergency',
                'debug',
                'error',
                'info',
                'notice',
                'warning',
            ],

            'fileExtension' => '',

            'filePermissions' => 0644,

            'path' => '',
        ],

        /*
         * ChromeLoggerHandler
         */
        // 'CodeIgniter\Log\Handlers\ChromeLoggerHandler' => [
        //     'handles' => [
        //         'critical',
        //         'alert',
        //         'emergency',
        //         'debug',
        //         'error',
        //         'info',
        //         'notice',
        //         'warning'
        //     ],
        // ],

        /*
         * ErrorlogHandler
         */
        // 'CodeIgniter\Log\Handlers\ErrorlogHandler' => [
        //     'handles' => [
        //         'critical',
        //         'alert',
        //         'emergency',
        //         'debug',
        //         'error',
        //         'info',
        //         'notice',
        //         'warning'
        //     ],
        //
        //     'messageType' => 0,
        // ],
    ];
}