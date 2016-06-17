<?php

namespace SedpMis\Transactions\Models;

class TransactionApproval extends \Eloquent
{
	protected $fillable = ['transaction_id', 'user_id', 'signatory_action_id', 'status', 'remarks'];
}
