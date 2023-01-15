<?php

namespace xywf221\Trojan\Common;

use Swow\Pack\Format;

class Buffer extends \Swow\Buffer
{
    public function readInt8(int $offset): int
    {
        return $this->unpack(Format::INT8, $offset);
    }

    public function readUInt8(int $offset = 0): int
    {
        return $this->unpack(Format::UINT8, $offset);
    }


    // 无符号 int16 大端
    public function readUInt16BE(int $offset = 0): int
    {
        return $this->unpack(Format::UINT16_BE, $offset);
    }

    public function writeUInt16BE(int $val, int $offset = 0): int
    {
        return $this->write($offset, pack(Format::UINT16_BE, $val));
    }

    protected function unpack($format, $offset): int|float
    {
        $ret = unpack($format, $this->read($offset, Format::getSize($format)));
        return $ret[1];
    }


}