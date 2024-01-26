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
                "status" => "error",
                "message" => $e->getMessage(),
                "data" => [],
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

            $splitAuthorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
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
            $isPass = $request->oldPassword === $request->Password;
            if (!$isPass) {
                return response()->json([
                    "state" => false,
                    "msg" => "รหัสผ่านไม่ถูกต้อง",
                ]);
            }

            //! Hash new password & Update to DB

            // $data = DB::make($newPassword);
            // $this->AdminModel->update($decoded->AccountID, ['Password' => $data]);

            //  $hash = $this->Bcrypt->hash($newPassword);
            //  $this->AdminModel->update($decoded->AccountID, ['Password' => $hash]);

            return response()->json([
                "state" => true, "msg" => "แก้ไขรหัสผ่านสำเร็จ",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage(),
                "data" => [],
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

            $splitAuthorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return response()->json([
                "state" => false,
                "msg" => "Header[Authorization] ผิดพลาด",
            ], 400);


            $jwtToken = $splitAuthorize[1];
            // //! Decode token to payload
            $decoded = JWT::decode($jwtToken, new Key(PRIVATE_KEY, 'HS256'));
            if ($decoded->Role != 'admin') return response()->setJSON(["state" => false, "msg" => "ไม่มีสิทธิ์การร้องขอข้อมูลนี้"]);
            // //**! ./Request validation

            $result = DB::table("Booking")->selectRaw('*')->where('RoomLevel', '=', 'vip')->get();
            return response()->json([
                "state" => true, "msg" => "แก้ไขรหัสผ่านสำเร็จ",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => $e->getMessage(),
                "data" => $result,
            ], 500);
        }
    }

    //TODO [POST] /admin/admin-approvement
    public function adminApprovement(Request $request)
    {
        //         try {
        //             $authorize = $request->header("Authorization");
        //             $jwt = $this->jwtUtils->verifyToken($authorize);
        //             if (!$jwt->state) return response()->json([
        //                 "state" => false,
        //                 "msg" => "ไม่มี TOKEN สำหรับการยืนยันตัวตน",
        //             ], 400);

        //             $splitAuthorize = $request->header("Authorization");
        //             $jwt = $this->jwtUtils->verifyToken($authorize);
        //             if (count($splitAuthorize) !== 2 || $splitAuthorize[0] !== 'Bearer') return response()->json([
        //                 "state" => false,
        //                 "msg" => "Header[Authorization] ผิดพลาด",
        //             ], 400);

        //             $jwtToken = $splitAuthorize[1];
        //             //! Decode token to payload
        //             $decoded = JWT::decode($jwtToken, new Key(PRIVATE_KEY, 'HS256'));
        //             if ($decoded->Role != 'admin') return response()->setJSON(["state" => false, "msg" => "ไม่มีสิทธิ์การร้องขอข้อมูลนี้"]);

        //             $rules = [
        //                 "BookID"      => $request->BookID,
        //                 "isApproved"  => $request->isApproved,
        //             ];
        //             $validator = Validator::make($request->all(), $rules);
        //             if ($validator->fails()) {
        //                 return response()->json([
        //                     "state" => false,
        //                     "msg" => "การระบุข้อมูลไม่ครบถ้วน",
        //                 ], 400);
        //             }

        //             $result = DB::table("Booking")->selectRaw('*')
        //               ->where ('RoomID',$RoomID)
        //               ->get();
        //       $books = $this->BookModel->query("SELECT * FROM vBooking WHERE BookID=?", [$BookID])->getResult();
        //       $bookInfo = (object)$books[0];
        //       //! Block Reserve exist
        //       if ($isApproved) {
        //         $sqlBlockReserve = DB::table("Booking")->selectRaw("CONVERT(varchar, StartDatetime, 20) AS StartDatetime, 
        //         CONVERT(varchar, StartDatetime, 20) AS EndDatetime")
        //         ->where ('RoomID', $RoomID)
        //         ->where ('Action','!=','vip')
        //         ->where ('RoomLevel','=','vip')
        //         ->get();

        //         $reservedList = BookModel->selectRaw($sqlBlockReserve)->where('RoomID',$bookInfo->RoomID)->get();
        //         $arrReserved = $reservedList->toArray();
        //         $startTimestamp = (new \DateTime($bookInfo->StartDatetime))->getTimestamp();
        //         $endTimestamp = (new \DateTime  ($bookInfo->EndDatetime))->getTimestamp();
        //         $nowTimestamp = (new \DateTime  ())->getTimestamp();
        //     // //! S >= Now

        //         if ($nowTimestamp >= $startTimestamp) 
        //             return response()->setJSON(["state" => false, "msg" => "ไม่สามารถจองห้องประชุมย้อนหลังได้"]);
        //             foreach ($reservedList as $reserved) {
        //                 $dbStartTimestamp = (new \DateTime($reserved->StartDatetime))->getTimestamp();
        //                 $dbEndTimestamp = (new \DateTime($reserved->EndDatetime))->getTimestamp();
        //     //             //! Start >= S > End
        //                 if ($startTimestamp >= $dbStartTimestamp && $startTimestamp < $dbEndTimestamp) {
        //                      array_push($arrReserved, 1);
        //                      break;
        //                 }
        //                 //! Start > E >= End
        //                 if ($endTimestamp > $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp) {
        //                      array_push($arrReserved, 1);
        //                      break;
        //                 }
        //                 //! S <= Start & E >= End
        //                 if ($startTimestamp <= $dbStartTimestamp && $endTimestamp >= $dbEndTimestamp) {
        //                      array_push($arrReserved, 1);
        //                      break;
        //                 }
        //                 //! S >= Start & E <= End
        //                 if ($startTimestamp >= $dbStartTimestamp && $endTimestamp <= $dbEndTimestamp) {
        //                      array_push($arrReserved, 1);
        //                      break;
        //                 }
        //            }

        //            if (count($arrReserved) !== 0) return response()->setJSON(["state" => false, "msg" => "ห้องประชุมนี้ได้ถูกจองไว้แล้วในช่วงเวลานี้"]);
        //     // if (count($reservedList) !== 0) return $this->response->setJSON(["state" => false, "msg" => "คุณอย่าลองของนะ!!!"]);
        //     // ! ./Block Reserve exist


        // //   ! Update Action & Status
        //           $data = [
        //                        'Action' => $isApproved ? "booking" : "canceled",
        //                        'Status' => $isApproved ? "approved" : "canceled"
        //                   ];

        //                   $this->BookModel->update($BookID, $data);

        //                 //   //! Admin approved
        //                   if ($isApproved && $bookInfo->Status == 'pending') {
        //                        $allReserved = $this->getRoomReserved($bookInfo->RoomID, $bookInfo->StartDatetime);

        //                        $lineMessage = "\n" . "🌟 " . $bookInfo->RoomName . " (" . $bookInfo->Amount . " ที่นั่ง) 🌟\n"
        //                             . "วันที่ " . (new \DateTime($bookInfo->StartDatetime))->format('d/m/Y') . "\n\n"
        //                             . "คิวจองห้องประชุม\n";

        //                        foreach ($allReserved as $reserved) {
        //                             $lineMessage .= (new \DateTime($reserved->StartDatetime))->format('H:i') . " - " . (new \DateTime($reserved->EndDatetime))->format('H:i') . " น. (" . $reserved->Name . ")\n";
        //                        }

        //                        $lineMessage .= "\n";
        //                        $lineMessage .= "ต้องการจองห้องประชุม\n";
        //                        $lineMessage .= "https://snc-services.sncformer.com/SncOneWay/";

        //                        $this->Line->sendMessage($lineMessage);
        //                   }

        //           $result = DB::table("Booking")->selectRaw('*')->where ('RoomLevel', '=', 'vip')->get();
        //           return response()->json([
        //               "state" => true, 
        //               "msg" => $isApproved ? "อนุมัติการจองห้องประชุมสำเร็จ" : "ยกเลิกการจองห้องประชุมสำเร็จ"
        //             ], 201);

        //             } catch (\Exception $e) {
        //                 return response()->json([
        //                 "status" => false,
        //                 "message" => $e->getMessage(),
        //                 "data" => $result,
        //             ], 500);
        //             }
        //         }
    }
}
