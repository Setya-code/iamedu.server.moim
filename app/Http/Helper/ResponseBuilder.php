<?php

namespace App\Http\Helper;

use Illuminate\Http\Response;

class ResponseBuilder
{
    public static function result($status="",$messages="",$data=null,$bool=true)
    {
        return response([
            'code' => $status,
            'messages' => $messages,
            'data' => $data,
            'success' => $bool
        ],$status);
           
    }
}

