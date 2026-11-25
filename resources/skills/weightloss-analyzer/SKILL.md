---
name: weightloss-analyzer
description: Analyze weight-management data, estimate energy needs, track calorie balance, identify plateaus, and support sustainable fat-loss and maintenance planning with nutrition-first guidance.
license: MIT
metadata:
    source: https://github.com/huifer/WellAlly-health/tree/main/.claude/skills/weightloss-analyzer
allowed-tools: get_user_profile health_specialist nutrition_specialist fitness_specialist glucose_spike_specialist
---

# Weight Management Analyzer

Use this skill for evidence-based weight management support. It should behave like a careful nutrition coach: practical, conservative, and focused on sustainable habits rather than crash dieting.

The skill analyzes body metrics, calorie intake, activity patterns, and progress trends. It should help users understand what is happening, what to adjust next, and when the situation is beyond self-management and should be escalated to a clinician or registered dietitian.

> **Data origin in Plate.** Most biometric, activity, sleep, blood-pressure, and glucose data in Plate originates from Apple HealthKit via the Plate Health Sync feature. Treat Health Sync data as the authoritative time series; manual log entries fill gaps. Always pull data through the Plate tools listed below — do not assume any value the user has not just confirmed or that did not come from a tool result this turn.

## Core Principles

- Prioritize long-term health, adherence, and lean-mass preservation.
- Treat BMI, body-fat readings, smart-scale output, and calorie estimates as screening tools, not diagnoses.
- Favor healthy eating patterns, portion control, and activity consistency over aggressive restriction.
- Avoid "quick fix" advice, detoxes, or supplement-first recommendations.
- Do not double-count exercise when calculating energy deficit.

## When to Use This Skill

Use this skill when the user asks for:

- weight-loss analysis or coaching
- fat-loss planning
- calorie target estimation
- BMR or TDEE calculation
- calorie deficit tracking
- plateau detection
- maintenance-phase guidance
- progress review for body weight, waist, or body composition

## Core Capabilities

### 1. Anthropometric Assessment

Assess body size and risk markers using weight, height, waist circumference, and body-composition trends.

**BMI calculation**
- `BMI = weight_kg / (height_m ^ 2)`

**Default adult BMI categories**
- Underweight: `< 18.5`
- Healthy weight: `18.5 to < 25`
- Overweight: `25 to < 30`
- Obesity: `>= 30`

**Optional Asian-specific cutoffs**
- Underweight: `< 18.5`
- Healthy weight: `18.5 to < 24`
- Overweight: `24 to < 28`
- Obesity: `>= 28`

Use the default adult BMI categories unless the user explicitly needs Asian-specific thresholds or the host workflow already uses them. As a heuristic, if `user.locale` returned by `get_user_profile` is one of `mn`, `cn`, `kr`, `jp`, `vn`, or `tw`, prefer the Asian cutoffs and note the choice in your response.

**Waist circumference**
- Increased cardiometabolic risk is commonly flagged above:
  - Women: `> 88 cm / 35 in`
  - Men: `> 102 cm / 40 in`

**Waist-to-hip ratio**
- Men: `>= 0.90` suggests central adiposity risk
- Women: `>= 0.85` suggests central adiposity risk

**Body-fat interpretation**
- Use body-fat percentage mainly for trend tracking and lean-mass preservation.
- Consumer smart-scale body-fat readings are noisy. Prioritize multi-week trends over single readings.

**Reference weight**
- BMI method: `target_weight_kg = height_m^2 * 22`
- Broca-style estimate: `target_weight_kg = (height_cm - 100) * 0.9`

Do not present a single "ideal weight" as medically definitive. Present it as a planning reference.

### 2. Energy Needs and Metabolic Estimation

Estimate resting and daily energy needs using standard predictive equations.

**Harris-Benedict (original)**
- Men: `BMR = 88.362 + (13.397 x weight_kg) + (4.799 x height_cm) - (5.677 x age)`
- Women: `BMR = 447.593 + (9.247 x weight_kg) + (3.098 x height_cm) - (4.330 x age)`

**Mifflin-St Jeor**
- Men: `BMR = (10 x weight_kg) + (6.25 x height_cm) - (5 x age) + 5`
- Women: `BMR = (10 x weight_kg) + (6.25 x height_cm) - (5 x age) - 161`

**Katch-McArdle**
- `BMR = 370 + (21.6 x lean_mass_kg)`
- `lean_mass_kg = weight_kg x (1 - body_fat_fraction)`

**TDEE**
- `TDEE = BMR x activity_factor`

