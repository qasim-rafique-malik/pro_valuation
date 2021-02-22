<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeadCategory extends Model
{
    protected $table = 'lead_category';
    protected $default = ['id','lead_name'];
}
