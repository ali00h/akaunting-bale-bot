<?php


namespace Modules\BaleBot\Http\Services;


class BaleBotCURL
{
    private $token = "";
    function __construct($token) {
        $this->token = $token;
    }

    public function sendMessage($chat_id,$msg,$keyboards = array()){

        $data = array();
        $data["chat_id"] = $chat_id;
        $data["text"] = $msg;
        $keys = array();
        foreach($keyboards as $key => $value){
            $keys[] = array("text"=>$value,"callback_data"=>$key);
        }
        $data["reply_markup"] = array();
        $data["reply_markup"]["inline_keyboard"] = array();
        $data["reply_markup"]["inline_keyboard"][] = $keys;

        //echo json_encode($data);
        //exit();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tapi.bale.ai/bot' . $this->token . '/sendMessage',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/plain'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function sendInvoice($chat_id,$price,$title,$desc,$secret_key,$cart_number,$photo_url){
        //"https://www.rose-tower.ir/wp-content/uploads/2021/12/logo.jpg"
        $data = array();

        $data = [
            "chat_id" => $chat_id,
            "title" => (string)$title,
            "description" => $desc,
            "payload" => (string)$secret_key,
            "provider_token" => $cart_number,
            "prices" => [
                [
                    "label" => "مبلغ به ریال",
                    "amount" => $price
                ]
            ]

        ];

        if($photo_url != ""){
            $data["photo_url"] = $photo_url;
        }

        //echo json_encode($data);
        //exit();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tapi.bale.ai/bot' . $this->token . '/sendInvoice',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: text/plain'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }

    public function setWebhook(){
        $webhook_url = route('bale-bot-webhook-send-message');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://tapi.bale.ai/bot' . $this->token . '/setWebhook',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => array('url' => $webhook_url),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }
}
