# Snap to Track Funnel

How the guest Snap to Track journey is measured, from a selected photo to a first payment confirmed by Stripe. The server records each stage in the app database (`snap_journeys` and `snap_analytics_events`), so ad blockers and browsers that never return from Stripe don't hide conversions.

## Running the report

```bash
php artisan snap:funnel-report                        # journeys started in the last 7 days
php artisan snap:funnel-report --days=30 --by=placement
php artisan snap:funnel-report --definitions          # what each stage means
php artisan snap:funnel-report --json                 # everything, for further analysis
```

Run it in production the same way as `billing:usage-report`. `--by` accepts `device`, `source`, `placement` or `plan`. Staff journeys and Stripe test-mode payments are left out unless you pass `--include-internal`. Conversion and checkout placements only count journeys whose window has closed, so give them a longer period such as `--days=30`.

## Journeys

- A journey is one guest browser's attempt, identified by a random journey ID. It starts with the first stage a guest reaches on the public tool: usually a selected photo, or the limit wall for a returning visitor whose free scans are used up. Journeys are only recorded while photo plans are on sale, so community installs record nothing.
- The browser's existing `photo_guest` cookie links later stages to the journey. Only its SHA-256 hash is stored. Signing up or logging in attaches the account. Checkout sends the journey ID, never the cookie, to Stripe as subscription metadata (`snap_journey`), so the payment webhook can find the journey without a browser.
- A guest can buy Starter without an account, from the result after a free scan or from the scan limit. The server first records a plan purchase, then opens Stripe Checkout with the purchase and journey IDs as subscription metadata (`plan_purchase`, `snap_journey`). Stripe collects the email. When Stripe confirms the first invoice, the purchase is marked paid and an activation link goes to the checkout email. The buyer can use the link on any device, or activate with a matching verified Gmail or Google Workspace account in the browser that started checkout. A successful claim attaches the plan and links the journey to the account.
- The conversion window is 7 days from the journey's first stage (`snap.journey_days`). After that, the same browser starts a new journey. A first payment still counts when Stripe confirms it after the window, as long as its checkout was opened inside the window.
- Each journey keeps:
  - the device (mobile or desktop) of the first page in the browser session;
  - the acquisition source, medium and campaign from that first page's UTM parameters or the referring site's host, or `direct`;
  - whether it belongs to a staff account listed in `ADMIN_EMAILS`.

## Stages

| Stage | Event | Meaning |
| --- | --- | --- |
| Photo selected | `snap_to_track_upload_selected` | A guest picked a photo and its upload started. |
| Photo rejected | `snap_to_track_upload_rejected` | The photo, the bot check or a rate limit stopped the upload before analysis. Error state. |
| Analysis requested | `snap_to_track_analysis_requested` | The server accepted the photo and queued its analysis. A retry is a new request marked `retry`. |
| Analysis finished | `snap_to_track_analysis_succeeded` | The analysis found at least one food item. |
| Analysis failed | `snap_to_track_analysis_failed` | The analysis ended without a usable result (`no_food`, `timeout`, `provider_error`, `start_failed`, …). Error state. |
| Result viewed | `snap_to_track_result_viewed` | The guest result page showed an unexpired, unsaved meal. |
| Offer viewed | `snap_to_track_offer_viewed` | A paid plan offer was on screen: `result`, `result_bar` (the result sheet) or `wall` (scan limit). Clicking it also counts as seeing it. Guest offers record `free_scans_used`; on `result_bar`, `1` is the free-or-Starter choice after the first scan and `2` is the offer after both free scans. Since 2026-11-26 the plan card (`result`) also shows after the first scan, so `result` can carry `1` too. |
| Save clicked | `snap_to_track_save_click` | The guest chose to save the meal: a Save button (`auth_path` `register`) or a Log in link, which also continues to saving (`login`). Recorded before the page navigates away. |
| Upgrade clicked | `snap_to_track_upgrade_click` | The guest chose a paid plan from an offer, recorded before the page navigates away. It carries the offer's `free_scans_used`. |
| Signed up or logged in | `snap_to_track_auth_completed` | The journey's browser finished sign-up or log-in (`method`: `register` or `login`). |
| Meal saved | `snap_to_track_meal_logged` | A meal from the public result was saved to the food log. |
| Checkout clicked | `snap_to_track_checkout_click` | The plan page asked a signed-in member's server to open a Stripe checkout. |
| Checkout opened | `snap_to_track_checkout_started` | Stripe created the checkout session and the browser was sent to it. Guest checkouts also record the `placement` (`result`, `result_bar` or `wall`) and the offer's `free_scans_used`. A repeat click reopens the same session instead of creating another. |
| Checkout failed | `snap_to_track_checkout_failed` | The checkout could not open (`already_subscribed` or `error`). Error state. |
| Returned from checkout | `snap_to_track_checkout_returned` | The browser came back to the success page. This is not proof of payment, and a payment doesn't need it. |
| Checkout cancelled | `snap_to_track_checkout_cancelled` | The browser came back from Stripe through the cancel link. |
| First payment | `snap_to_track_first_payment` | A signed Stripe webhook confirmed the paid invoice that created a subscription. |
| Purchase claimed | `snap_to_track_purchase_claimed` | A guest purchase paid before sign-up was added to an account. The `method` is `email_link` or `google`. |

`--definitions` prints the same list with each stage's de-duplication rule.

The report counts three ordered paths:

- **Scan:** Photo selected → Analysis requested → Result viewed → Offer viewed
- **Save path:** Result viewed → Save clicked → Signed up or logged in → Meal saved
- **Upgrade path:** Offer viewed → Checkout opened → First payment

