<?php

namespace App\Libraries;

class LineNotify
{
    private string $LINE_TOKEN;
    public function __construct(string $TOKEN)
    {
        $this->LINE_TOKEN = $TOKEN;
    }

    public function sendMessage(string $message)
    {
        $queryData = array('message' => $message);
        $queryData = http_build_query($queryData, '', '&');
        $headerOptions = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . "Authorization: Bearer " . $this->LINE_TOKEN . "\r\n"
                    . "Content-Length: " . strlen($queryData) . "\r\n",
                'content' => $queryData
            ),
        );
        $context = stream_context_create($headerOptions);
        $result = file_get_contents("()", FALSE, $context);
        $res = json_decode($result);
        return $res;
    }
    //      https://notify-api.line.me/api/notify
}
