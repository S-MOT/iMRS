<?php

namespace App\Http\Libraries\JWT;

define("PRIVATE_KEY", "-----BEGIN RSA PRIVATE KEY-----
MIIBOQIBAAJAWWkdqo0cjrsASYYiy3HZWpTAGrjJytIGXsIruG9bHoEtILuIF/8F
9uUoGs2aq8sCSPrylDZcaIuv/udduMG1DwIDAQABAkAm0P/UH1cakAzb3qQUduTJ
8nJEJ51TtEKXuOyKMa35W8/RLDogZIV0kGhtKIKpT6rbY5g5dPO2UMhBqOCpxFFh
AiEAn7UpjyFopNBT4Emc6MwjC0r1r2sEALdievlbAW02gj0CIQCPUZ7mPhzSugog
pTzKCve31mi1GOHotYwfJ0wPiiiFOwIgWMlMS2gVVtsCZkRGWR1ztgl8QINL/GH2
+8q4jzh5/zkCIEQnHzYEIXbAC2Lc0NyPfJ9KcX5097DO7HRAHrZhD8XTAiEAjyva
BV6ZOx0SpOx5/5nV1vQ6tLPH7tyyWMvU6963/FI=
-----END RSA PRIVATE KEY-----");

// define("PRIVATE_KEY", "Pikachu","Satochi");

class JWTUtils
{
     public function generateToken($payload)
     {
          $token = JWT::encode($payload, PRIVATE_KEY, 'HS256');
          return $token;
     }

     public function verifyToken($header)
     {
          $token = null;
          // extract the token from the header
          if (!empty($header)) {
               if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                    $token = $matches[1];
               }
          }

          // check if token is null or empty
          if (is_null($token) || empty($token)) {
               return (object)['state' => false, 'msg' => 'Access denied', 'decoded' => []];
          }

          try {
               $decoded = JWT::decode($token, new Key(PRIVATE_KEY, 'HS256'));
               return (object)['state' => true, 'msg' => 'OK', 'decoded' => $decoded];
          } catch (\Exception $e) {
               return (object)['state' => false, 'msg' => $e->getMessage(), 'decoded' => []];
          }
     }
}
