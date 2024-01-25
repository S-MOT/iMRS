<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookModel extends Model
{
     protected $table = 'Booking';

     protected $primaryKey = 'BookID';

     protected $allowedFields = [
          "RoomID",
          "Name",
          "Code",
          "Company",
          "Tel",
          "Timestamp",
          "StartDatetime",
          "EndDatetime",
          "Purpose",
          "Action",
          "Status",
     ];
}
