<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wildside\Userstamps\Userstamps;

class PengunjungWisata extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected $fillable = [
        "year",
        "month",
        "id_pengelola_wisata",
        "number_of_visitors",
        "gross_income",
        "status",
        "approved_by_kasi_at",
        "approved_by_cdk_at",
        "rejection_note",
        "created_by",
        "updated_by",
        "deleted_by"
    ];

    protected $table = "pengunjung_wisata";

    public function pengelolaWisata()
    {
        return $this->belongsTo(PengelolaWisata::class, 'id_pengelola_wisata');
    }
}
