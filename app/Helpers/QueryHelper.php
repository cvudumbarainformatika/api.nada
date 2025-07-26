<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class QueryHelper
{
    public static function getSqlWithBindings($query)
    {
        return vsprintf(str_replace(['?'], ['\'%s\''], $query->toSql()), $query->getBindings());
    }
}