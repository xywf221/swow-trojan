<?php

namespace xywf221\Trojan\Session;

use Psr\Log\LoggerInterface;
use Swow\Socket;

class ClientSession implements SessionInterface
{
    public function __construct(private readonly array $config, private readonly LoggerInterface $logger)
    {
    }

    public function start(Socket $inSocket)
    {

    }
}