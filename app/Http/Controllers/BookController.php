<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWT;
use App\Models\BookModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Http\Libraries\JWT\JWTUtils;
use App\Http\Libraries\JWT\Key;


// line group token: x0UK5hTkQCkQ57d32vxpIl0h2sTYpoiD4FSUg4TpN5Z
class  BookController extends Controller
{
    private $BookModel;
    private $Line;
    private $LineVIPRequest;
    private $LineVIP;

    public function __construct()
    {
        // $this->BookModel = new BookModel();
        // // $this->Line = new LineNotify('Ba9xCyeayKnZxgbDgu3RSCe6jcYPiTMv1uPG8V5E21c'); //! Line Token 1 on 1
        // // $this->Line = new LineNotify('xiMZBK86ZnSC8jU6lrv07qaCdjmgIKJyUDpXc4PdNOB'); //! Line Token General room //PPSO
        //  $this->Line = new LineNotify('x0UK5hTkQCkQ57d32vxpIl0h2sTYpoiD4FSUg4TpN5Z'); //! Line Token General room
        //  $this->LineVIPRequest = new LineNotify('Xmz9U3rA1jgMke59nRiu2YCOEWmwFBIZuKMDEloIa4I'); //! Line Token VIP Request room
        //  $this->LineVIP = new LineNotify('zyvtd70EviovzBKXgqRDnWoo0U4H4NkkWiMyevvhWsV'); //! Line Token VIP MRS
    }

    private function checkRoom($RoomID)
    {
        $query = $this->BookModel->query('SELECT * FROM Rooms WHERE RoomID=?;', [$RoomID]);
        return $query->getResult()[0];
    }

    private function getRoomReserved($RoomID, $StartDatetime)
    {
        $sql = "SELECT * FROM vBooking 
         WHERE RoomID=? 
         AND CONVERT(DATE, StartDatetime) = CONVERT(DATE, ?) 
         AND Action='booking'
         AND Status='approved';";
        $query = $this->BookModel->query($sql, [$RoomID, $StartDatetime]);
        return $query->getResult();
    }

    //TODO [POST] /book/add-book
    public function addBook(Request $request)
    {
        try {
            $rules = [
                "RoomID"        => ["required", "int", "min:1"],
                "Name"          => ["required", "string", "min:1"],
                "Code"          => ["required", "string", "min:7"],
                "Company"       => ["required", "string", "min:2"],
                "Tel"           => ["required", "string", "min:1"],
                "StartDatetime" => ["required", "date",],
                "EndDatetime"   => ["required", "date",],
                "Purpose"       => ["required", "string", "min:1"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }

            // $sqlBlockReserve = DB::table('Booking')
            //     ->select('*')
            //     ->where('RoomID', "=", $request->RoomID)
            //     ->where('Action', '!=', 'canceled')
            //     ->where('Status', '=', 'approved')
            //     ->get();


            $sqlBlockReserve = DB::table('Booking')
                ->selectRaw('TO_CHAR("StartDatetime", \'YYYY-MM-DD HH24:MI:SS\') 
                AS StartDatetime, TO_CHAR ("EndDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS EndDatetime')
                ->where('RoomID', $request->RoomID)
                ->where('Action', '!=', 'canceled')
                ->where('Status', '=', 'approved')
                ->get();

            // $reservedList = $this->BookModel->query($sqlBlockReserve, [$RoomID])->getResult();
            // $arrReserved = array();
            // $startTimestamp = (new \DateTime($StartDatetime))->getTimestamp();
            // $endTimestamp = (new \DateTime($EndDatetime))->getTimestamp();
            // $nowTimestamp = (new \DateTime())->getTimestamp();

            // foreach ($reservedList as $reserved) {
            //     $dbStartTimestamp = (new \DateTime($reserved->StartDatetime))->getTimestamp();
            //     $dbEndTimestamp = (new \DateTime($reserved->EndDatetime))->getTimestamp();

            //         if ($startTimestamp >= $dbStartTimestamp && $startTimestamp < $dbEndTimestamp) {
            //             array_push($arrReserved, 1);
            //             break;
            //        }
            //        //! Start > E >= End
            //        if ($endTimestamp > $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp) {
            //             array_push($arrReserved, 1);
            //             break;
            //        }
            //        //! S <= Start & E >= End
            //        if ($startTimestamp <= $dbStartTimestamp && $endTimestamp >= $dbEndTimestamp) {
            //             array_push($arrReserved, 1);
            //             break;
            //        }
            //        //! S >= Start & E <= End
            //        if ($startTimestamp >= $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp) {
            //             array_push($arrReserved, 1);
            //             break;
            //        }
            //   }

            //   if (count($arrReserved) !== 0) return $this->response->setJSON(["state" => false, "msg" => "ห้องประชุมนี้ได้ถูกจองไว้แล้วในช่วงเวลานี้"]);

            //   //! ./Block Reverse exist

            //   $roomInfo = $this->checkRoom($RoomID);

            //   //! Add Booking
            //   $sql = "INSERT INTO Booking (RoomID,Name,Code,Company,Tel,Timestamp,StartDatetime,EndDatetime,Purpose,Action,Status)
            //             VALUES (?,?,?,?,?,GETDATE(),?,?,?,?,?)";
            //   $this->BookModel->query($sql, [
            //        $RoomID,
            //        $Name,
            //        $Code,
            //        $Company,
            //        $Tel,
            //        $StartDatetime,
            //        $EndDatetime,
            //        $Purpose,
            //        'booking',
            //        $roomInfo->RoomLevel == 'vip' ? 'pending' : 'approved'
            //   ]);

            return response()->json([
                "state" => true,
                "msg" => "เพิ่มการจองสำเร็จ",
                "data" => $sqlBlockReserve
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
        //TODO [GET] /book/get-book
        public function getBook(Request $request)
        
             try {

                $query = $this->BookModel->query
                ("SELECT * FROM vBooking WHERE EndDatetime>=GETDATE() AND Action='booking' AND Status='approved';");
               $data = array();


             }
    }
}
