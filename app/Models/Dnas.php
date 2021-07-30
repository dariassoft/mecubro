<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dnas extends Model
{
    use HasFactory;
    protected $fillable = ['dna', 'result'];
}
