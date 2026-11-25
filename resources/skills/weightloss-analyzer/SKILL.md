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

Use the default adult BMI categories unless the user specifies a different applicable standard. Name the standard you use.

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

Use the records returned by Plate and information the user provides. Identify gaps explicitly.

Call `get_user_profile` directly. Delegate health records and goals to `health_specialist`, diet references to `nutrition_specialist`, and fitness goals to `fitness_specialist`. Include the date range, metric and relevant profile facts in each task.

| What you need | Plate upstream | Tool to call | Notes |
|---|---|---|---|
| Profile (height, age, sex, target weight, dietary preferences, conditions, medications) | manual onboarding + profile edits | `get_user_profile` | Request the smallest relevant section, such as `biometrics` for measurements or `goals` for target weight. |
| Body weight, waist, body-fat trend | Apple HealthKit via **Health Sync** + manual entries | `get_health_summary` (daily aggregates), `get_health_data` (specific entries) | Ask the health specialist to calculate rolling averages or weekly changes from the available daily values and report how many days had data. |
| Activity (steps, exercise minutes, strength sessions) | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_fitness_goals` | Strength sessions may also be inferred from manually logged workouts. |
| Calorie & macronutrient intake (totals, adherence) | synced and manually logged food entries | `get_health_summary`, `get_health_data` | Use recorded intake to assess adherence. Treat days without intake entries as logging gaps. |
| Daily notes (hunger, symptoms, recovery, mood) | manual entries and chat-derived logs | `get_health_data` (date-ranged) | Pull these only when the analysis demands a qualitative signal. |
| Sleep | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_health_data` | Use as a plateau-troubleshooting input, not a primary metric. |
| Blood pressure | Apple HealthKit via **Health Sync** + manual | `get_health_summary`, `get_health_data` | Flag uncontrolled BP and escalate per the safety rules below. |
| Glucose | Apple HealthKit (CGM) via **Health Sync** + manual | `get_health_summary`, `get_health_data`, `get_meal_glucose_response` | Ask `health_specialist` for observed responses to logged meals. Use `glucose_spike_specialist` for a prospective food comparison or spike estimate. |
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

Ask `health_specialist` for `get_health_summary` over the chosen window. Have it call `get_health_data` to drill into specific dates where the summary surfaced an anomaly. If the user asks about glucose response, blood pressure, or sleep, include that question in the same task.

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

## Report Examples

When you need a report format for body metrics, energy needs, calorie balance, or progress, call `LoadSkill` with `name: "weightloss-analyzer"` and `path: "report-examples.md"`. Use only the user’s own data in the report.

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

**Skill version**: v1.3 (Plate adaptation)
**Last updated**: 2026-11-25
**Maintainer**: Acara App
