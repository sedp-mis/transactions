<?php

namespace SedpMis\Transactions\Interfaces;

interface UserResolverInterface
{
    public function getUser($signatory);
}
