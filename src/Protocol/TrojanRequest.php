<?php

namespace xywf221\Trojan\Protocol;

use xywf221\Trojan\Common\Buffer;
use xywf221\Trojan\Core\Constant;
use xywf221\Trojan\Exception\ParseRequestException;

class TrojanRequest
{
    /**
     * @param string $password
     * @param TrojanCommand $command
     * @param Socks5Address $addr
     * @param int $length
     */
    public function __construct(public string $password, public TrojanCommand $command, public Socks5Address $addr, public int $length)
    {
    }

    /**
     * @throws ParseRequestException
     */
    static function Parse(Buffer $buffer): static
    {
        if ($buffer->isEmpty()) {
            throw new ParseRequestException("buffer empty");
        }
        // 寻找密码
        $pos = strpos($buffer, Constant::CRLF);
        if ($pos === false) {
            throw new ParseRequestException("find password failed : not find CRLF");
        }
        $offset = $pos + 2;

        // 密码后面需要包含包体
        if ($buffer->getLength() <= $offset) {
            throw new ParseRequestException("payload need more data");
        }
        $commandU8 = $buffer->readUInt8($offset);
        $command = TrojanCommand::tryFrom($commandU8);
        if ($command == null) {
            throw new ParseRequestException("unknown trojan command: $commandU8");
        }
        // parser address
        $address = Socks5Address::Parse($buffer, $offset + 1);
        $payloadLength = $buffer->getLength() - $offset;
        // 检测内容必须得 >= 地址长度 + CRLF
        if ($payloadLength < $address->len + 3 || $buffer->read($offset + $address->len + 1, 2) !== Constant::CRLF) {
            throw new ParseRequestException("bad packet");
        }

        return new self(
            $buffer->read(0, $pos),
            $command,
            $address,
            $offset + $address->len + 3
        );
    }
}