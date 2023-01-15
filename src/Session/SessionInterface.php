<?php

namespace xywf221\Trojan\Session;

use Swow\Socket;

interface SessionInterface
{
    public function start(Socket $inSocket);
}