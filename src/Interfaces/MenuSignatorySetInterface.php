<?php

namespace SedpMis\Transactions\Interfaces;

interface MenuSignatorySetInterface
{
    /**
     * Find signatory set of a menu.
     *
     * @param  int $menuId
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException
     * @return \SedpMis\Transactions\Models\Interfaces\SignatorySetInterface
     */
    public function findSignatorySet($menuId);
}
