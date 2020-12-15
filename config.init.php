<?php

return [
    'botName' => 'XXX',
    'logLevel' => 'debug',
    'redis' => [
        'host' => 'xxx',
    ],
    'amqp' => [
        'host' => 'xxx',
    ],
    // for tests only
    'testEmailPwd' => getenv('TEST_EMAIL_PWD') ?: 'XXX',
];
