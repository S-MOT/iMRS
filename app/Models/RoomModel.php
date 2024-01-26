<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomModel extends Model
{
     protected $table = 'Rooms';

     protected $primaryKey = 'RoomID';

     protected $allowedFields = ['RoomName', 'RoomLevel', 'Amount', 'Detail', 'PicturePath'];
}
