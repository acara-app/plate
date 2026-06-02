<?php

declare(strict_types=1);

namespace App\Workflows\MealPlanPipeline;

use App\Data\DayMealsData;
use App\Workflows\SaveDayMealsActivity;
use Closure;

final readonly class SaveDayMealsStep
{
    public function __construct(private SaveDayMealsActivity $saver) {}

    /**
     * @param  Closure(MealPlanDayContext): MealPlanDayContext  $next
     */
    public function handle(MealPlanDayContext $context, Closure $next): MealPlanDayContext
    {
        if ($context->dayMeals instanceof DayMealsData) {
            $this->saver->handle($context->mealPlan, $context->dayMeals, $context->dayNumber);
        }

        return $next($context);
    }
}
