<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Http\Libraries\JWT\JWTUtils;
use App\Http\Libraries\JWT\Key;

class  adminController extends Controller
{
    private $jwtUtils;

    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }
    //TODO [POST] /check-login
    function checkLogin(Request $request)
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
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
            ], 500);
        }
    }
    
    //TODO [POST] /admin/re-password

    // function rePassword(Request $request)
    // { 
    //  try {   
    //     $authorize = $request->header("Authorization");
    //     $jwt = $this->jwtUtils->verifyToken($authorize);
    //     if (!$jwt->state) return response()->json([
    //         "state" => false,
    //         "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน",
    //     ], 400);

    //     // $authorizaion = $this->request->getServer('HTTP_AUTHORIZATION');
    //     // if (is_null($authorizaion))
    //     //  return $this->response->setStatusCode(400)->setJSON(["state" => false, "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน"]);
        
    //     $splitAuthorize = $request->header("Authorization");
    //     $jwt = $this->jwtUtils->verifyToken($authorize);
    //     if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return response()->json([
    //         "state" => false,
    //         "msg" => "Header[Authorization] ผิดพลาด",
    //     ], 400); 
       
    //     $rules = [
    //         "oldPassword" => ["required", "string", "min:1"],
    //         "newPassword" => ["required", "string", "min:1"],
    //     ];

    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             "state" => false,
    //             "msg" => "การระบุข้อมูลไม่ครบถ้วน",
    //         ], 400);
    //     }
    //     $isPass = $request->oldPassword === $request->Password;
    //     if (!$isPass) {
    //         return response()->json([
    //             "state" => false,
    //             "msg" => "รหัสผ่านไม่ถูกต้อง",
    //         ]);
    //     }


    //     $data = DB::make($newPassword);
    //     $this->AdminModel->update($decoded->AccountID, ['Password' => $data]);


         //! Hash new password & Update to DB

        //  $hash = $this->Bcrypt->hash($newPassword);
        //  $this->AdminModel->update($decoded->AccountID, ['Password' => $hash]);
             
    //         return response()->json([
    //             "state" => true, "msg" => "แก้ไขรหัสผ่านสำเร็จ",
    //         ], 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => $e->getMessage(),
    //             "data" => [],
    //         ], 500);
    //     }
    // }

   
}