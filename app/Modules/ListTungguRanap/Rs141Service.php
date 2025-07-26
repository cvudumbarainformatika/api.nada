<?php

declare(strict_types=1);

namespace App\Modules\ListTungguRanap;

class Rs141Service
{
    /** @var Rs141Repos */
    private $repository;

    /**
     * @param Rs141Repository $repository
     */
    public function __construct(Rs141Repo $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->repository->getAll();
    }

    /**
     * @param int
     * @return Rs141
     */
    // public function get(int $id): Rs141
    // {
    //     return $this->repository->get($id);
    // }

    // /**
    //  * @param array $unvalidatedData
    //  * @return Rs141
    //  */
    // public function update(array $unvalidatedData): Rs141
    // {
    //     return $this->repository->update(Rs141Mapper::mapFrom(array_merge($unvalidatedData)));
    // }
}