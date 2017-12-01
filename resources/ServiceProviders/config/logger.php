<?php
/*-----------------------------------------------------------------------------------------------------------
string   `channel`: The logging channel

string   `default_level`:  Default for handler not assigned level

int      `max_files`: works on 'daily_files' handler

[][]    `handlers`:
           - string `handler`: 'files'|'daily_files'|'syslog'|'error_log'|<Class Name>|<Registered Service>
           - string `level`:  'debug'|'info'|'notice'|'warning'|'error'|'critical'|'alert'|'emergency'

string[]|callable[] `processors`:  <callable>|<Registered Service>::__invoke(array $record)
-----------------------------------------------------------------------------------------------------------*/
return [
    'channel' => env('APP_NAME', 'Sledium'),
    'default_level' => 'debug',
    'max_files' => 5,
    'handlers' => [
        [
            'handler' => 'files',
            'level' => 'debug',
        ],
    ],
    'processors' => [
        //''
    ]
];
