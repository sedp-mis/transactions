<?php

namespace SedpMis\Transactions\Models;

class User extends \Eloquent
{
    protected $appends = ['full_name'];

    public function getFullNameAttribute()
    {
        return "{$this->lname}, {$this->fname} {$this->mname}";
    }

    public static function findByBranchJob($branchId, $jobId)
    {
        $query = static::where('job_id', $jobId);

        if (is_array($branchId)) {
            return $query->whereIn('branch_id', $branchId)->get();
        }

        return $query->where('branch_id', $branchId)->first();
    }

    public function job()
    {
        return $this->belongsTo('Job');
    }
}
