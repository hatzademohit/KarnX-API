<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseModelLoggingTrait;

class InquiryQuoteDetails extends Model
{
    use BaseModelLoggingTrait;
    protected $table = 'inquiry_quote_details';
}
