<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\RoomModel;
use Illuminate\Support\Facades\DB;

class RoomController extends BaseController
{
     private $RoomModel;
     public function __construct()
     {
          $this->RoomModel = new RoomModel();
     }

     //TODO [GET] /room/rooms-list
     public function roomsList()
     {
          try {
               $data = DB::table('Rooms')->orderBy('RoomID', 'DESC')->get();
               return response()->json([
                    $data,
               ], 200);
          } catch (\Exception $e) {
               return response()->json([
                    "error" => $e->getMessage(),
               ], 500);
          }
     }
}
