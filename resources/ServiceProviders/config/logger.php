<?php
/*-----------------------------------------------------------------------------------------------------------
`channel`: The logging channel
`default_level`: Default for handler not assigned level
`max_files`: works on 'daily_files' handler
`handlers`:
   - `handler`: 'files'|'daily_files'|'syslog'|'error_log'|<Class Name>|<Registered Service>
   - `level`:  'debug'|'info'|'notice'|'warning'|'error'|'critical'|'alert'|'emergency'

-----------------------------------------------------------------------------------------------------------*/
return [
    'channel' => env('APP_NAME', 'Sledium'),
    'default_level' => 'debug',
    'max_files' => 5, //
    'handlers' => [
        [
            'handler' => 'files',
            'level' => 'debug',
        ],
    ]
];
