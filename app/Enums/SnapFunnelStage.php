<?php

declare(strict_types=1);

namespace App\Enums;

enum SnapFunnelStage: string
{
    case UploadSelected = 'snap_to_track_upload_selected';
    case UploadRejected = 'snap_to_track_upload_rejected';
    case AnalysisRequested = 'snap_to_track_analysis_requested';
    case AnalysisSucceeded = 'snap_to_track_analysis_succeeded';
    case AnalysisFailed = 'snap_to_track_analysis_failed';
    case ResultViewed = 'snap_to_track_result_viewed';
    case OfferViewed = 'snap_to_track_offer_viewed';
    case SaveClicked = 'snap_to_track_save_click';
    case UpgradeClicked = 'snap_to_track_upgrade_click';
    case AuthCompleted = 'snap_to_track_auth_completed';
    case MealSaved = 'snap_to_track_meal_logged';
    case CheckoutClicked = 'snap_to_track_checkout_click';
    case CheckoutStarted = 'snap_to_track_checkout_started';
    case CheckoutFailed = 'snap_to_track_checkout_failed';
    case CheckoutReturned = 'snap_to_track_checkout_returned';
    case CheckoutCancelled = 'snap_to_track_checkout_cancelled';
    case FirstPayment = 'snap_to_track_first_payment';

    public function label(): string
    {
        return match ($this) {
            self::UploadSelected => 'Photo selected',
            self::UploadRejected => 'Photo rejected',
            self::AnalysisRequested => 'Analysis requested',
            self::AnalysisSucceeded => 'Analysis finished',
            self::AnalysisFailed => 'Analysis failed',
            self::ResultViewed => 'Result viewed',
            self::OfferViewed => 'Offer viewed',
            self::SaveClicked => 'Save clicked',
            self::UpgradeClicked => 'Upgrade clicked',
            self::AuthCompleted => 'Signed up or logged in',
            self::MealSaved => 'Meal saved',
            self::CheckoutClicked => 'Checkout clicked',
            self::CheckoutStarted => 'Checkout opened',
            self::CheckoutFailed => 'Checkout failed',
            self::CheckoutReturned => 'Returned from checkout',
            self::CheckoutCancelled => 'Checkout cancelled',
            self::FirstPayment => 'First payment',
        };
    }

    public function definition(): string
    {
        return match ($this) {
            self::UploadSelected => 'A guest picked a photo and its upload started. Once per upload.',
            self::UploadRejected => 'The photo, the bot check or a rate limit stopped the upload before analysis (reason). Error state.',
            self::AnalysisRequested => 'The server accepted the photo and queued its analysis. Once per analysis request; a retry is a new request marked retry.',
            self::AnalysisSucceeded => 'The analysis finished with at least one food item. Once per analysis request.',
            self::AnalysisFailed => 'The analysis ended without a usable result (failure). Error state. Once per analysis request.',
            self::ResultViewed => 'The guest result page showed an unexpired, unsaved meal. Once per analysis; reloads do not repeat it.',
            self::OfferViewed => 'A paid plan offer was on screen (placement, plan); clicking it also counts as seeing it. Once per placement and result.',
            self::SaveClicked => 'The guest chose to save the meal (auth_path, placement): a Save button, or a Log in link that continues to saving. Recorded before the page navigates away. Once per result.',
            self::UpgradeClicked => 'The guest chose a paid plan from an offer (placement, plan), recorded before the page navigates away. Once per placement and result.',
            self::AuthCompleted => "The journey's browser finished sign-up or log-in (method). Once per account.",
            self::MealSaved => 'A meal from the public Snap to Track result was saved to the food log. Once per meal.',
            self::CheckoutClicked => 'The plan page asked the server to open a Stripe checkout (plan, interval). Every request counts as a raw event.',
            self::CheckoutStarted => 'Stripe created the checkout session and the browser was sent to it (plan, interval). Once per checkout request.',
            self::CheckoutFailed => 'The checkout could not open (reason). Error state.',
            self::CheckoutReturned => 'The browser came back to the success page (entitlement). Not proof of payment, and not needed for one. Once per checkout.',
            self::CheckoutCancelled => 'The browser came back from Stripe through the cancel link. Once per checkout.',
            self::FirstPayment => "A signed Stripe webhook confirmed the paid invoice that created a Plate customer's subscription (plan, amount, live). Once per subscription; replays, renewals, trial starts and unpaid invoices never add another.",
        };
    }
}
