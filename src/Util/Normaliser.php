<?php

namespace Droid\Plugin\Apache\Util;

class Normaliser
{
    public function normaliseConfName($confName)
    {
        if (substr($confName, -5, 5) === '.conf') {
            return substr($confName, 0, -5);
        }
        return $confName;
    }

    public function normaliseConfFilename($confName)
    {
        if (substr($confName, -5, 5) != '.conf') {
            return $confName . '.conf';
        }
        return $confName;
    }
}
