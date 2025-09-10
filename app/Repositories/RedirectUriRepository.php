<?php declare(strict_types=1);

namespace App\Repositories;

use App\Models\RedirectUri;

class RedirectUriRepository extends Repository
{
    public function __construct()
    {
        parent::__construct(RedirectUri::class);
    }

    public static function make(): self
    {
        return new self();
    }

    public function findByCode(string $code): ?RedirectUri
    {
        return $this->db
            ->where('code', $code)
            ->first();
    }
}
