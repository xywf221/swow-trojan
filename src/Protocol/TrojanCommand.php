<?php

namespace xywf221\Trojan\Protocol;

enum TrojanCommand: int
{
    case CONNECT = 0x01;
    case UDP_ASSOCIATE = 0x03;
}
