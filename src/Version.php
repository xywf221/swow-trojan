<?php

namespace xywf221\Trojan;

use Swow\Library;

class Version
{
    static function info(): string
    {
        return sprintf("php:%s,swow:%s", phpversion(), Library::VERSION);
    }
}