<?php

namespace xywf221\Trojan\Protocol;

enum Socks5AddressType: int
{
    case IPv4 = 0x01;
    case FQDN = 0x03;
    case IPv6 = 0x04;
}
