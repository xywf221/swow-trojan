<?php

namespace xywf221\Trojan\Protocol;

use Swow\Buffer;
use Swow\Socket;

class UdpWrapSocket extends Socket
{
    public function __construct(private Socket $wrap, private string $addr, private int $port)
    {
    }

    public function recvData(Buffer $buffer, int $offset = 0, int $size = -1, ?int $timeout = null): int
    {
        return $this->wrap->recvData($buffer, $offset, $size, $timeout);
    }

    public function send(\Stringable|string $data, int $start = 0, int $length = -1, ?int $timeout = null): static
    {
        $this->wrap->send(UdpPacket::Generate($this->addr, $this->port, $data), $start, $length, $timeout);
        return $this;
    }


}