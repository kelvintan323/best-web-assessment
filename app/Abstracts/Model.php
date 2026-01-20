<?php

namespace App\Abstracts;

use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class Model extends Eloquent
{
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
