<?php

use Monolog\Handler\StreamHandler;
use pahanini\Monolog\Formatter\CliFormatter;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use App\Core\Input;
use App\Core\Output;

return [
    'input' => function() {
        return new Input;
    },
    'output' => function() {
        return new Output;
    },
    'logLevel' => 'info',
    'shared' => [
        LoggerInterface::class,
    ],
    LoggerInterface::class => static function ($c) {
        $stream = new StreamHandler(STDERR, $c->get('logLevel'));
        $stream->setFormatter(new CliFormatter());
        return (new Logger('app'))->pushHandler($stream);
    },

];
