<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminModel extends Model
{
     protected $table = 'Accounts';

     protected $primaryKey = 'AccountID';

     protected $allowedFields = ['Name', 'Username', 'Password', 'Role', 'DatetimeLogin'];
}