**Activity factors**
- Sedentary: `1.2`
- Lightly active: `1.375`
- Moderately active: `1.55`
- Very active: `1.725`
- Extremely active: `1.9`

**Interpretation rule**
- Use `Mifflin-St Jeor` as the default estimate for most adults.
- Use `Katch-McArdle` when lean-mass data is reasonably trustworthy.
- Mention uncertainty. Estimated energy needs are usually good starting points, not exact truths.

### 3. Calorie Balance and Rate-of-Loss Analysis

Track whether the current plan is likely to drive fat loss, maintenance, or regain.

**If TDEE already includes activity**
- `daily_deficit = TDEE - intake`

**If activity is tracked separately from baseline expenditure**
- `daily_deficit = baseline_expenditure + exercise_kcal + NEAT_kcal - intake`

Never add exercise calories on top of a TDEE that already includes them.

**Weight-loss estimate**
- `1 kg body weight ~= 7700 kcal`
- `expected_weekly_change_kg = (average_daily_deficit x 7) / 7700`

**Reasonable working targets**
- Mild deficit: `250 to 500 kcal/day`
- Moderate deficit: `500 to 750 kcal/day`
- Aggressive deficit: `750 to 1000 kcal/day` only when context supports it

**Expected pace**
- A common practical target is roughly `0.25 to 1.0 kg/week`
- Larger users may lose faster early on
- Smaller, leaner, older, or highly active users may lose more slowly

### 4. Nutrition Quality Review

Weight management should not be reduced to calories alone. Review the structure and quality of the diet.

Assess whether the eating pattern:

- includes vegetables, fruits, whole grains, legumes, lean proteins, dairy or fortified alternatives, nuts, and seeds
- limits added sugars
- limits saturated fat
- avoids routine excess sodium
- distributes meals in a way the user can sustain
- supports satiety, training, recovery, and adequate protein intake

Look for common failure points:

- low-protein meals
- low-fiber intake
- liquid calories
- frequent restaurant meals
- weekend overeating
- untracked snacks, bites, and drinks
- calorie targets that are too low to sustain

When appropriate, use simple meal-structure coaching:

- half the plate from vegetables and fruit
- one quarter from lean protein
- one quarter from whole grains or other high-fiber starches

### 5. Plateau Detection and Troubleshooting

Identify whether progress has truly stalled.

**Plateau rule**
- A practical plateau is `2 to 4 weeks` of flat weight trend despite reasonably good adherence.

Before recommending a bigger deficit, check:

- logging accuracy
- declining step count or NEAT
- restaurant and weekend intake
- menstrual-cycle water retention
- constipation or sodium-related water shifts
- new strength training or muscle soreness
- medication effects
- sleep disruption and stress

Only after those checks should the skill suggest a plan adjustment such as:

- tightening food logging
- increasing daily movement
- adding one or two more strength sessions
- modestly reducing calories
- inserting a short maintenance phase if fatigue and adherence are poor

### 6. Phase Management

Support three common phases:

**Fat-loss phase**
- track trend weight, average intake, step count, training, waist, and adherence
- compare actual pace with expected pace

**Maintenance phase**
- define a maintenance band, such as `target weight +/- 2 kg`
- keep regular weigh-ins
- raise calories carefully if needed
- emphasize activity consistency to reduce regain risk

**Recovery or reset phase**
- use when adherence is failing, diet fatigue is high, or the user has been dieting hard for a long time
- focus on stabilizing habits, sleep, meal structure, and training consistency

## Where to Get the Data

In Plate, every input this skill needs is reachable through tool calls. Do not invent values. Always source from a tool result this turn.

**Who calls these tools.** Call `get_user_profile` yourself. The other tools in this table belong to the specialists, so ask the specialist that owns a tool instead of calling it: `health_specialist` runs `get_health_summary`, `get_health_data` and `get_health_goals`; `nutrition_specialist` runs `get_diet_reference`; `fitness_specialist` runs `get_fitness_goals`; and `glucose_spike_specialist` predicts a food's glucose response. Give the specialist a self-contained task with the window, the data you need, and the profile facts it needs, and relay only the numbers it reports.

