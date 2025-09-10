<?php declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

class Repository
{
    protected Model $db;

    public function __construct(string $model)
    {
        $this->db = app($model);
    }

}
