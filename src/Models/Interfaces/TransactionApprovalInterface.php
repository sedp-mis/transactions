<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface TransactionApprovalInterface
{
    public function signatoryAction();
    public function signatory();
    public function user();
    public function job();
}
