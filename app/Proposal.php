<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Proposal extends BaseModel
{
    protected $table = 'proposals';
//    use Notifiable;

    protected $dates = ['valid_till'];

    public function items() {
        return $this->hasMany(ProposalItem::class);
    }

    public function currency(){
        return $this->belongsTo(Currency::class, 'currency_id')->withoutGlobalScopes(['enable']);
    }
    public function lead(){
        return $this->belongsTo(Lead::class);
    }

}
