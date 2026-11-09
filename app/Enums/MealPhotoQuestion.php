<?php

declare(strict_types=1);

namespace App\Enums;

enum MealPhotoQuestion: string
{
    case Subject = 'subject';
    case Face = 'face';
    case PersonalDetails = 'personal_details';
    case ImageQuality = 'image_quality';
    case FoodType = 'food_type';
    case Cuisine = 'cuisine';
    case DishType = 'dish_type';
}
