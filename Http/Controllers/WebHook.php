<?php


namespace Modules\BaleBot\Http\Controllers;


use App\Abstracts\Http\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\BaleBot\Http\Services\BaleBotCURL;

class WebHook extends Controller
{
    private $chat_id = "";
    private $message = "";
    private $data = "";
    private $cmdArr = array();
    private $cancelTitle = "";
    private $token = "";
    private $cart_number = "";
    private $bale_messages = array();

    protected $raw;

    private function getParameters(Request $request)
    {

        $rawContent = $request->getContent();
        $this->raw = json_decode($rawContent,true);
        if($this->raw == null) $this->raw = array();
        $this->getSettings();
    }

    private function getSettings(){
        $this->cmdArr = $this->getPaymentCategory();
        $this->token = "397274748:ym4tUZ9QshinW1TbeQVVqm5AKRzHcuTtpl2H6Tn7";
        $this->cart_number = "6104337754169462";
        $this->bale_messages = array(
            "message_select_operation"=>"لطفا عملیات مورد نظر خود را انتخاب نمایید:",
            "error_username"=>"شماره واحد وارد شده معتبر نمی باشد. لطفا شماره واحد معتبر را وارد نمایید:",
            "message_enter_price"=>"لطفا مبلغ مورد نظر خود را به ریال ارسال فرمایید:",
            "error_operation"=>"عملیات وارد شده معتبر نمی باشد.",
            "error_price"=>"مبلغ وارد شده معتبر نمی باشد. لطفا مجدد وارد نمایید:",
            "success_payment"=>"با تشکر از پرداخت شما. پرداخت شما به مبلغ {price} ریال با شماره پیگیری {track_id} و با شماره سند {transaction_id} با موفقیت ثبت شد. مانده حساب شما {balance} می باشد.",
            "error_payment"=>"در ثبت اطلاعات پرداخت مشکلی بوجود آمده است. لطفا با مدیریت تماس بگیرید.",
            "message_welcome"=>"به بازوی برج رز خوش آمدید.\nلطفا شماره واحد خود را ارسال فرمایید:",
            "message_cancel"=>"شروع مجدد بازو",
            "message_start_again"=>"شروع مجدد بازو"

        );
    }

    /**
     * called webhook from bale.
     *
     * @return Response
     */
    public function send_message(Request $request)
    {

        //http://localhost/akaunting/bale-bot/webhook/send-message
        $this->getParameters($request);

        if(isset($this->raw["callback_query"]["message"]["chat"]["id"]))
            $this->chat_id = $this->raw["callback_query"]["message"]["chat"]["id"];
        else if(isset($this->raw["message"]["chat"]["id"]))
            $this->chat_id = $this->raw["message"]["chat"]["id"];


        if(isset($this->raw["callback_query"]["data"]))
            $this->data = $this->raw["callback_query"]["data"];

        if(isset($this->raw["message"]["text"]))
            $this->message = $this->convertPE($this->raw["message"]["text"]);

        if(isset($this->raw["message"]["successful_payment"]))
            $this->data = "successful_payment";


        $result =  "";
        if($this->chat_id != "" && ($this->message != "" || $this->data != "")){

            $bbCurl = new BaleBotCURL($this->token);


            if($this->message == "/start" || $this->data == "cancel") $this->removeDBStatus($this->chat_id);
            $chat_record = $this->getDBStatus($this->chat_id);
            if($chat_record){
                if((int)$chat_record->status == 0){
                    $reference = (int)$this->message;
                    $contact_id = $this->getContactID($reference);
                    if($contact_id !== false){
                        $msgArr = $this->cmdArr;
                        $msgArr["cancel"] = $this->bale_messages["message_cancel"];
                        $result = $bbCurl->sendMessage($this->chat_id,$this->bale_messages["message_select_operation"],$msgArr);
                        $this->updateDBStatus($this->chat_id,array("reference"=>$reference,"contact_id"=>(int)$contact_id),1);
                    }else{
                        $result = $this->sendError($this->bale_messages["error_username"]);
                    }
                }else if((int)$chat_record->status == 1){
                    $json_data = json_decode($chat_record->json_data,true);
                    if(array_key_exists($this->data, $this->cmdArr)){
                        $json_data["payment_type"] = $this->data;
                        $result = $bbCurl->sendMessage($this->chat_id,$this->bale_messages["message_enter_price"],array("cancel"=>$this->bale_messages["message_cancel"]));
                        $this->updateDBStatus($this->chat_id,$json_data,2);
                    }else{
                        $result = $this->sendError($this->bale_messages["error_operation"]);
                    }
                }else if((int)$chat_record->status == 2){
                    $price = (int)$this->message;
                    if($price > 0){
                        $json_data = json_decode($chat_record->json_data,true);
                        $json_data["secret_key"] = rand(100000,1000000);
                        $result = $bbCurl->sendInvoice($this->chat_id,(int)$this->message,$json_data["reference"],$this->cmdArr[$json_data["payment_type"]],$json_data["secret_key"],$this->cart_number,"");
                        $this->updateDBStatus($this->chat_id,$json_data,3);
                    }else{
                        $result = $this->sendError($this->bale_messages["error_price"]);
                    }
                }else if((int)$chat_record->status == 3){
                    $json_data = json_decode($chat_record->json_data,true);
                    if($this->data == "successful_payment"){
                        $json_data["pay_amount"] = $this->raw["message"]["successful_payment"]["total_amount"];
                        $json_data["pay_track_id"] = $this->raw["message"]["successful_payment"]["provider_payment_charge_id"];
                        $json_data["pay_date"] = (string)$this->raw["message"]["date"];
                        $sec_key = (int)$this->raw["message"]["successful_payment"]["invoice_payload"];
                        if($sec_key == (int)$json_data["secret_key"]){
                            $this->updateDBStatus($this->chat_id,$json_data,4);
                            $tempMessage = $this->bale_messages["success_payment"];
                            $tempMessage = str_replace("{price}", $json_data["pay_amount"], $tempMessage);
                            $tempMessage = str_replace("{track_id}", $json_data["pay_track_id"], $tempMessage);
                            $tempMessage = str_replace("{transaction_id}", "0", $tempMessage);
                            $tempMessage = str_replace("{balance}", "0", $tempMessage);

                            $result = $bbCurl->sendMessage($this->chat_id,$tempMessage
                                ,array("cancel"=>$this->bale_messages["message_start_again"]));
                        }else{
                            $result = $bbCurl->sendMessage($this->chat_id,$this->bale_messages["error_payment"],array("cancel"=>$this->bale_messages["message_cancel"]));
                        }
                    }
                }
            }else{
                $result = $bbCurl->sendMessage($this->chat_id,$this->bale_messages["message_welcome"],array("cancel"=>$this->bale_messages["message_cancel"]));
                $this->updateDBStatus($this->chat_id,array(),0);
            }

        }

        //$this->db->wh_log(json_encode(array("request"=>$this->raw,"response"=>$result,"chat_id"=>$this->chat_id,"message"=>$this->message,"data"=>$this->data)));


        return response()->json($result,200);
    }


