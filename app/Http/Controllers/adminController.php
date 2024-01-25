<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\AdminModel;
use App\Http\Libraries\JWT\JWTUtils;

class  adminController extends Controller
{
    private $jwtUtils; 
    
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }
    //TODO [POST] /check-login
    function create(Request $request)
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

// $isPass = $user->Password === $request->Password;
    // function checkLogin(Request $request)
    // {
    //     try {
    //         $username = $request->input('Username');
    //         $password = $request->input('Password');
    //         $validate = is_null($username) || is_null($password);
    //         if ($validate) {
    //             return response()->json(["state" => false, "msg" => "การระบุข้อมูลไม่ครบถ้วน"], 400);
    //         }

    //         //! Get user information
    //         //! Check user
    //         $users = $this->getUserInfo($username);
    //         if (count($users) === 0) {
    //             return response()->json(["state" => false, "msg" => "ไม่มีผู้ใช้งานในระบบ"]);
    //         }

    //         //! Check password
    //         $user = (object) $users[0]; // first row
    //         $isPass = DB::check($password, $user->Password);
    //         if (!$isPass) {
    //             return response()->json(["state" => false, "msg" => "รหัสผ่านไม่ถูกต้อง"]);
    //         }

    //         //! Set payload & Encode JWT
    //         $now = now();
    //         $payload = [
    //             'AccountID' => $user->AccountID,
    //             'Name' => $user->Name,
    //             'Username' => $user->Username,
    //             'Role' => $user->Role,
    //             'iat' => $now->timestamp, //! generate token time
    //             'exp' => $now->addHours(30)->timestamp //! expire token time
    //         ];
    //         $token = $this->jwtUtils->generateToken($payload);

    //         return response()->json(["state" => true, "msg" => "เข้าสู่ระบบสำเร็จ", "token" => $token]);
    //     } catch (\Exception $e) {
    //         return response()->json(["state" => false, "msg" => $e->getMessage()], 500);
    //     }
    // }

//    //TODO [POST] /admin/re-password
//     public function rePassword(Request $request)
//     {
//         try {
//             //! Request validation
//             //! Authorize
//             $authorization = $request->header('Authorization');

//             if (is_null($authorization)) {
//                 return response()->json(["state" => false, "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน"], 400);
//             }

//             $splitAuthorization = explode(" ", $authorization);
//             if (count($splitAuthorization) !== 2 || $splitAuthorization[0] !== 'Bearer') {
//                 return response()->json(["state" => false, "msg" => "Header[Authorization] ผิดพลาด"], 400);
//             }

//             //! Token index[1]
//             $jwtToken = $splitAuthorization[1];
//             //! Decode token to payload
//             $decoded = JWT::decode($jwtToken, $this->$PRIVATE_KEY, ['HS256']);
    
//             //! Body
//             $oldPassword = $request->input('oldPassword');
//             $newPassword = $request->input('newPassword');
//             $validate = is_null($oldPassword) || is_null($newPassword);

//             if ($validate) {
//                 return response()->json(["state" => false, "msg" => "การระบุข้อมูลไม่ครบถ้วน"], 400);
//             }
//             //! ./Request validation

//             //! Get user information & check old password
//             $users = $this->getUserInfo($decoded->Username);
//             $user = (object)$users[0]; // first row
//             $isPass = DB::check($oldPassword, $user->Password); //! Compare password

//             if (!$isPass) {
//                 return response()->json(["state" => false, "msg" => "รหัสผ่านไม่ถูกต้อง"]);
//             }

//             //! Hash new password & Update to DB

//             $hash = DB::make($newPassword);
//             $this->AdminModel->update($decoded->AccountID, ['Password' => $hash]);

//             return response()->json(["state" => true, "msg" => "แก้ไขรหัสผ่านสำเร็จ"]);
//         } catch (\Exception $e) {
//             return response()->json(["state" => false, "msg" => $e->getMessage()]);
//         }
//     }

   
}



