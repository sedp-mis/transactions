<?php

namespace SedpMis\Transactions\Models;

class DocumentSignatory extends \BaseModel
{
    protected $fillable = [];

    // protected $attributes = [
    //     'is_actual' => 0,
    // ];

    /**
     * Signatory pdf format.
     *
     * @return array
     */
    public function signatoryPdfFormat()
    {
        return [

        ];
    }
}
