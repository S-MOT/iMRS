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
        $this->BookModel = new BookModel();
        // // $this->Line = new LineNotify('Ba9xCyeayKnZxgbDgu3RSCe6jcYPiTMv1uPG8V5E21c'); //! Line Token 1 on 1
        // // $this->Line = new LineNotify('xiMZBK86ZnSC8jU6lrv07qaCdjmgIKJyUDpXc4PdNOB'); //! Line Token General room //PPSO
        //  $this->Line = new LineNotify('x0UK5hTkQCkQ57d32vxpIl0h2sTYpoiD4FSUg4TpN5Z'); //! Line Token General room
        //  $this->LineVIPRequest = new LineNotify('Xmz9U3rA1jgMke59nRiu2YCOEWmwFBIZuKMDEloIa4I'); //! Line Token VIP Request room
        //  $this->LineVIP = new LineNotify('zyvtd70EviovzBKXgqRDnWoo0U4H4NkkWiMyevvhWsV'); //! Line Token VIP MRS
    }

    private function checkRoom($RoomID)
    {
        $query = DB::table('Rooms')
            ->select('*')
            ->where('BookID', '=', $RoomID)->first();
        return $query;
    }
    private function getRoomReserved($RoomID, $StartDatetime)

    {
        $query = DB::table('vBooking')
            ->where('RoomID', '=', $RoomID)
            ->whereDate('StartDatetime', '=', date('Y-m-d', strtotime($StartDatetime)))
            ->where('Action', '=', 'booking')
            ->where('Status', '=', 'approved')
            ->first();
        return $query;


        //   $sql = "SELECT * FROM vBooking 
        //   WHERE RoomID=? 
        //   AND CONVERT(DATE, StartDatetime) = CONVERT(DATE, ?) 
        //   AND Action='booking'
        //   AND Status='approved';";
        //   $query = $this->BookModel->query($sql, [$RoomID, $StartDatetime]);
        //   return $query->getResult();
    }
    private function checkEditCode()
    {
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

            $reservedList = DB::table('Booking')
                ->selectRaw('TO_CHAR("StartDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS "StartDatetime", TO_CHAR("EndDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS "EndDatetime"')
                ->where('RoomID', $request->RoomID)
                ->where('Action', '!=', 'canceled')
                ->where('Status', '=', 'approved')
                ->get();

            $arrReserved = array();
            $startTimestamp = (new \DateTime($request->StartDatetime))->getTimestamp();
            $endTimestamp = (new \DateTime($request->EndDatetime))->getTimestamp();

            foreach ($reservedList as $reserved) {
                // Check if the properties exist before accessing them
                $dbStartDatetime = isset($reserved->StartDatetime) ? $reserved->StartDatetime : null;
                $dbEndDatetime = isset($reserved->EndDatetime) ? $reserved->EndDatetime : null;

                if ($dbStartDatetime === null || $dbEndDatetime === null) {
                    // Handle the case where the properties are not present
                    continue;
                }

                $dbStartTimestamp = (new \DateTime($dbStartDatetime))->getTimestamp();
                $dbEndTimestamp = (new \DateTime($dbEndDatetime))->getTimestamp();

                if (
                    $startTimestamp >= $dbStartTimestamp && $startTimestamp < $dbEndTimestamp
                    || $endTimestamp > $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp
                    || $startTimestamp <= $dbStartTimestamp && $endTimestamp >= $dbEndTimestamp
                    || $startTimestamp >= $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp
                ) {
                    array_push($arrReserved, 1);
                    break;
                }
            } { //! Line Notify
                //  $allReserved = $this->getRoomReserved($RoomID, $StartDatetime);
                //  if ($roomInfo->RoomLevel != 'vip') {

                //       $lineMessage = "\n" . $roomInfo->RoomName . " (" . $roomInfo->Amount . " ที่นั่ง)\n"
                //            . "วันที่ " . (new \DateTime($StartDatetime))->format('d/m/Y') . "\n\n"
                //            . "คิวจองห้องประชุม\n";

                //       foreach ($allReserved as $reserved) {
                //            $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
                //       }

                //       $lineMessage .= "\n";
                //       $lineMessage .= "ต้องการจองห้องประชุม\n";
                //       $lineMessage .= "https://snc-services.sncformer.com/SncOneWay/";

                //       $this->Line->sendMessage($lineMessage);
                //       return $this->response->setJSON(["state" => true, "msg" => "เพิ่มการจองสำเร็จ", "lineMessage" => $lineMessage]);
                //  } else {
                //       $lineMessage = "\n" . $roomInfo->RoomName . " (" . $roomInfo->Amount . " ที่นั่ง)\n"
                //            . "วันที่ " . (new \DateTime($StartDatetime))->format('d/m/Y') . "\n\n";

                //       foreach ($allReserved as $reserved) {
                //            $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
                //       }

                //       $lineMessage .= "กรุณาอนุมัติการจองได้ที่ :\n";
                //       $lineMessage .= "https://snc-services.sncformer.com/iMRS/IT/";

                //       $this->LineVIPRequest->sendMessage($lineMessage);
                //  }
            } //! ./Line Notify

            if (count($arrReserved) > 0) {
                return response()->json([
                    "state" => false,
                    "msg" => "ห้องประชุมนี้ได้ถูกจองไว้แล้วในช่วงเวลานี้",
                ], 400);
            }

            // Add Booking
            DB::table("Booking")->insert([
                "RoomID"          => $request->RoomID,
                "Name"            => $request->Name,
                "Code"            => $request->Code,
                "Company"         => $request->Company,
                "Tel"             => $request->Tel,
                "Timestamp"       => now(),
                "StartDatetime"   => $request->StartDatetime,
                "EndDatetime"     => $request->EndDatetime,
                "Purpose"         => $request->Purpose,
            ]);

            return response()->json([
                "state" => true,
                "msg" => "เพิ่มการจองสำเร็จ",
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
            $result = DB::table('Booking')
                ->select('*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('EndDatetime', '>=', now())
                ->where('Action', '=', 'booking')
                ->where('Status', '=', 'approved')
                ->get();

            foreach ($result as $row) {
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

            return response()->json([], 201);
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

            $query = DB::table("Booking")
                ->select('*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('EndDatetime', '>=', GETDATE())
                ->where('Status', '=', 'approved')
                ->where('RoomID', '=', $RoomID)
                ->get();

            // $query = $this->BookModel->query
            // ("SELECT * FROM vBooking WHERE EndDatetime>=GETDATE() AND Status='approved' AND RoomID=?;", [$RoomID]);
            // $data = array();

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

            $result = DB::table('Booking')
                ->select('*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->orderByDesc('BookID')
                ->take(50)
                ->get();

            return response()->json([
                "state" => true,
                "data" => $result,
            ], 201);
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
            // //! ./Request Validation
            // if (!$this->checkEditCode($BookID, $Code))
            //     return response()->setJSON([
            //         "state" => false,
            //         "msg" => "รหัสการยืนยันไม่ถูกต้อง"
            //     ], 400);


            // $data = [
            //     'Name'    => $Name,
            //     'Code'    => $Code,
            //     'Company' => $Company,
            //     'Tel'     => $Tel,
            //     'Purpose' => $Purpose,
            // ];

            // $this->BookModel->update($BookID, $data);

            // return response()->json([
            //     "state" => true,
            //     "msg" => "แก้ไขการจองสำเร็จ",
            //     "data" => $result,
            // ], 201);
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
            $BookID = request()->getVar('BookID');
            //! Request Validation
            //! NULL Check
            $validator = Validator::make($request->all(),  $BookID);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }
            // ! ./Request Validation

            // $this->BookModel->query("UPDATE Booking SET Action='cancel' WHERE BookID=?", [$BookID]);
            // $query = $this->BookModel->query("SELECT * FROM vBooking WHERE BookID=?;", [$BookID]);
            // $BookInfo = $query->getResult()[0];

            // $this->BookModel->update($BookID, ['Action' => 'canceled']);

            { //   //! Line Notify
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
            } //   //! ./Line Notify

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
