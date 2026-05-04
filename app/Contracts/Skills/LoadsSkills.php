<?php

declare(strict_types=1);

namespace App\Contracts\Skills;

use App\Data\Skills\SkillContent;
use App\Data\Skills\SkillSummary;
use Illuminate\Support\Collection;

interface LoadsSkills
{
    /**
     * @return Collection<int, SkillSummary>
     */
    public function loadAll(): Collection;

    public function loadByName(string $name): ?SkillContent;
}
