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
    }

    //TODO [GET] /book/get-book
    public function getBook()
    {
        try {

            $query = $this->BookModel->query("SELECT * FROM vBooking WHERE EndDatetime>=GETDATE() AND Action='booking' AND Status='approved';");
            $data = array();

            foreach ($query->getResult() as $row) {
                $start = new \DateTime($row->StartDatetime);
                $end = new \DateTime($row->EndDatetime);
                $datetimeBlock = array();
                while ($start->getTimestamp() <= $end->getTimestamp()) {
                    array_push($datetimeBlock, $start->format('Y-m-d H:i:s'));
                    $start->modify('+30 minutes');
                }
                $row->DatetimeBlock = $datetimeBlock;
                array_push($data, $row);
            }

            return response()->json([($data)], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }

    //TODO [GET] /book/get-book-room-id
    //! Not use
    public function getBookByRoomID($RoomID = null)
    {
        try {

            $query = $this->BookModel->query("SELECT * FROM vBooking WHERE EndDatetime>=GETDATE() AND Status='approved' AND RoomID=?;", [$RoomID]);
            $data = array();

            foreach ($query->getResult() as $row) {
                $start = new \DateTime($row->StartDatetime);
                $end = new \DateTime($row->EndDatetime);
                $datetimeBlock = array();
                while ($start->getTimestamp() <= $end->getTimestamp()) {
                    array_push($datetimeBlock, $start->format('Y-m-d H:i:s'));
                    $start->modify('+30 minutes');
                }
                $row->DatetimeBlock = $datetimeBlock;
                array_push($data, $row);
            }

            return response()->json([($data)], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }

    //TODO [GET] /book/get-book-history
    public function getBookHistory()
    {
        try {
            $query = DB::orderBy('BookID', 'DESC')->take(50)->get();

            return response()->json($query->getResult(), 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }

    //TODO [GET] /book/get-book/($code)
    public function getBookByCode($code = null)
    {
        try {
            $result = DB::table("Booking")
                ->select('Booking.*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('Booking.Code', '=', $code)
                ->where('Booking.StartDatetime', '>=', DB::raw('NOW()'))
                ->where('Booking.Action', '=', 'booking')
                ->where('Booking.Status', '=', 'approved')
                ->get();

            return response()->json([
                "state" => true,
                "msg" => "แก้ไขรหัสผ่านสำเร็จ",
                "data" => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
    //TODO [POST] /book/edit-book
    public function editBook(Request $request)
    {
        try {

            $rules = [
                "BookID" => ["required", "int", "min:1"],
                "Name" => ["required", "string", "min:1"],
                "Code" => ["required", "int", "min:7"],
                "Company" => ["required", "string", "min:1"],
                "Tel" => ["required", "string", "min:1"],
                "Purpose" => ["required", "string", "min:1"],
            ];

            //! Request Validation
            //! NULL Check

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }
            //! ./Request Validation

            // if (!$this->checkEditCode($BookID, $Code)) return $this->response->setJSON(["state" => false, "msg" => "รหัสการยืนยันไม่ถูกต้อง"]);

            // $data = [
            //     'Name' => $Name,
            //     'Code' => $Code,
            //     'Company' => $Company,
            //     'Tel' => $Tel,
            //     'Purpose' => $Purpose,
            // ];

            // $this->BookModel->update($BookID, $data);

            return response()->json([
                "state" => true,
                "msg" => "แก้ไขการจองสำเร็จ",
                // "data" => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }

    //TODO [POST] /book/cancel-book
    public function cancelBook(Request $request)
    {
        try {
            // $BookID = $this->request->getVar('BookID');
            // //! Request Validation
            // //! NULL Check
            // $validate = is_null($BookID);
            // if ($validate) return $this->response->setStatusCode(400)->setJSON(["state" => false, "msg" => "การระบุข้อมูลไม่ครบถ้วน"]);
            //! ./Request Validation

            // $this->BookModel->query("UPDATE Booking SET Action='cancel' WHERE BookID=?", [$BookID]);
            // $query = $this->BookModel->query("SELECT * FROM vBooking WHERE BookID=?;", [$BookID]);
            // $BookInfo = $query->getResult()[0];

            // $this->BookModel->update($BookID, ['Action' => 'canceled']);

            //   //! Line Notify
            //   $allReserved = $this->getRoomReserved($BookInfo->RoomID, $BookInfo->StartDatetime);
            //   $RoomLevel = ((object)$allReserved[0])->RoomLevel;

            //   $lineMessage = "\n" . $BookInfo->RoomName . " (" . $BookInfo->Amount . " ที่นั่ง)\n"
            //        . "วันที่ " . (new \DateTime($BookInfo->StartDatetime))->format('d/m/Y') . "\n\n"
            //        . "คิวจองห้องประชุม\n";

            //   foreach ($allReserved as $reserved) {
            //        $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
            //   }

            //   $lineMessage .= "\n";
            //   $lineMessage .= "ต้องการจองห้องประชุม\n";
            //   $lineMessage .= "https://snc-services.sncformer.com/SncOneWay/";

            //   if ($RoomLevel == 'vip') {
            //        $this->LineVIP->sendMessage($lineMessage);
            //   } else {
            //        $this->Line->sendMessage($lineMessage);
            //   }
            //   //! ./Line Notify

            return response()->json([
                "state" => true,
                "msg" => "แก้ไขการจองสำเร็จ",
                // "data" => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
}
