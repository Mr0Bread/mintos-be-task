<?php

namespace App\Service;

use DateTime;

class TimeService
{
    public function getCurrentDateTime()
    {
        return new DateTime();
    }

    public function createFromFormat($format, $time)
    {
        return DateTime::createFromFormat($format, $time);
    }
}