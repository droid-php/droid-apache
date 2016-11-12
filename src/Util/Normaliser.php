<?php

namespace Droid\Plugin\Apache\Util;

class Normaliser
{
    public function normaliseConfName($confName, $extension = '.conf')
    {
        $ext_len = strlen($extension);
        if (substr($confName, 0-$ext_len, $ext_len) === $extension) {
            return substr($confName, 0, 0-$ext_len);
        }
        return $confName;
    }

    public function normaliseConfFilename($confName, $extension = '.conf')
    {
        $ext_len = strlen($extension);
        if (substr($confName, 0-$ext_len, $ext_len) != $extension) {
            return $confName . $extension;
        }
        return $confName;
    }
}
