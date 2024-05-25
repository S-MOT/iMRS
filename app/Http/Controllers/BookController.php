<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Libraries\LineNotify;


// line group token: x0UK5hTkQCkQ57d32vxpIl0h2sTYpoiD4FSUg4TpN5Z
class  BookController extends Controller
{
    // private $BookModel;
    private $BookModel;
    private $Line;
    private $LineVIPRequest;
    private $LineVIP;

    public function __construct()
    {
        // $this->BookModel = new BookModel();
        // $this->Line = new LineNotify('sCkhQ307rX0KCwsXj9uWK3C067ek5BQw0e4R050FWLm'); //! Line Token General room (test iMRS2)
        $this->Line = new LineNotify('0wc1OH8c3XZk9hfyxIgsF36zWWJgr9FKBYbA1yqehoV'); //? Line Token General room (test iMRS2)
        // $this->LineVIPRequest = new LineNotify('Meeh3Fg2rrgrv75Z3pXEAEGjxBCdshzCVyEJkh831LM'); //! Line Token VIP Request room (VIP)
        $this->LineVIPRequest = new LineNotify('wR1pDVzKLyZunYOzTaGNowkLfp8Bheyvo7TZEI5YEqv'); //? Line Token VIP Request room (VIP)
        // $this->LineVIP = new LineNotify('XLsFGUlUT0NavSIIvVaQAn4F71xLaiWI8wkrs2E8PLc'); //! Line Token VIP MRS (MRS)s
        $this->LineVIP = new LineNotify('z2BejJ5rgd0iSDCPaF3JIB6pmodF0IDukPTGxJA4lau'); //? Line Token VIP MRS (MRS)s
    }
    private function checkRoom($RoomID)
    {
        $query = DB::table('Rooms')
            ->select('*')
            ->where('RoomID', '=', $RoomID)->first();
        return $query;
    }
    private function getRoomReserved($RoomID, $StartDatetime)
    {
        $query = DB::table('Booking')
            ->select('*')
            ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
            ->where('Rooms.RoomID', "=", $RoomID)
            ->whereDate('StartDatetime', '=', date('Y-m-d', strtotime($StartDatetime)))
            ->where('Action', '=', 'Booking')
            ->where('Status', '=', 'approved')
            ->get();
        return $query;
    }
    private function checkEditCode($BookID, $Code)
    {
        $data = DB::table('Booking')
            ->where('BookID', $BookID)
            ->where('Code', $Code)
            ->get();
        return count($data) > 0;
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
            //! Request Validation
            //! NULL Check
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }
            //! Block Reserve exist
            $reservedList = DB::table('Booking')
                ->selectRaw('TO_CHAR("StartDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS "StartDatetime", TO_CHAR("EndDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS "EndDatetime"')
                ->where('RoomID', $request->RoomID)
                ->where('Action', '!=', 'canceled')
                ->where('Status', '=', 'approved')
                ->get();
            $arrReserved = array();

            $startTimestamp = (new \DateTime($request->StartDatetime))->getTimestamp();
            $endTimestamp = (new \DateTime($request->EndDatetime))->getTimestamp();
            $nowTimestamp = new \DateTime();
            //! S >= Now

            if ($nowTimestamp->getTimestamp() >= $startTimestamp) {
                return response()->json([
                    "state" => false,
                    "msg"   => "ไม่สามารถจองห้องประชุมย้อนหลังได้"
                ]);
            }
            $arrReserved = [];
            foreach ($reservedList as $reserved) {

                $dbStartDatetime = isset($reserved->StartDatetime) ? $reserved->StartDatetime : null;
                $dbEndDatetime = isset($reserved->EndDatetime) ? $reserved->EndDatetime : null;

                if ($dbStartDatetime === null || $dbEndDatetime === null) {
                    continue;
                }
                $dbStartTimestamp = (new \DateTime($dbStartDatetime))->getTimestamp();
                $dbEndTimestamp = (new \DateTime($dbEndDatetime))->getTimestamp();
                if (
                    //! Start >= S > End
                    $startTimestamp >= $dbStartTimestamp && $startTimestamp < $dbEndTimestamp
                    //! Start > E >= End
                    || $endTimestamp > $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp
                    //! S <= Start & E >= End
                    || $startTimestamp <= $dbStartTimestamp && $endTimestamp >= $dbEndTimestamp
                    //! S >= Start & E <= End                    
                    || $startTimestamp >= $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp
                ) {
                    array_push($arrReserved, 1);
                    break;
                }
            }
            if (count($arrReserved) > 0) {
                return response()->json([
                    "state" => false,
                    "msg" => "ห้องประชุมนี้ได้ถูกจองไว้แล้วในช่วงเวลานี้",
                ], 400);
            }
            // //! ./Block Reverse exist

            $roomInfo = $this->checkRoom($request->RoomID);

