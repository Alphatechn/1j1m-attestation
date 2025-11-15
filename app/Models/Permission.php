<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Cette classe étend Spatie Permission
    // Cela résoudra le problème de binding automatique
}
