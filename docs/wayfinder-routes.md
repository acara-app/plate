# Wayfinder Route Usage Guide

This document explains how to use the Wayfinder-generated route helpers in this application.

## Route Structure

Wayfinder generates a nested object structure for routes. The routes are organized by controller/resource name in the `resources/js/routes/` directory.

### Directory Structure

```
resources/js/routes/
├── index.ts                    # Top-level routes (login, logout, home, dashboard, register)
├── onboarding/
│   ├── index.ts               # Onboarding parent object
│   ├── questionnaire/
│   ├── biometrics/
│   │   └── index.ts          # biometrics.show, biometrics.store
│   ├── goals/
│   ├── lifestyle/
│   ├── dietary-preferences/
│   └── health-conditions/
└── [other route groups...]
```

## Import Pattern

### Correct Import

```typescript
import onboarding from '@/routes/onboarding';
```

### Usage Examples

#### Getting URLs for Links

```typescript
// Link to show biometrics page
<Link href={onboarding.biometrics.show.url()}>

// Link to show goals page  
<Link href={onboarding.goals.show.url()}>
```

#### Posting to Store Endpoints

```typescript
// Post form data to biometrics store endpoint
post(onboarding.biometrics.store.url());

// Post form data to goals store endpoint
post(onboarding.goals.store.url());
```

## Available Onboarding Routes

| Route Object | URL | Method | Purpose |
|-------------|-----|--------|---------|
| `onboarding.questionnaire` | `/onboarding` or `/onboarding/start` | GET | Landing page |
| `onboarding.biometrics.show` | `/onboarding/biometrics` | GET | Show biometrics form |
| `onboarding.biometrics.store` | `/onboarding/biometrics` | POST | Store biometrics data |
| `onboarding.goals.show` | `/onboarding/goals` | GET | Show goals form |
| `onboarding.goals.store` | `/onboarding/goals` | POST | Store goals data |
| `onboarding.lifestyle.show` | `/onboarding/lifestyle` | GET | Show lifestyle form |
| `onboarding.lifestyle.store` | `/onboarding/lifestyle` | POST | Store lifestyle data |
| `onboarding.dietaryPreferences.show` | `/onboarding/dietary-preferences` | GET | Show dietary preferences form |
| `onboarding.dietaryPreferences.store` | `/onboarding/dietary-preferences` | POST | Store dietary preferences |
| `onboarding.healthConditions.show` | `/onboarding/health-conditions` | GET | Show health conditions form |
| `onboarding.healthConditions.store` | `/onboarding/health-conditions` | POST | Store health conditions |

## Route Object Methods

Each route object provides these methods:

- `.url(options?)` - Get the URL string
- `.get(options?)` - Get route definition with GET method
- `.post(options?)` - Get route definition with POST method (for POST routes)
- `.form` - Form helper object for use with HTML forms

## Common Mistakes

❌ **Don't import flat route names:**
```typescript
// This doesn't exist!
import { onboardingBiometricsStore } from '@/routes';
```

✅ **Use nested object structure:**
```typescript
import onboarding from '@/routes/onboarding';
post(onboarding.biometrics.store.url());
```

## Regenerating Routes

When you add/modify Laravel routes, regenerate Wayfinder routes:

```bash
php artisan wayfinder:generate --with-form
```

This will update all TypeScript route definitions in `resources/js/routes/`.
