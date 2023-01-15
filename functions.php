<?php

use Swow\Socket;
use xywf221\Trojan\Common\Buffer;

function swow_socket_copy(Socket $src, Socket $dst): int
{
    $traffic = 0;
    $buffer = new Buffer(32 * 1024);
    try {
        while (true) {
            $recvLength = $src->recvData($buffer);
            $dst->send($buffer);
            $traffic += $recvLength;
            $buffer->clear();
        }
    } catch (\Exception $exception) {
        // ignore exception
    }
    return $traffic;
}

function is_ipv6(string $ipaddr): bool
{
    return str_contains($ipaddr, ':');
}