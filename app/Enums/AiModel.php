<?php

declare(strict_types=1);

namespace App\Enums;

enum AiModel: string
{
    case Gemini25Flash = 'gemini-2.5-flash';
    case Gemini25Pro = 'gemini-2.5-pro';
    case Gemini3ProPreview = 'gemini-3-pro-preview';
}