| What you need | Plate upstream | Tool to call | Notes |
|---|---|---|---|
| Profile (height, age, sex, target weight, dietary prefs, conditions, medications, locale) | manual onboarding + profile edits | `get_user_profile` | Pass `section: "all"` for the first read; later, request `biometrics` / `goals` / `dietary_preferences` to keep responses focused. |
| Body weight, waist, body-fat trend | Apple HealthKit via **Health Sync** + manual entries | `get_health_summary` (windowed averages, weekly rate of change), `get_health_data` (specific entries) | Always start with `get_health_summary` over the analysis window — it returns the rolling averages this skill needs. |
| Activity (steps, exercise minutes, strength sessions) | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_fitness_goals` | Strength sessions may also be inferred from manually logged workouts. |
| Calorie & macronutrient intake (totals, adherence) | manual logging in Altani (`log_health_entry`, `analyze_photo`) and active meal plans | `get_health_summary`, `get_health_data`, `get_diet_reference` | Treat days without intake entries as logging gaps, not zero-calorie days. |
| Daily notes (hunger, symptoms, recovery, mood) | manual entries and chat-derived logs | `get_health_data` (date-ranged) | Pull these only when the analysis demands a qualitative signal. |
| Sleep | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_health_data` | Use as a plateau-troubleshooting input, not a primary metric. |
| Blood pressure | Apple HealthKit via **Health Sync** + manual | `get_health_summary`, `get_health_data` | Flag uncontrolled BP and escalate per the safety rules below. |
| Glucose | Apple HealthKit (CGM) via **Health Sync** + manual | `get_health_summary`, `get_health_data`, `glucose_spike_specialist` | Required when the user has diabetes or pre-diabetes context. |
| Medications | manual records + Health Sync (where supported) | `get_health_data` (medication-typed entries); `get_user_profile` for the static medication list | Medications affecting appetite, glucose, fluid balance, or thyroid can drive weight changes — flag and escalate. |
| Health goals & current phase | profile + goal store | `get_health_goals`, `get_fitness_goals` | Use to reconcile the user's stated goal with the data you observe. |

If a tool call returns no data over the requested window, say so explicitly and ask the user whether to widen the window or continue with what is available — never extrapolate from missing data.

## Workflow

### Step 1: Clarify the Goal

Determine whether the user wants:

- an initial calorie target
- progress review
- plateau troubleshooting
- body-composition review
- maintenance planning

Also clarify:

- time frame
- current weight and goal weight
- training status
- relevant medical conditions

### Step 2: Read the Data

Fetch the data listed in the *Where to Get the Data* table above. Begin with `get_user_profile` (for static biometrics, goals, locale), then ask `health_specialist` for `get_health_summary` over the chosen window (for trend metrics). Have it call `get_health_data` afterward only to drill into specific dates the summary surfaced as anomalies. If the user asks about glucose response, blood pressure, or sleep, ask for those streams in the same task.

Minimum review window:
- `2 weeks` for a quick check
- `4 to 8 weeks` for progress review
- `12+ weeks` for phase management

### Step 3: Clean and Summarize

Calculate:

- start weight, current weight, lowest recent weight
- 7-day rolling average weight
- weekly rate of change
- adherence rate to intake target
- average daily intake
- average steps or activity minutes
- number of strength sessions per week

### Step 4: Estimate Energy Needs

Calculate BMR and TDEE using the best available formula. If multiple formulas are used, explain which one is preferred and why.

### Step 5: Evaluate the Deficit

Compare estimated expenditure with actual intake.

Look for:

- average deficit size
- excessive day-to-day swings
- weekend compensation
- unrealistic targets
- signs the user is under-eating but not adhering consistently

### Step 6: Review Nutrition Quality

Check whether the plan is nutritionally sound enough to sustain weight loss.

Review:

- meal regularity
- protein distribution
- fruit and vegetable intake
- whole-grain and fiber intake
- sugar-sweetened beverages
- takeout frequency
- alcohol intake

### Step 7: Identify the Current Phase

Classify the user as:

- losing
- maintaining
- regaining
- stalled
- inconsistent due to adherence problems

### Step 8: Generate Recommendations

Recommendations should be specific, limited, and actionable.

Good examples:

- "Keep calories where they are and improve weekend logging."
- "Increase steps by 2,000 per day before cutting food further."
- "Add two protein-forward lunches to improve satiety."
- "Hold a 2-week maintenance phase, then reassess."

Avoid vague coaching such as:

- "eat cleaner"
- "try harder"
- "just do more cardio"

## Output Formats

### Body Metrics Report

```markdown
# Body Metrics Review

## Profile
- Sex: Male
- Age: 52
- Height: 175 cm
- Weight: 75 kg

## Screening Metrics

### BMI
- Current BMI: 24.5
- Category: Healthy weight using standard adult BMI cutoffs
- Reference planning weight: 67 kg (BMI 22)
- Comment: BMI is only one screening tool and should be interpreted together with waist and body-composition trends.

### Waist and Body Composition
- Waist circumference: 92 cm
- Waist-to-hip ratio: 0.94
- Estimated body fat: 25%
- Comment: Central fat distribution appears to be a higher priority risk marker than BMI alone.

## Coaching Priorities
1. Reduce waist circumference gradually through a sustainable calorie deficit.
2. Preserve lean mass with resistance training and adequate protein intake.
3. Monitor trend weight and waist rather than reacting to daily fluctuations.
```

