<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;


class CostsImput extends Model
{
    use HasFactory;

    protected $table = 'costs_imputs';

    public static function boot()
    {
        parent::boot();

        CostsImput::deleted(function ($costsimput) {
            $file = $costsimput->filename;
            if (File::isFile($file)) {
                File::delete($file);
            }
        });
    }
}
