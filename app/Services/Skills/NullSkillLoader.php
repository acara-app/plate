<?php

declare(strict_types=1);

namespace App\Services\Skills;

use App\Contracts\Skills\LoadsSkills;
use App\Data\Skills\SkillContent;
use App\Data\Skills\SkillSummary;
use Illuminate\Support\Collection;

final readonly class NullSkillLoader implements LoadsSkills
{
    /**
     * @return Collection<int, SkillSummary>
     */
    public function loadAll(): Collection
    {
        return collect();
    }

    public function loadByName(string $name): ?SkillContent
    {
        return null;
    }
}
