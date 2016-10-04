<?php

namespace SedpMis\Transactions\Models;

class DocumentType extends \Eloquent
{
    protected $fillable = [];

    // Document type code constants
    const JV  = 1;
    const CD  = 2;
    const OR_ = 3;
}
