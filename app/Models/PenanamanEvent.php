<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenanamanEvent extends Model
{
    public $timestamps = false;
    protected $table = 'penanaman_events';
    protected $fillable = ['user_id', 'action', 'note', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
    public function actor() { return $this->belongsTo(User::class, 'user_id'); }
}
