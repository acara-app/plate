<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\User;
use App\Services\ToolRegistry;
use App\Utilities\LanguageUtil;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\ProviderTool;

abstract class SpecialistAgent implements Agent, CanActAsTool, HasTools
{
    use Promptable;

    public function __construct(protected readonly ToolRegistry $toolRegistry) {}

    abstract public function name(): string;

    abstract public function description(): string;

    abstract protected function promptView(): string;

    abstract protected function toolConfigKey(): string;

    public function instructions(): string
    {
        $user = Auth::user();
        $code = $user instanceof User ? ($user->locale ?? 'en') : 'en';
        ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($code);

        return view($this->promptView(), [
            'language' => $language,
            'languageCode' => $languageCode,
        ])->render();
    }

    /**
     * @return array<int, Tool|ProviderTool>
     */
    public function tools(): array
    {
        return $this->toolRegistry->getToolGroup($this->toolConfigKey());
    }
}
