<?php

namespace App\Http\Controllers;


// use App\Http\Libraries\JWT\JWT;
use App\Http\Libraries\LineNotify;
use App\Models\AdminModel;
use App\Models\BookModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Libraries\Bcrypt;

use App\Http\Libraries\JWT\JWTUtils;
// use App\Http\Libraries\JWT\Key;

class  AdminController extends Controller
{
    private $jwtUtils;
    private $AdminModel;
    private $BookModel;
    private $Bcrypt;

    private $Line;
    public function __construct()
    {
        $this->Bcrypt = new Bcrypt(10);
        $this->AdminModel = new AdminModel();
        $this->BookModel = new BookModel();
        // $this->Line = new LineNotify('XLsFGUlUT0NavSIIvVaQAn4F71xLaiWI8wkrs2E8PLc'); //! Line Token VIP MRS
        $this->Line = new LineNotify('z2BejJ5rgd0iSDCPaF3JIB6pmodF0IDukPTGxJA4lau'); //? Line Token VIP MRS test 
        $this->jwtUtils = new JWTUtils();
    }
    private function getUserInfo($Username)
    {
        $users = $this->AdminModel->where('Username', $Username)->first();
        return $users;
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

    //TODO [POST] /check-login ///////////////////////////check pass
    public function checkLogin(Request $request)
    {
        try {
            $rules = [
                "Username" => ["required", "string", "min:1"],
                "Password" => ["required", "string", "min:1"],
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }
            //! Get users information
            //! check user
            $this->getUserInfo($request->Username);
            $user = DB::table("Accounts")
                ->where("Username", strtolower($request->Username))
                ->first();
            if (!$user) {
                return response()->json([
                    "state" => false,
                    "msg" => "ไม่มีผู้ใช้งานในระบบ",
                ], 400);
            }
            //! check password
            $isPass = $this->Bcrypt->verify($request->Password, $user->Password);
            if (!$isPass) {
                return response()->json([
                    "state" => false,
                    "msg" => "รหัสผ่านไม่ถูกต้อง",
                ]);
            }
            //! Set payload & Encode JWT
            date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();
            $payload = [
                'AccountID' => $user->AccountID,
                'Name' => $user->Name,
                'Username' => $user->Username,
                'Role' => $user->Role,
                'iat' => $now->getTimestamp(), //! generate token time
                'exp' => $now->modify('+30 hours')->getTimestamp() //! expire token time
            ];
            $token = $this->jwtUtils->generateToken($payload);
            return response()->json([
                "state" => true,
                "msg" => "เข้าสูระบบสำเร็จ",
                "token" => $token
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
    //TODO [POST] /admin/re-password
    public function rePassword(Request $request)
    {
        try {
            //! Request validation
            //! Authorize 
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "state" => false,
                "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน",
            ], 400);

            $splitAuthorize = explode(" ", $request->header("Authorization"));
            if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return response()->json([
                "state" => false,
                "msg" => "Header[Authorization] ผิดพลาด",
            ], 400);

            //! Body
            $rules = [
                "oldPassword" => ["required", "string", "min:1"],
                "newPassword" => ["required", "string", "min:1"],
            ];
            //! ./Request validation
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }

            if ($request->oldPassword === $request->newPassword) {
                return response()->json([
                    "state" => false,
                    "msg" => "รหัสผ่านซ้ำกัน",
                ], 400);
            }
            //! Get user information & check old pasword
            $this->getUserInfo($request->Username);
            $users = DB::table("Accounts")->where('AccountID', $jwt->decoded->AccountID)->first();
            $isPass = $this->Bcrypt->verify($request->oldPassword, $users->Password);
            if (!$isPass) {
                return response()->json([
                    "state" => false,
                    "msg" => "รหัสผ่านไม่ถูกต้อง",

                ]);
            }
            //! Hash new password & Update to DB
            $hash = $this->Bcrypt->hash($request->newPassword);
            DB::table("Accounts")->where('AccountID', $jwt->decoded->AccountID)->update(['Password' => $hash]);

            return response()->json([
                "state" => true,
                "msg" => "แก้ไขรหัสผ่านสำเร็จ",
                // "data" => $this->Bcrypt->hash("12346")
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
    //TODO [GET] /admin/get-books-vip
    public function getBooksVIP(Request $request)
    {
        try {
            //! Request validation
            //! Authorize
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "state" => false,
                "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน",
            ], 400);

            $splitAuthorize = explode(" ", $request->header("Authorization"));
            if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return response()->json([
                "state" => false,
                "msg" => "Header[Authorization] ผิดพลาด",
            ], 400);


            //! Decode token to payload
            if ($jwt->decoded->Role != 'admin')
                return response()->json([
                    "state" => false,
                    "msg" => "ไม่มีสิทธิ์การร้องขอข้อมูลนี้"
                ], 400);


            $result = DB::table("Booking")
                ->select('Booking.*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('Rooms.RoomLevel', '=', 'vip')
                ->get();

            return response()->json([
                "state" => true,
                "msg" => "ร้องขอข้อมูลสำเร็จ",
                "data" => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }

    //TODO [POST] /admin/admin-approvement
    public function adminApprovement(Request $request)
    {
        try {
            //! Request validation
            //! Authorize
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "state" => false,
                "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน",
            ], 400);

            $splitAuthorize = explode(" ", $request->header("Authorization"));
            if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer')
                return response()->json([
                    "state" => false,
                    "msg" => "Header[Authorization] ผิดพลาด",
                ], 400);

            //! Token index[1]
            //! Decode token to payload
            if ($jwt->decoded->Role != 'admin')
                return response()->json([
                    "state" => false,
                    "msg" => "ไม่มีสิทธิ์การร้องขอข้อมูลนี้"
                ], 400);

            //! Body
            $rules = [
                "BookID"        => ["required", "int"],
                "isApproved"    => ["required", "boolean"],
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {

                return response()->json([
                    "state" => false,
                    "msg" => "การระบุข้อมูลไม่ครบถ้วน",
                ], 400);
            }
            // //! ./Request validation

            $books = DB::table('Booking')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('BookID', '=', $request->BookID)
                ->get();
            $bookInfo = (object)$books[0];

            //! Block Reserve exist
            date_default_timezone_set('Asia/Bangkok');
            if ($request->isApproved) {
                $reservedList =  DB::table('Booking')
                    ->selectRaw('TO_CHAR("StartDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS "StartDatetime", TO_CHAR("EndDatetime", \'YYYY-MM-DD HH24:MI:SS\') AS "EndDatetime"')
                    ->where('BookID', $request->BookID)
                    ->where('Action', '!=', 'canceled')
                    ->where('Status', '=', 'approved')
                    ->get();
                $arrReserved = array();

                $nowTimestamp = new \DateTime();
                $startTimestamp = (new \DateTime($bookInfo->StartDatetime))->getTimestamp();
                $endTimestamp = (new \DateTime($bookInfo->EndDatetime))->getTimestamp();
                // //! S >= Now
                if ($nowTimestamp->getTimestamp() >= $startTimestamp) {
                    return response()->json([
                        "state" => false,
                        "msg" => "ไม่สามารถจองห้องประชุมย้อนหลังได้"
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
            }
            // //! ./Block Reserve exist
            //! Update Action & Status
            $data = [
                'Action' => $request->isApproved ? "Booking" : "canceled",
                'Status' => $request->isApproved ? "approved" : "canceled"
            ];

            DB::table("Booking")
                ->where('BookID', '=', $request->BookID)
                ->update($data);

            // //! Admin approved
            if ($request->isApproved) {
                $allReserved = $this->getRoomReserved($bookInfo->RoomID, $bookInfo->StartDatetime);

                $lineMessage = "\n" . "🌟 " . $bookInfo->RoomName . " (" . $bookInfo->Amount . " ที่นั่ง) 🌟\n"
                    . "วันที่ " . (new \DateTime($bookInfo->StartDatetime))->format('d/m/Y') . "\n\n"
                    . "คิวจองห้องประชุม\n";

                foreach ($allReserved as $reserved) {
                    $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
                }
                $lineMessage .= "\n";
                $lineMessage .= "ต้องการจองห้องประชุม\n";
                $lineMessage .= "https://snc-services.sncformer.com/SncOneWay/";
                $this->Line->sendMessage($lineMessage);
            };

            return response()->json([
                "state" => true,
                "msg" =>  $request->isApproved ? "อนุมัติการจองห้องประชุมสำเร็จ" : "ยกเลิกการจองห้องประชุมสำเร็จ",
                "data" => []
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
}
