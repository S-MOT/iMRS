<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Libraries\Bcrypt;
use App\Http\Libraries\JWT\JWTUtils;
use App\Http\Libraries\JWT\Key;

class  AdminController extends Controller
{
    private $jwtUtils;
    private $Bcrypt;


    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
        $this->Bcrypt = new Bcrypt(10);
    }

    //TODO [POST] /check-login
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
            $user = DB::table("Accounts")
                ->where("Username", strtolower($request->Username))
                ->first();
            if (!$user) {
                return response()->json([
                    "state" => false,
                    "msg" => "ไม่มีผู้ใช้งานในระบบ",
                ], 400);
            }
            $isPass = $user->Password === $request->Password;
            if (!$isPass) {
                return response()->json([
                    "state" => false,
                    "msg" => "รหัสผ่านไม่ถูกต้อง",
                ]);
            }
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
                "state" => true, "msg" => "เข้าสูระบบสำเร็จ", "token" => $token
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

            $rules = [
                "oldPassword" => ["required", "string", "min:1"],
                "newPassword" => ["required", "string", "min:1"],
            ];

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

            $users = DB::table("Accounts")->where('AccountID', $jwt->decoded->AccountID)->first();
            $isPass = $request->oldPassword === $users->Password;
            if (!$isPass) {
                return response()->json([
                    "state" => false,
                    "msg" => "รหัสผ่านไม่ถูกต้อง",
                ]);
            }

            DB::table("Accounts")->where('AccountID', $jwt->decoded->AccountID)->update(['Password' => $request->newPassword]);

            return response()->json([
                "state" => true,
                "msg" => "แก้ไขรหัสผ่านสำเร็จ",
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

            if ($jwt->decoded->Role != 'admin')
                return response()->setJSON([
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


    //TODO [POST] /admin/admin-approvement
    public function adminApprovement(Request $request)
    {
        try {
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
            if ($jwt->decoded->Role != 'admin')
                return response()->setJSON([
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

            $bookInfo = DB::table("Booking")
                ->select('Booking.*', 'Rooms.*')
                ->leftJoin('Rooms', 'Booking.RoomID', '=', 'Rooms.RoomID')
                ->where('Booking.BookID', '=', $request->BookID)
                ->where('Rooms.RoomLevel', '=', 'vip')
                ->first();

            $data = [
                'Action' => $request->isApproved ? "booking" : "canceled",
                'Status' => $request->isApproved ? "approved" : "canceled"
            ];

            DB::table("Booking")
                ->where('BookID', '=', $request->BookID)
                ->update($data);

            return response()->json([
                "state" => true,
                "msg" =>  $request->isApproved ? "อนุมัติการจองห้องประชุมสำเร็จ" : "ยกเลิกการจองห้องประชุมสำเร็จ",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "state" => false,
                "msg" => $e->getMessage()
            ], 500);
        }
    }
}
