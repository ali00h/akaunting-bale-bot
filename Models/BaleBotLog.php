<?php

namespace Modules\BaleBot\Http\Model;

use App\Abstracts\Model;
use App\Models\Document\Document;
use App\Traits\Currencies;
use App\Traits\Media;
use Bkwld\Cloner\Cloneable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BaleBotLog extends Model
{
    protected $table = 'bale_bot';
    public $timestamps = true;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'chat_id', 'json_data', 'start_date', 'status', 'archive','created_at','updated_at'];


}