    private function setPayment($paid_at,$amount,$account_id,$contact_id,$category_id,$reference){
        DB::table('transactions')->insert([
            'company_id' =>  '1',
            'type' =>  'income',
            'paid_at' =>  $paid_at,
            'amount' =>  $amount,
            'currency_code' =>  'IRR',
            'currency_rate' =>  1,
            'account_id' =>  $account_id,
            'contact_id' =>  $contact_id,
            'category_id' =>  $category_id,
            'payment_method' =>  'offline-payments.bank_transfer.2',
            'reference' =>  $reference,
            'parent_id' =>  0,
            'created_from' =>  'core::ui',
            'created_by' =>  1,
            'reconciled' =>  0,
            'created_at' =>  Carbon::now()

        ]);


    }

    private function getPaymentCategory(){
        $table = DB::table('categories')
            ->where('company_id', 1)
            ->where('type', 'income')
            ->where('enabled', 1)
            ->select(['id','name'])
            ->get();

        $rows = array();
        foreach ($table as $item) {
            $rows["cmd" . $item->id] = $item->name;
        }


        return $rows;
    }

    public function getDBStatus($chat_id){
        $table = DB::table('bale_bot')
            ->where('chat_id', (int)$chat_id)
            ->where('archive', 0)
            ->get();
        if(sizeof($table) == 0) return false;
        return $table[0];
    }

    public function getContactID($reference){
        $table = DB::table('contacts')
            ->where('reference', $reference)
            ->where('type', 'customer')
            ->where('enabled', 1)
            ->get();
        if(sizeof($table) == 0) return false;
        return $table[0]->id;
    }

    public function updateDBStatus($chat_id,$obj,$status){
        if($status == 0) {
            DB::table('bale_bot')->insert([
                'chat_id' =>  (int)$chat_id,
                'json_data' => json_encode($obj),
                'start_date' => Carbon::now(),
                'status' => (int)$status,
                'archive' => 0,
                'created_at' => Carbon::now()
            ]);
        }else {
            $affected = DB::table('bale_bot')
                ->where('chat_id', (int)$chat_id)
                ->where('archive', 0)
                ->update(['json_data' => json_encode($obj),'status' => (int)$status,'updated_at' => Carbon::now()]);

        }
    }

    public function removeDBStatus($chat_id){
        $affected = DB::table('bale_bot')
            ->where('chat_id', (int)$chat_id)
            ->where('archive', 0)
            ->update(['archive' => 1,'updated_at' => Carbon::now()]);
    }

    private function sendError($msg){
        $msgArr = array();
        $msgArr["cancel"] = $this->bale_messages["message_cancel"];
        $bbCurl = new BaleBotCURL($this->token);
        $result = $bbCurl->sendMessage($this->chat_id,$msg,$msgArr);
    }



    private function convertPE($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

}
