<?php

namespace xywf221\Trojan\Protocol;

use Swow\Pack\Format;
use Swow\Socket;
use xywf221\Trojan\Common\Buffer;
use xywf221\Trojan\Core\Constant;
use xywf221\Trojan\Exception\ParseUdpPacketException;

class UdpPacket
{

    public function __construct(public Socks5Address $addr, public int $length)
    {
    }

    /**
     * @throws ParseUdpPacketException
     */
    static function Parse(Buffer $buffer, $offset): static|null
    {
        //+------+----------+----------+--------+---------+----------+
        //| ATYP | DST.ADDR | DST.PORT | Length |  CRLF   | Payload  |
        //+------+----------+----------+--------+---------+----------+
        //|  1   | Variable |    2     |   2    | X'0D0A' | Variable |
        //+------+----------+----------+--------+---------+----------+
        if ($buffer->getLength() == $offset) {
            throw new ParseUdpPacketException("buffer is empty");
        }
        $addr = Socks5Address::Parse($buffer, $offset);
        if ($buffer->getLength() < $offset + $addr->len + 2) {
            throw new ParseUdpPacketException("need more data");
        }

        $length = $buffer->readUInt16BE($offset + $addr->len);

        // 4 = length + CRLF
        if ($buffer->getLength() < $offset + $addr->len + 4 + $length || $buffer->read($offset + $addr->len + 2, 2) !== "\r\n") {
            throw new ParseUdpPacketException("bad packet");
        }

        return new self($addr, $offset + $addr->len + 4);
    }

    static function Generate(string $addr, int $port, \Stringable|string|Buffer $payload): Buffer
    {
        $buffer = Socks5Address::Generate($addr, $port);
        if ($payload instanceof \Swow\Buffer) {
            $buffer->append(pack(Format::UINT16_BE, $payload->getLength()));
        } else {
            $buffer->append(pack(Format::UINT16_BE, strlen($payload)));
        }
        $buffer->append(Constant::CRLF);
        $buffer->append($payload);
        return $buffer;
    }
}