A journey counts at a step only if it reached every earlier step of that path first, in order. "Signed up or logged in" also counts when the journey was already signed in before the previous step. "Returned from checkout" isn't a step: Stripe often confirms the payment before the browser comes back, and some buyers never come back. Returns appear under errors and pending states and in the raw totals. The upgrade path covers both guest checkout and members who sign in first, so neither the offer click nor sign-up is a required step; both stay in the raw totals.

## De-duplication

- Every event has a unique key built from its stage, its journey and the attempt it belongs to (an upload, an analysis request, a result, a checkout). Reloads, re-renders, double clicks and repeated success-page visits therefore write nothing new.
- Funnel numbers count unique journeys. Raw event totals are listed separately and do include repeats, such as several uploads, a retried analysis or offers seen on two results.
- First payments are counted once per Stripe subscription. Only signed `invoice.paid` and `invoice.payment_succeeded` webhooks count, and only for the invoice that created the subscription (`billing_reason` `subscription_create`) with `amount_paid` above zero. None of the following adds one: a renewal (including renewals of subscriptions that started before the ledger), a trial start, a failed or unpaid invoice, a webhook replay, a success-page visit or an entitlement change. A trial that converts later wouldn't be counted; no current plan has a trial.
- Invoices for Stripe customers that don't belong to a Plate account are ignored, unless their subscription metadata names a plan purchase recorded by this app (a guest purchase that hasn't been claimed yet).
- While `STRIPE_WEBHOOK_SECRET` is unset, Cashier skips signature checks, so no payment is recorded at all.

## Errors and pending states

These are listed beside the funnel, not inside it:

- photo rejected, by reason (`photo`, `verification`, `rate_limited`);
- analysis failed, by failure code;
- analysis requested with no outcome recorded (still running, or interrupted with nobody left to check it);
- checkout failed, by reason;
- checkout cancelled;
- checkout opened but never returned, cancelled or paid (abandoned);
- returned from checkout but payment not confirmed.

## Payments and reconciliation

- First payments confirmed by Stripe in live mode are split into three groups: those from a Snap journey, those from a staff journey, and those with no Snap journey (for example, a checkout opened from inside the app outside any journey). Test-mode payments are counted on their own row.
- The report compares these payments with the billing records: subscriptions created in the same period, how many of them have a confirmed first payment, and the Stripe status of the rest (for example, `incomplete`).

## Conversion, placements and activation

- **Conversion** looks at journeys whose window has closed and that reached a result. It shows how many of them have a confirmed live first payment. A payment confirmed after the window still counts when its checkout opened inside it. Each journey counts once, whether or not it clicked an offer or signed up. Staff journeys and journeys with a test-mode payment are left out.
- **Checkout placement** uses the same journeys. It counts the checkouts they opened and their first payments by the checkout's `placement` and `free_scans_used`. For example, `result_bar · after 1 free scan` is the free-or-Starter choice after the first scan, and `result_bar · after 2 free scans` is the offer after both. A payment belongs to the last checkout its journey opened before it. A journey counts once per placement.
  - Member checkouts from the plan page have no placement and show as `plan page`.
  - `result_bar` checkouts without a free-scan count show as plain `result_bar`. These are checkouts recorded before the count was added on 2026-11-24, and checkouts continued from the purchase page.
- **Guest purchases** covers plan purchases paid in the period. It counts those activated (by `email_link` or `google`), those cancelled before activation and those not activated yet.
- **Open guest purchases now** lists every paid purchase that is neither activated nor cancelled, whatever its payment date. It shows how long each has waited for activation, and which ones were sent to support and why (`active_plan` or `other_customer`). A purchase waiting over 30 days has renewed without access.
- Purchases from staff journeys and purchases whose first payment was in test mode are left out.

`--json` returns these as `conversion`, `placements`, `purchases` and `open_purchases`.

## What Umami still shows

Umami keeps the raw browser events and acquisition analytics. Some of its event names match the stage names above, but its counts are raw browser events, not journeys, and can cover different buttons: its `snap_to_track_save_click` fires only from the Save buttons, not from the Log in links, and names the button in `placement`: `meal` (the meal card), `result` (the plan offer) or `result_bar` (the result sheet). Ad blockers can drop them. Signed-in scans in the app also send `snap_to_track_result_viewed`, with `source` set to `authenticated_snap_to_track`. The journey ledger sends nothing to Umami.

## Recording a change

There are no flow versions. When a change alters the journey's screens, copy or offer, add a dated line to `BuildSnapFunnelReport::HISTORY`. Every later report then prints it in its notes. Treat before-and-after differences as observational evidence, not proof that the change caused them.

## Privacy

Analytics properties never include email addresses, photos, meal or health values, draft tokens or the raw guest cookie. When an account is deleted, its journeys stay as anonymous rows with no account attached.

## History notes

The report prints dated notes from `BuildSnapFunnelReport::HISTORY`, together with the day the ledger started. They cover the 2026-10-24 Umami event changes, the 2026-11-04 move of guest results to their own page, the 2026-11-07 Save layout on that page, the 2026-11-22 plan choice in its action bar, the 2026-11-26 change of that bar into a sheet that opens once after 2.5 seconds, the 2026-11-26 plan card after the first scan, the 2026-11-24 guest checkout before sign-up and Google activation option, the 2026-11-24 free-scan count on guest offers and checkouts, the 2026-11-07 change to paying before email verification and the 2026-11-07 removal of the review page before the food log. There is no data before the ledger start, and none is estimated. Don't build a "comparable" historical funnel from Umami totals.
