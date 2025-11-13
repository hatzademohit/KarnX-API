<?php

namespace App\Models\FormFieldsData;

use Illuminate\Database\Eloquent\Model;

class RequiredDocumentOption extends Model
{
    protected $table = 'required_document_option';
    protected $fillable = ['name', 'is_active', 'order_by'];
}