            DB::table("Booking")->insert([
                "RoomID"         => $request->RoomID,
                "Name"           => $request->Name,
                "Code"           => $request->Code,
                "Company"        => $request->Company,
                "Tel"            => $request->Tel,
                "Timestamp"      => now(),
                "StartDatetime"  => $request->StartDatetime,
                "EndDatetime"    => $request->EndDatetime,
                "Purpose"        => $request->Purpose,
                "Action"         => 'Booking',
                "Status"         => $roomInfo && $roomInfo->RoomLevel == 'vip' ? 'pending' : 'approved'

                // ("Room Level: " . ($roomInfo ? $roomInfo->RoomLevel : "null"))

                // "RoomID"        => ["required", "int", "min:1"],
                // "Name"          => ["required", "string", "min:1"],
                // "Code"          => ["required", "string", "min:7"],
                // "Company"       => ["required", "string", "min:2"],
                // "Tel"           => ["required", "string", "min:1"],
                // "StartDatetime" => ["required", "date",],
                // "EndDatetime"   => ["required", "date",],
                // "Purpose"       => ["required", "string", "min:1"],

            ]);
            // //! Line Notify
            $allReserved = $this->getRoomReserved($request->RoomID, $request->StartDatetime);
            if ($roomInfo->RoomLevel != 'vip') {

                $lineMessage = "\n" . $roomInfo->RoomName . " (" . $roomInfo->Amount . " ที่นั่ง)\n"
                    . "วันที่ " . (new \DateTime($request->StartDatetime))->format('d/m/Y') . "\n\n"
                    . "คิวจองห้องประชุม\n";

                foreach ($allReserved as $reserved) {
                    $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
                }
                $lineMessage .= "\n";
                $lineMessage .= "ต้องการจองห้องประชุม\n";
                $lineMessage .= "https://snc-services.sncformer.com/SncOneWay/";
                $this->Line->sendMessage($lineMessage);

                return response()->json(["state" => true, "msg" => "เพิ่มการจองสำเร็จ", "lineMessage" => $lineMessage]);
            } else {
                $lineMessage = "\n" . $roomInfo->RoomName . " (" . $roomInfo->Amount . " ที่นั่ง)\n"
                    . "วันที่ " . (new \DateTime($request->StartDatetime))->format('d/m/Y') . "\n\n";

                foreach ($allReserved as $reserved) {
                    $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
                }
                $lineMessage .= "กรุณาอนุมัติการจองได้ที่ :\n";
                $lineMessage .= "https://snc-services.sncformer.com/iMRS/IT/";
                $this->LineVIPRequest->sendMessage($lineMessage);
            }
            //! ./Line Notify
            return response()->json([
                "state" => true,
                "msg" => "เพิ่มการจองสำเร็จ",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage(),
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
                ->where('Action', '=', 'Booking')
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
                // array_push($data, $row);
            }
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
    //TODO [GET] /book/get-book-room-id
    //! Not use
    public function getBookByRoomID($RoomID = null)
    {
        try {
            $query = DB::table("Booking")
                ->select('*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('Booking.EndDatetime', '>=', now())
                ->where('Booking.Status', '=', 'approved')
                ->where('Booking.RoomID', '=', $RoomID)
                ->get();
            $data = array();

            foreach ($query as $row) {
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
            return response()->json([
                "state" => true,
                "data" => $data,
            ], 201);
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
                ->where('Booking.Action', '=', 'Booking')
                ->where('Booking.Status', '=', 'approved')
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
    //TODO [POST] /book/edit-book ////////////*////////////////// edit 
    public function editBook(Request $request)
    {
        try {
            $rules = [
                "BookID"    => ["required", "int"],
                "Name"      => ["required", "string", "min:1"],
                "Code"      => ["required", "string", "min:7"],
                "Company"   => ["required", "string", "min:1"],
                "Tel"       => ["required", "string", "min:1"],
                "Purpose"   => ["required", "string", "min:1"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg"   => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }

            DB::table('Booking')
                ->where('BookID', $request->BookID)
                ->update([
                    "Name"      => $request->Name,
                    "Code"      => $request->Code,
                    "Company"   => $request->Company,
                    "Tel"       => $request->Tel,
                    "Purpose"   => $request->Purpose,
                ]);

            return response()->json([
                "state" => true,
                "msg"   => "แก้ไขการจองสำเร็จ",
                "data"  => $request->all(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg"   => $e->getMessage(),
            ], 500);
        }
    }


    //TODO [POST] /book/cancel-book
    public function cancelBook(Request $request)
    {
        try {
            $rules = [
                "BookID" => ["required", "int", "min:1"],
            ];

            //! ./Request Validation
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }
            //! ./Request Validation

            $result = DB::table("Booking")
                ->select('*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('BookID', '=', $request->BookID)
                ->where('Booking.Status', '=', 'approved')
                ->get();

            DB::table('Booking')
                ->where('BookID', $request->BookID)
                ->update(['Action' => 'cancel']);

            $BookInfo = DB::table('Booking')
                ->select('*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('BookID', $request->BookID)
                ->first();

            $allReserved = $this->getRoomReserved($BookInfo->RoomID, $BookInfo->StartDatetime);
            $RoomLevel = "vip";

            $lineMessage = "\n" . $BookInfo->RoomName . " (" . $BookInfo->Amount . " ที่นั่ง)\n"
                . "วันที่ " . (new \DateTime($BookInfo->StartDatetime))->format('d/m/Y') . "\n\n"
                . "คิวจองห้องประชุม\n";

            foreach ($allReserved as $reserved) {
                $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
            }
            $lineMessage .= "\n";
            $lineMessage .= "ต้องการจองห้องประชุม\n";
            $lineMessage .= "https://snc-services.sncformer.com/SncOneWay/";

            if ($RoomLevel == 'vip') {
                $this->LineVIP->sendMessage($lineMessage);
            } else {
                $this->Line->sendMessage($lineMessage);
            }
            return response()->json([
                "state" => true,
                "msg" => "แก้ไขการจองสำเร็จ",
                "data" => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
}
