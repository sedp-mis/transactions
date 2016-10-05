<?php

namespace SedpMis\Transactions\Interfaces;

interface MenuSignatorySetInterface
{
    /**
     * Find signatory set of a menu.
     *
     * @param  int $menuId
     * @return \SedpMis\Transactions\Models\Interfaces\SignatorySetInterface
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \RuntimeException
     */
    public function findSignatorySet($menuId);
}
