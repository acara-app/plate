<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Ai\Attributes\AiToolSensitivity;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoOffer;
use App\Enums\DataSensitivity;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class AnalyzePhoto implements Tool
{
    private string $requestId;

    /**
     * @param  array<int, Base64Image>  $images
     */
    public function __construct(private array $images, private ?User $user = null, ?string $requestId = null)
    {
        $this->requestId = $requestId ?? (string) Str::uuid();
    }

    public function name(): string
    {
        return 'analyze_photo';
    }

    public function description(): string
    {
        return 'Analyze a food photo shared by the user. Performs detailed nutritional analysis including calorie count, macronutrients (protein, carbs, fat), and portion size estimation for each food item. Use this tool when the user shares a photo of food or a meal. After receiving the results, present BOTH the per-ingredient breakdown (showing calories and carbs for each item) AND the meal totals. This per-item detail helps users understand where their carbs and calories come from. Present for confirmation before logging with log_health_entry.';
    }

    public function handle(Request $request): string
    {
        if ($this->images === []) {
            return (string) json_encode([
                'error' => 'No image was provided. Please share a photo first.',
            ]);
        }

        $user = $this->user ?? Auth::user();

        $image = $this->images[0];

        $language = null;
        $languageCode = null;

        if ($user instanceof User) {
            ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($user->locale);
        }

        try {
            $analysis = resolve(AnalyzeFoodPhotoAction::class)->handle(
                $image->base64,
                $image->mime ?? 'image/jpeg',
                $language,
                $languageCode,
                new PhotoAnalysisContext($user, null, $this->requestId, 'chat', hash('sha256', $image->base64)),
            );
        } catch (PhotoLimitExceeded $photoLimitExceeded) {
            return (string) json_encode($this->limitReached($photoLimitExceeded));
        }

        return (string) json_encode($analysis->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional context about what the user wants to know about the photo (e.g., "How many calories?", "Is this healthy?"). Defaults to general food analysis.')
                ->required(),
        ];
    }

    /**
     * @return array{error: string, upgrade?: array{plan: string, price: string, url: string}}
     */
    private function limitReached(PhotoLimitExceeded $exception): array
    {
        $offer = $exception->entitlement->canUpgrade ? $exception->entitlement->offer : null;

        if (! $offer instanceof PhotoOffer) {
            return ['error' => $exception->getMessage()];
        }

        return [
            'error' => $exception->getMessage(),
            'upgrade' => [
                'plan' => $offer->name,
                'price' => $offer->formattedPrice.'/month',
                'url' => route('checkout.start', ['product' => $offer->productId]),
            ],
        ];
    }
}
