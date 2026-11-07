<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\AnalyzeFoodPhotoAction;
use App\Ai\Attributes\AiToolSensitivity;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoOffer;
use App\Enums\DataSensitivity;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        return 'Analyze a food photo shared by the user. Performs detailed nutritional analysis including calorie count, macronutrients (protein, carbs, fat), and portion size estimation for each food item. Use this tool when the user shares a photo of food or a meal. After receiving the results, present BOTH the per-ingredient breakdown (showing calories and carbs for each item) AND the meal totals. This per-item detail helps users understand where their carbs and calories come from. Present for confirmation before logging with log_health_entry. If the result has an `error`, the photo was not analyzed: explain why in one or two sentences and do not call this tool again for that photo. If it also has a `plan`, you may mention that plan once with its price and link.';
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
            return $this->notAnalyzed($photoLimitExceeded->getMessage(), $photoLimitExceeded->entitlement);
        } catch (UsageLimitExceededException $usageLimitExceeded) {
            return $this->notAnalyzed('Your AI usage limit is used up for now, so this photo was not analyzed. It resets at '.$usageLimitExceeded->resetsAt->toIso8601String().'.');
        } catch (HttpExceptionInterface $unavailable) {
            return $this->notAnalyzed($unavailable->getMessage());
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

    private function notAnalyzed(string $reason, ?PhotoEntitlement $entitlement = null): string
    {
        $reply = ['error' => $reason];

        if ($entitlement instanceof PhotoEntitlement && $entitlement->canUpgrade && $entitlement->offer instanceof PhotoOffer) {
            $reply['plan'] = [
                'name' => $entitlement->offer->name,
                'price' => $entitlement->offer->formattedPrice.' per month',
                'url' => route('checkout.start', ['product' => $entitlement->offer->productId, 'returning' => 1]),
            ];
        }

        return (string) json_encode($reply, JSON_UNESCAPED_SLASHES);
    }
}
