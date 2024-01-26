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
        // $this->Line = new LineNotify('Ba9xCyeayKnZxgbDgu3RSCe6jcYPiTMv1uPG8V5E21c'); //! Line Token 1 on 1
        // $this->Line = new LineNotify('xiMZBK86ZnSC8jU6lrv07qaCdjmgIKJyUDpXc4PdNOB'); //! Line Token General room //PPSO
        //* $this->Line = new LineNotify('x0UK5hTkQCkQ57d32vxpIl0h2sTYpoiD4FSUg4TpN5Z'); //! Line Token General room
        //* $this->LineVIPRequest = new LineNotify('Xmz9U3rA1jgMke59nRiu2YCOEWmwFBIZuKMDEloIa4I'); //! Line Token VIP Request room
        //* $this->LineVIP = new LineNotify('zyvtd70EviovzBKXgqRDnWoo0U4H4NkkWiMyevvhWsV'); //! Line Token VIP MRS
        function checkRoom($RoomID)
        {
            //  $query = $this->BookModel->query('SELECT * FROM Rooms WHERE RoomID=?;', [$RoomID]);
            //  return $query->getResult()[0];
        }
    }
}
