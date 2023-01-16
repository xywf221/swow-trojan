<?php

namespace xywf221\Trojan\Protocol;

use Swow\Buffer;
use Swow\Socket;

class UdpWrapSocket extends Socket
{
    public function __construct(private Socket $wrap, private string $addr, private int $port)
    {
    }

    public function send(\Stringable|string $data, int $start = 0, int $length = -1, ?int $timeout = null): static
    {
        $this->wrap->send(UdpPacket::Generate($this->addr, $this->port, $data), $start, $length, $timeout);
        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        call_user_func_array($this->wrap->$name, $arguments);
    }


}