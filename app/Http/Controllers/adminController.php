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
            // $now = new \DateTime();
            // $payload = [
            //      'AccountID' => $user->AccountID,
            //      'Name' => $user->Name,
            //      'Username' => $user->Username,
            //      'Role' => $user->Role,
            //      'iat' => $now->getTimestamp(), //! generate token time
            //      'exp' => $now->modify('+30 hours')->getTimestamp() //! expire token time
            // ];
            // $token = JWT::encode($payload, $this->PRIVATE_KEY, 'HS256');

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

    function rePassword(Request $request) {
        $authorize = $request->header("Authorization");
        $jwt = $this->jwtUtils->verifyToken($authorize);
        if (!$jwt->state) return response()->json([
            "state" => false,
            "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน",
        ], 400);

        $splitAuthorize = explode(" ", $authorize);
        if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return response()->json([
            "state" => false,
            "msg" => "Header[Authorization] ผิดพลาด",
        ], 400);

        $jwtToken = $splitAuthorize[1];
        $decoded = JWT::decode($jwtToken, new Key($this->jwtUtils, 'HS256'));



    }
    // public function rePassword()
    // {
    //      try {
    //           $authorizaion = $this->request->getServer('HTTP_AUTHORIZATION');
    //           if (is_null($authorizaion))
    //            return $this->response->setStatusCode(400)->setJSON(["state" => false, "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน"]);

    //           $splitAuthorize = explode(" ", $authorizaion);
    //           if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return $this->response->setStatusCode(400)->setJSON(["state" => false, "msg" => "Header[Authorization] ผิดพลาด"]);

    //           $jwtToken = $splitAuthorize[1];
    //           $decoded = JWT::decode($jwtToken, new Key($this->PRIVATE_KEY, 'HS256'));

    //           //! Body
    //           $oldPassword = $this->request->getVar('oldPassword');
    //           $newPassword = $this->request->getVar('newPassword');
    //           $validate = is_null($oldPassword) || is_null($newPassword);
    //           if ($validate) return $this->response->setStatusCode(400)->setJSON(["state" => false, "msg" => "การระบุข้อมูลไม่ครบถ้วน"]);
    //           //! ./Request validation

    //           //! Get user information & check old pasword
    //           $users = $this->getUserInfo($decoded->Username);
    //           $user = (object)$users[0]; // first row
    //           $isPass = $this->Bcrypt->verify($oldPassword, $user->Password); //! Compare password
    //           if (!$isPass) 
    //                return $this->response->setJSON(["state" => false, "msg" => "รหัสผ่านไม่ถูกต้อง"]);

    //           //! Hash new password & Update to DB
    //           $hash = $this->Bcrypt->hash($newPassword);
    //           $this->AdminModel->update($decoded->AccountID, ['Password' => $hash]);

    //           return $this->response->setJSON(["state" => true, "msg" => "แก้ไขรหัสผ่านสำเร็จ"]);
    //      } catch (\Exception $e) {
    //           return $this->response->setJSON(["state" => false, "msg" => $e->getMessage()]);
    //      }
    // }
}
