<?php

namespace xywf221\Trojan\Protocol;

use Swow\Pack\Format;
use xywf221\Trojan\Common\Buffer;
use xywf221\Trojan\Exception\ParseAddressException;
use xywf221\Trojan\Exception\UnknownAddressTypeException;

class Socks5Address
{

    public function __construct(public string $address, public int $port, public int $len)
    {
    }

    static function Parse(Buffer $buffer, int $offset): static
    {
        $addrTypeU8 = $buffer->readUInt8($offset);
        $addrType = Socks5AddressType::tryFrom($addrTypeU8);
        if ($addrType == null) {
            throw new UnknownAddressTypeException($addrTypeU8);
        }
        // add addr type
        $offset += 1;


        return match ($addrType) {
            Socks5AddressType::IPv4 => self::parseIpv4Addr($buffer, $offset),
            Socks5AddressType::FQDN => self::parseFQDNAddr($buffer, $offset),
            Socks5AddressType::IPv6 => self::parseIpv6Addr($buffer, $offset)
        };
    }

    static function Generate(string $ipaddr, int $port): Buffer
    {
        $buffer = new Buffer(0);
        if (is_ipv6($ipaddr)) {
            $buffer->append("\x04");
            $ipv6Parts = explode(':', $ipaddr);
            for ($i = 0; $i < 8; $i++) {
                // fix bug
                if (strlen($ipv6Parts[$i]) == 3) {
                    //补零
                    $ipv6Parts[$i] = '0' . $ipv6Parts[$i];
                }
                $buffer->append(
                    pack("CC",
                        hexdec(substr($ipv6Parts[$i], 0, 2)),
                        hexdec(substr($ipv6Parts[$i], 2, 2))
                    )
                );
            }
        } else {
            $buffer->append("\x01");
            $ipv4 = explode('.', $ipaddr);
            $buffer->append(pack("C4", ...$ipv4));
        }
        $buffer->append(pack(Format::UINT16_BE, $port));
        return $buffer;
    }


    private static function parseIpv4Addr(Buffer $buffer, $offset): Socks5Address
    {
        if ($buffer->getLength() < $offset + 4 + 2) {
            throw new ParseAddressException('parse Ipv4 address failed : need more data');
        }

        $ip = unpack("C4", $buffer->read($offset, 4));

        // 1 [Command] + 4 [ip] + 2 [port]
        return new self(join(".", $ip), self::readPort($buffer, $offset + 4), 1 + 4 + 2);
    }

    private static function parseIpv6Addr(Buffer $buffer, $offset): Socks5Address
    {
        if ($buffer->getLength() < $offset + 16 + 2) {
            throw new ParseAddressException('read Ipv6 address failed : need more data');
        }

        $data = unpack("C16", $buffer->read($offset, 16));
        $addr = sprintf("%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x:%02x%02x",
            $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9],
            $data[10], $data[11], $data[12], $data[13], $data[14], $data[15], $data[16]);
        return new self($addr, self::readPort($buffer, $offset + 16), 1 + 16 + 2);
    }

    private static function parseFQDNAddr(Buffer $buffer, $offset): Socks5Address
    {
        $domainLen = $buffer->readUInt8($offset);
        if ($domainLen == 0) {
            throw new ParseAddressException('read FQDN address failed : domain length is empty');
        }
        $domain = $buffer->read($offset + 1, $domainLen);
        $port = self::readPort($buffer, $offset + 1 + $domainLen);
        // 1 [Command] + 1 [domainLen] + domainLen + 2 [port]
        return new self($domain, $port, 1 + 1 + $domainLen + 2);
    }

    private static function readPort(Buffer $buffer, $offset): int
    {
        // 检查buffer offset + 2是否能够满足
        if ($buffer->getLength() < $offset + 2) {
            throw new ParseAddressException("read port failed : need more data offset:$offset+2 ,buffer length:{$buffer->getLength()}");
        }
        return $buffer->readUInt16BE($offset);
    }
}