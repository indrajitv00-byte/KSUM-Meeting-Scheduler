<?php

namespace App\Models;

use App\Traits\AutoFill;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
  use AutoFill;

  protected $fillable = ['name', 'type', 'guard_name'];
}
