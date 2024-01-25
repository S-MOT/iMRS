<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Libraries\JWT\JWTUtils;

class  testController extends Controller
{
    private $jwtUtils; 
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    
    //* [GET] /todolist
    function getAll(Request $request)
    {
        try {
            // $authorize = $request->header("Authorization");
            // $jwt = $this->jwtUtils->verifyToken($authorize);
            // if (!$jwt->state) return response()->json([
            //     "status"  => "error",
            //     "message" => "Unauthorized",
            //     "data"    => []
            // ], 401);
            // $decoded = $jwt->decoded;

            $result = DB::table("Booking")
                ->select()
                ->get();
           
            return response()->json([
                "status"    => "success",
                "message"   => "Data from query",
                "data"      => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

    function checkLogin(Request $request)
    {
        try {
            $username = $request->input('Username');
            $password = $request->input('Password');
            $validate = is_null($username) || is_null($password);
            if ($validate) {
                return response()->json(["state" => false, "msg" => "การระบุข้อมูลไม่ครบถ้วน"], 400);
            }

            //! Get user information
            //! Check user
            $users = $this->getUserInfo($username);
            if (count($users) === 0) {
                return response()->json(["state" => false, "msg" => "ไม่มีผู้ใช้งานในระบบ"]);
            }

            //! Check password
            $user = (object) $users[0]; // first row
            $isPass = DB::check($password, $user->Password);
            if (!$isPass) {
                return response()->json(["state" => false, "msg" => "รหัสผ่านไม่ถูกต้อง"]);
            }

            //! Set payload & Encode JWT
            $now = now();
            $payload = [
                'AccountID' => $user->AccountID,
                'Name' => $user->Name,
                'Username' => $user->Username,
                'Role' => $user->Role,
                'iat' => $now->timestamp, //! generate token time
                'exp' => $now->addHours(30)->timestamp //! expire token time
            ];
            $token = $this->jwtUtils->generateToken($payload);

            return response()->json(["state" => true, "msg" => "เข้าสู่ระบบสำเร็จ", "token" => $token]);
        } catch (\Exception $e) {
            return response()->json(["state" => false, "msg" => $e->getMessage()], 500);
        }
    }
}