### Energy Needs Report

```markdown
# Energy Needs Review

## BMR Estimates

| Formula | BMR (kcal/day) | Notes |
|---|---:|---|
| Harris-Benedict | 1650 | Older formula |
| Mifflin-St Jeor | 1620 | Best default estimate |
| Katch-McArdle | 1700 | Useful only if body-fat estimate is reliable |

**Working BMR:** 1620 kcal/day

## TDEE Estimate
- Activity level: Moderately active
- Activity factor: 1.55
- Estimated TDEE: 2511 kcal/day

## Suggested Intake Ranges
- Mild deficit: 2000 to 2250 kcal/day
- Moderate deficit: 1750 to 2000 kcal/day
- Aggressive deficit: use only if clinically appropriate and adherence is strong

## Interpretation
- Start with the smallest deficit that the user can sustain.
- If hunger, fatigue, or rebound eating are already problems, do not force a deeper cut.
```

### Calorie Balance Report

```markdown
# Weekly Calorie Balance Review

## Week Summary

| Day | Intake | Activity Burn | Deficit | On Plan |
|---|---:|---:|---:|---|
| Mon | 1800 | included in TDEE | 711 | Yes |
| Tue | 2100 | included in TDEE | 411 | Borderline |
| Wed | 1750 | included in TDEE | 761 | Yes |
| Thu | 1950 | included in TDEE | 561 | Yes |
| Fri | 2200 | included in TDEE | 311 | Borderline |
| Sat | 2400 | included in TDEE | 111 | No |
| Sun | 1850 | included in TDEE | 661 | Yes |

## Weekly Interpretation
- Average daily deficit: 504 kcal/day
- Estimated weekly loss: 0.46 kg
- Main issue: weekend intake is eroding an otherwise workable plan

## Next Adjustment
1. Keep weekday targets unchanged.
2. Add a weekend meal plan before reducing calories further.
3. Recheck trend weight after 2 weeks.
```

### Phase Management Report

```markdown
# Weight Management Phase Review

## Current Phase
Fat-loss phase

## Progress
- Start date: 2025-01-01
- Starting weight: 82 kg
- Current weight: 75 kg
- Goal weight: 67 kg
- Total lost: 7 kg
- Remaining: 8 kg
- Progress toward goal: 47%

## Rate of Change
- Total duration: 24 weeks
- Long-term average: 0.29 kg/week
- Last 4 weeks: 0.35 kg/week

## Status
- Weight is trending downward
- Progress is slower than the original target, but still meaningful
- The current pace may be more sustainable than a more aggressive cut

## Recommended Next Steps
1. Continue the current calorie range for 2 more weeks.
2. Add one more resistance-training session weekly.
3. Review protein intake and weekend consistency.
```

## Sample User Prompts that Should Activate This Skill

- "Can you analyze my weight loss progress?"
- "How is my fat loss going over the last month?"
- "Am I in a deficit?"
- "I'm stuck — why am I not losing weight anymore?"
- "What should my calorie target be?"
- "Estimate my BMR / TDEE."
- "How long until I reach my goal weight at this pace?"
- "Should I be doing a maintenance phase?"

## Safety Guardrails

### Escalate to a Clinician or Registered Dietitian When

- BMI is very high or very low
- the user is pregnant, postpartum, or breastfeeding
- the user has diabetes, cardiovascular disease, kidney disease, liver disease, or an eating-disorder history
- the user is taking prescription medication that may affect weight, appetite, or glucose
- weight loss is unintentional
- fatigue, dizziness, fainting, hair loss, amenorrhea, or other red flags appear

### Nutrition Safety Rules

- Avoid very-low-calorie plans unless medically supervised.
- Do not recommend weight-loss supplements as a first-line strategy.
- If calorie intake appears extremely low, flag likely under-fueling before praising adherence.
- If the user is physically active, preserve muscle by prioritizing resistance training and adequate dietary protein.

### Medical Disclaimer

This skill provides educational weight-management guidance only. It does not diagnose, treat, or replace medical care.

---

**Skill version**: v1.2 (Plate adaptation — data sources point at Plate tools, Health Sync called out, locale-aware BMI cutoffs)
**Last updated**: 2026-05-04
**Maintainer**: Acara App
