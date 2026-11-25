---
name: nutrition-analyzer
description: Analyze dietary intake, surface nutrition patterns, screen nutrient adequacy against DRIs, link diet to weight, sleep, activity, blood pressure, and glucose, and generate sustainable food-first recommendations.
license: MIT
metadata:
    source: https://github.com/huifer/WellAlly-health/tree/main/.claude/skills/nutrition-analyzer
allowed-tools: get_user_profile health_specialist nutrition_specialist fitness_specialist glucose_spike_specialist
---

# Nutrition Analyzer

Use this skill for practical, evidence-based nutrition review. It should behave like a careful nutrition analyst: food-first, pattern-aware, conservative with supplements, and clear about the difference between intake estimates, screening signals, and clinical diagnosis.

The skill analyzes nutrition logs, meal patterns, and nutrient intake, then relates them to the user's wider health data. It should help users understand what they are eating, where the main gaps or excesses are, how those patterns are changing over time, and which realistic changes would matter most.

> **Data origin in Plate.** Most intake, biometric, activity, sleep, blood-pressure, and glucose data in Plate originates from Apple HealthKit via the Plate **Health Sync** feature; manual logging in Altani (`log_health_entry`, `analyze_photo`) fills gaps. Treat Health Sync data as the authoritative time series. Always pull data through the Plate tools listed in *Where to Get the Data* below — never assume a value the user has not just confirmed or that did not come from a tool result this turn.

## Core Principles

- Prioritize the overall dietary pattern, not isolated nutrients.
- Treat food logs, app estimates, and nutrient databases as approximate.
- Use nutrient reference values (RDA, AI, UL) as screening tools, not as a diagnosis.
- Prefer food-based changes before any supplement-first advice.
- Separate correlation from causation when comparing diet against weight, blood pressure, sleep, glucose, or activity.
- Escalate specialized cases such as pregnancy, kidney disease, eating-disorder risk, or severe malnutrition.

## When to Use This Skill

Use this skill when the user asks for:

- nutrition trend analysis
- nutrient intake or adequacy review
- meal-pattern and eating-behavior review
- diet-quality scoring
- food-based improvement recommendations
- nutrition links to weight, sleep, training, blood pressure, or glucose
- macronutrient and micronutrient adequacy checks
- supplement review at a general guidance level

## Core Capabilities

### 1. Nutrition Trend Analysis

Analyze how intake changes over time and identify meaningful movement, not daily noise.

Common trend dimensions:

- calories
- protein, carbohydrate, fat, and fiber
- saturated, monounsaturated, polyunsaturated, and trans fat
- sodium, potassium, and the potassium-to-sodium ratio
- added sugars
- selected vitamins and minerals
- meal timing and eating-window patterns
- food-category distribution

Each trend should report:

- direction of change (improving / stable / worsening)
- magnitude and percentage change
- adherence over time
- whether the change is likely helpful, neutral, or concerning

Use rolling averages whenever possible. Single-day readings are usually too noisy to interpret confidently.

### 2. Nutrient Adequacy Review

Assess whether estimated intake is likely meeting needs, using the correct reference framework:

- `RDA` when an RDA exists
- `AI` (Adequate Intake) when no RDA is defined
- `UL` (Tolerable Upper Intake Level) for nutrients where excess intake matters

Always interpret reference values in context: age, sex, pregnancy or lactation, dietary pattern, training load, medical conditions, and medication use.

Reference intake standards vary by region. Default to the US Dietary Guidelines / WHO values as the canonical fallback, and prefer region-appropriate DRIs when `user.locale` returned by `get_user_profile` indicates a specific locale; note the standard you used.

**Macronutrients**
- total protein intake and its distribution across meals
- carbohydrate quality (refined vs. complex) and fiber content
- fat quality, with attention to saturated and trans fat
- calorie density and satiety profile

**Micronutrients often worth screening**
- vitamin D
- vitamin B12
- folate
- calcium
- iron
- magnesium
- potassium
- iodine
- zinc
- omega-3 (EPA, DHA, ALA)
- choline

**Interpretation guardrails**
- Intake below target does not prove deficiency.
- Intake at or above target does not guarantee optimal status.
- Labs, symptoms, and clinical context can change the meaning of the data.

Prefer wording such as `likely low` or `borderline` over diagnosing deficiency from intake data alone.

### 3. Diet Quality Assessment

Assess the overall quality of the eating pattern, not just the nutrient math.

Look for whether the diet regularly includes:

- vegetables
- fruit, especially whole fruit
- legumes
- whole grains
- lean or minimally processed protein sources
- dairy or fortified alternatives when appropriate
- nuts, seeds, and unsaturated-fat sources

Look for whether the diet over-relies on:

- ultra-processed snacks
- sugar-sweetened beverages
- refined grains
- restaurant and takeout meals
- large portions of energy-dense, low-fiber foods
- frequent high-sodium convenience foods

Useful reference-pattern signals:

- added sugars kept below `10%` of calories
- saturated fat kept below `10%` of calories
- sodium generally below `2300 mg/day` (ideal closer to `1500 mg/day`)
- a potassium-to-sodium ratio trending above `1` (ideally `> 2`)
- alignment with Mediterranean-style, DASH-style, or other plant-forward whole-food patterns when appropriate

These are broad public-health targets, not individualized prescriptions for every user.

### 4. Meal Pattern and Behavioral Review

Analyze how the user eats, not only what they eat.

Review:

- breakfast regularity
- number of meals and snacks per day
- late-night eating
- eating-window consistency
- weekend-versus-weekday drift
- emotional or stress-linked eating when logs support it
- meal composition and satiety

Common red flags:

- protein-poor breakfasts
- very low fiber across the day
- long gaps followed by overeating
- most calories concentrated late at night
- liquid calories displacing whole-food meals

When appropriate, use simple plate-structure coaching:

- half the plate from vegetables and fruit
- one quarter from lean protein
- one quarter from whole grains or other higher-fiber starches

### 5. Correlation Analysis with Other Health Data

Nutrition does not operate in isolation. Compare diet patterns with other tracked data when available.

**Nutrition ↔ Weight**
- calorie intake versus weight trend
- fiber and protein versus hunger and adherence
- alcohol or weekend intake versus weight stalls

**Nutrition ↔ Exercise**
- protein sufficiency for recovery
- carbohydrate availability for training performance
- under-fueling risk in active users

**Nutrition ↔ Sleep**
- caffeine timing and quantity
- alcohol and sleep disruption
- late meals and sleep quality
- magnesium and tryptophan-bearing foods

**Nutrition ↔ Blood Pressure**
- sodium pattern
- potassium-rich food intake and the potassium-to-sodium ratio
- DASH-style adherence

**Nutrition ↔ Glucose**
- carbohydrate quality and load
- mixed-meal structure
- fiber effects on the glucose curve
- sugary-beverage and dessert frequency

Correlation outputs must always state that observational, self-logged data cannot establish causation. Where helpful, note that lagged effects (e.g., evening caffeine on that night's sleep) are still associations, not proof.

### 6. Personalized Recommendation Generation

Generate recommendations that are specific, limited, practical, and food-first.

Recommendation types:

- close likely nutrient gaps with food choices
- improve meal structure and satiety
- replace high-sodium or high-added-sugar staples
- improve protein distribution across the day
- increase vegetables, fruit, legumes, and whole grains
- improve unsaturated-fat sources and omega-3 intake
- simplify the eating pattern so it is easier to sustain

Supplement suggestions, if offered at all, should be:

- secondary to food-based guidance
- framed as general educational guidance, not a prescription
- screened for obvious interaction or safety concerns
- escalated when the user has relevant conditions or takes interacting medications

Ground recommendations in DRIs/RDA standards, the user's nutrition history, their stated goals and conditions, and current evidence-based nutrition guidance.

## Where to Get the Data

In Plate, every input this skill needs is reachable through tool calls. Do not invent values. Always source from a tool result this turn.

**Who calls these tools.** Call `get_user_profile` yourself. The other tools in this table belong to the specialists, so ask the specialist that owns a tool instead of calling it: `health_specialist` runs `get_health_summary`, `get_health_data` and `get_health_goals`; `nutrition_specialist` runs `get_diet_reference`, `get_calorie_level_guideline` and `get_daily_servings_by_calorie`; `fitness_specialist` runs `get_fitness_goals`; and `glucose_spike_specialist` predicts a food's glucose response. Give the specialist a self-contained task with the window, the data type (for example `type: "food"`), and the profile facts it needs, and relay only the numbers it reports.

| What you need | Plate upstream | Tool to call | Notes |
|---|---|---|---|
| Profile (age, sex, height, dietary preferences, conditions, medications, locale) | manual onboarding + profile edits | `get_user_profile` | Pass `section: "all"` for the first read; later request `biometrics` / `dietary_preferences` / `goals` to keep responses focused. |
| Calorie, macronutrient & micronutrient intake (calories, carbs, protein, fat and its subtypes, fiber, sugar, sodium, potassium, vitamins, minerals) | Apple HealthKit via **Health Sync** + manual logging in Altani (`log_health_entry`, `analyze_photo`) | `get_health_summary` (windowed daily totals — start here), `get_health_data` (individual entries) | Call `get_health_summary` with `type: "food"` to get the full daily nutrient panel in one request; use `get_health_data` to drill into specific days. Treat days without intake entries as logging gaps, not zero-intake days. A nutrient the user does not have in HealthKit simply will not appear — say so rather than infer it. |
| Reference targets & patterns (DRIs, food groups, serving sizes, diet-pattern cards) | bundled reference content | `get_calorie_level_guideline`, `get_daily_servings_by_calorie`, `get_diet_reference` | Guideline/reference data, not the user's intake. Use to set targets and structure recommendations (e.g. Mediterranean or DASH cards via `get_diet_reference`). |
| Body weight | Apple HealthKit via **Health Sync** + manual | `get_health_summary`, `get_health_data` | For diet ↔ weight correlation. |
| Activity (steps, exercise minutes, training) | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_fitness_goals` | For fueling adequacy and under-eating checks in active users. |
| Sleep | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_health_data` | For caffeine-timing and late-meal correlations. |
| Blood pressure | Apple HealthKit via **Health Sync** + manual | `get_health_summary`, `get_health_data` | For sodium and potassium-to-sodium correlation; flag uncontrolled BP and escalate. |
| Glucose | Apple HealthKit (CGM) via **Health Sync** + manual | `get_health_summary`, `get_health_data`, `glucose_spike_specialist` | For carbohydrate-quality and meal-structure effects; required when diabetes or pre-diabetes context applies. |
| Medications | manual records + Health Sync (where supported) | `get_health_data` (medication-typed entries); `get_user_profile` for the static medication list | Flag medications affecting appetite, glucose, fluid balance, or nutrient absorption. |
| Health goals & phase | profile + goal store | `get_health_goals`, `get_fitness_goals` | Reconcile the user's stated goal with what the data shows. |

If a tool call returns no data over the requested window, say so explicitly and ask the user whether to widen the window or continue with what is available — never extrapolate from missing data.

**Not carried by Health Sync.** Plate does not sync trans fat, omega-3 (EPA/DHA/ALA), choline, or added sugars — only *total* sugar is tracked. Treat these as available only when the user states them this turn or shares a meal photo; never populate them from tracked data, and omit them from reports (for example, drop the trans-fat row from the fat-quality table) when no user-provided value exists.

## Workflow

### Step 1: Clarify the Analysis Scope

Identify:

- the main question: trends, adequacy, quality, correlation, or recommendations
- time range: week, month, quarter, or custom
- analysis depth: macros only, targeted nutrients, or full review
- relevant context: weight goal, training status, disease history, dietary restrictions

### Step 2: Read the Data

Fetch the data listed in the *Where to Get the Data* table above. Begin with `get_user_profile` (static profile, conditions, medications, locale), then ask `health_specialist` for `get_health_summary` with `type: "food"` over the chosen window (the full daily nutrient panel). Have it drill into specific days with `get_health_data` only where the summary surfaces an anomaly. Ask for weight, activity, sleep, blood-pressure, or glucose streams when the question calls for a correlation.

Read enough surrounding context to interpret the nutrition data safely. Do not push generic potassium increases in users with kidney-disease risk, and do not push high-protein advice where the clinical context may limit protein. If a tool returns no data over the requested window, say so and ask whether to widen the window — never extrapolate from missing data.

### Step 3: Clean and Summarize

Summarize:

- average daily intake (calories and macros)
- weekly and monthly trends with rolling averages
- adherence to calorie or macro goals when present
- food-category frequency
- meal-timing patterns
- likely missing or inconsistent records

Run a completeness check before interpreting patterns aggressively. Treat days without intake entries as logging gaps, not zero-calorie days.

### Step 4: Evaluate Nutrient Adequacy

Compare average intake with the appropriate reference value (RDA, AI, or UL).

```python
def evaluate_nutrient_intake(actual, target, ul=None):
    ratio = actual / target if target else None

    if ul is not None and actual > ul:
        return "above_ul"
    if ratio is None:
        return "unknown"
    if ratio < 0.7:
        return "likely_low"
    if ratio < 1.0:
        return "borderline"
    return "meets_or_exceeds_target"
```

### Step 5: Score Diet Quality Carefully

Quality scores are useful summaries, but they are heuristics, not clinical assessments. A defensible score combines:

- adequacy of core food groups
- fiber and protein sufficiency
- moderation of sodium, saturated fat, and added sugars
- food variety
- reliance on minimally processed foods

### Step 6: Cross-Reference Related Health Data

If related data exists, compare nutrition patterns against body weight, activity volume, blood pressure, glucose, and sleep quality. State clearly when a result is only a descriptive association.

### Step 7: Generate the Report

Lead with the high-signal findings:

1. what is going well
2. the main gaps or excesses
3. the most useful next changes
4. any safety flags or escalation points

## Output Formats

### Nutrition Trend Report

```markdown
# Nutrition Intake Trend Review

## Analysis Window
2026-02-28 to 2026-05-29 (90 days logged)

## High-Level Summary
- Calorie intake trended modestly downward and is now closer to the user's stated target.
- Protein intake has been stable and generally adequate.
- Fiber improved but likely remains below an ideal range for satiety and gut health.
- Sodium intake improved meaningfully; the potassium-to-sodium ratio is still below target.

## Macronutrient Trends

### Calories
- Trend: Down
- Starting average: 2100 kcal/day
- Current average: 1950 kcal/day
- Change: -150 kcal/day (-7.1%)
- Interpretation: Consistent with weight-loss support, assuming adherence is real and energy levels remain acceptable.

### Protein
- Trend: Stable
- Average: 82 g/day (target 80 g/day, 93% of days on target)
- Interpretation: Likely adequate, but should be checked against body size and training load.

### Fiber
- Trend: Improving
- Starting average: 18 g/day
- Current average: 22 g/day (target 30 g/day)
- Interpretation: Better than baseline, but still low for appetite control and overall diet quality.

### Fat Quality
| Fat type | Start | Current | Target | Trend |
|---|---:|---:|---|---|
| Saturated | 25 g | 20 g | < 20 g | Improving |
| Monounsaturated | 30 g | 32 g | > 35 g | Slightly up |
| Polyunsaturated | 15 g | 12 g | 15–20 g | Needs increase |

## Food Pattern Notes
- More whole grains and produce than baseline
- Less refined grain intake
- Better meal regularity on weekdays than weekends

## Top Priorities
1. Raise fiber toward 30 g/day with legumes, fruit, vegetables, oats, and higher-fiber starches.
2. Improve unsaturated-fat sources: nuts, seeds, olive oil, and fatty fish.
3. Protect the sodium gains by limiting convenience foods and restaurant meals.
```

### Nutrient Adequacy Report

```markdown
# Nutrient Adequacy Review

## Likely Adequate
- Protein
- Vitamin B12
- Iron

## Borderline or Likely Low
- Fiber
- Calcium
- Folate
- Vitamin D

## Watch Areas
- Sodium is acceptable on average but spikes on weekends.
- Potassium appears lower than ideal for a general healthy pattern; individualize if kidney disease or related medication use is present.

## Interpretation
- These categories are based on estimated intake, not lab-confirmed deficiency.
- The most actionable gaps are fiber, calcium-rich foods, and overall produce volume.

## Food-First Actions
1. Add one calcium-rich food daily.
2. Replace at least one refined-grain serving with a whole-grain or legume option.
3. Increase vegetables at lunch and dinner.
```

### Correlation Review

```markdown
# Nutrition and Health Correlation Review

## Data Compared
- Nutrition logs: 90 days
- Sleep logs: 90 days
- Weight logs: 90 days

## Observed Patterns
- Higher-fiber days were associated with less evening snacking.
- Late caffeine intake was associated with shorter sleep on logged weekdays.
- Weekend calorie spikes aligned with slower weekly weight loss.

## Interpretation
- These are descriptive associations from self-tracked data.
- They suggest useful behavior targets but do not prove cause and effect.

## Practical Adjustments
1. Move caffeine earlier in the day.
2. Keep a higher-fiber afternoon meal or snack on workdays and weekends.
3. Pre-plan one weekend dinner to reduce drift.
```

### Recommendation Report

```markdown
# Personalized Nutrition Recommendations

## What to Keep
- Protein intake is reasonably steady.
- Produce intake is moving in the right direction.
- Sodium control has improved.

## What to Change Next

### Priority 1: Improve Fiber Intake
- Add oats, beans, lentils, berries, fruit, and higher-fiber starches.
- Increase gradually to reduce GI discomfort.

### Priority 2: Improve Meal Composition
- Build meals around protein, produce, and a higher-fiber carbohydrate.
- Reduce reliance on low-satiety snack foods.

### Priority 3: Review Vitamin D Strategy
- Food sources alone are often insufficient.
- If the user is supplementing, frame this as monitoring and clinician-aligned follow-up, not self-prescribed dose increases.

## Suggested Next Checkpoint
- Reassess in 2 to 4 weeks with updated logs and body-weight trend data.
```

## Data Structure

In Plate, `get_health_summary` returns **daily per-nutrient aggregates** (one row per nutrient type, with totals) and `get_health_data` returns individual entries. The per-meal JSON below illustrates a fully detailed log — use it as the shape to reason about when the user supplies a detailed food log or a photo analysed by `analyze_photo`, not as a literal Plate response.

### Example Nutrition Log

```json
{
  "date": "2026-05-29",
  "meals": [
    {
      "type": "breakfast",
      "time": "07:30",
      "foods": ["eggs", "milk", "whole-wheat bread"],
      "calories": 450,
      "macronutrients": {
        "protein_g": 20,
        "carbs_g": 55,
        "fat_g": 15,
        "fiber_g": 5,
        "saturated_fat_g": 5,
        "monounsaturated_fat_g": 6,
        "polyunsaturated_fat_g": 3,
        "trans_fat_g": 0.1
      },
      "micronutrients": {
        "vitamin_a_mcg": 150,
        "vitamin_c_mg": 5,
        "vitamin_d_mcg": 1.5,
        "vitamin_e_mg": 1,
        "vitamin_k_mcg": 5,
        "thiamine_mg": 0.3,
        "riboflavin_mg": 0.4,
        "niacin_mg": 4,
        "vitamin_b6_mg": 0.1,
        "folate_mcg": 30,
        "vitamin_b12_mcg": 0.6,
        "calcium_mg": 250,
        "iron_mg": 2,
        "magnesium_mg": 40,
        "phosphorus_mg": 200,
        "zinc_mg": 2,
        "selenium_mcg": 10,
        "potassium_mg": 350,
        "sodium_mg": 300
      },
      "special_nutrients": {
        "omega_3_g": 0.1,
        "choline_mg": 150
      }
    }
  ],
  "daily_summary": {
    "total_calories": 2000,
    "total_macronutrients": {
      "protein_g": 80,
      "carbs_g": 250,
      "fat_g": 65,
      "fiber_g": 30
    },
    "rda_achievement": {
      "protein": 100,
      "vitamin_c": 85,
      "vitamin_d": 35,
      "calcium": 90,
      "iron": 75
    },
    "goal_achieved": true
  }
}
```

## Algorithms

These are a **method reference, not executable code** — the agent has no runtime and will not run them. Apply the logic yourself to values returned by the tools, and never fabricate an input or an output. For windowed trends, request the rolling averages and daily totals from `get_health_summary` (it computes them server-side); do not recompute them from raw entries.

### Nutrient Adequacy Classification

```python
def classify_nutrient(actual_intake, target_value, ul_value=None):
    if target_value is None:
        return {"status": "unknown"}

    ratio = actual_intake / target_value

    if ul_value is not None and actual_intake > ul_value:
        status = "above_ul"
    elif ratio < 0.7:
        status = "likely_low"
    elif ratio < 1.0:
        status = "borderline"
    else:
        status = "meets_or_exceeds_target"

    return {
        "achievement_rate": round(ratio * 100, 1),
        "status": status,
    }
```

### Simple Diet-Quality Heuristic

```python
def calculate_diet_quality_score(day):
    score = 0

    # adequacy
    if day["fruit_servings"] >= 2:
        score += 10
    if day["vegetable_servings"] >= 3:
        score += 15
    if day["whole_grain_servings"] >= 3:
        score += 10
    if day["protein_distribution_ok"]:
        score += 10
    if day["fiber_g"] >= 25:
        score += 15

    # moderation
    if day["sodium_mg"] <= 2300:
        score += 10
    if day["added_sugars_pct"] < 10:
        score += 15
    if day["saturated_fat_pct"] < 10:
        score += 15

    return min(score, 100)
```

This score is a model-estimated heuristic — a rough planning signal, not a validated or tool-computed clinical metric.

## Sample User Prompts that Should Activate This Skill

- "Analyze my nutrition over the last three months."
- "Am I getting enough protein / fiber / calcium?"
- "Is my diet quality improving?"
- "How does my sodium intake look?"
- "Why am I always hungry in the evenings?"
- "Does my caffeine intake affect my sleep?"
- "What should I change in my diet to support my weight goal?"
- "Are my vitamin D levels something I should worry about?"

## Safety Guardrails

### This Skill Can Help With

- nutrition-data review and statistics
- general diet-quality analysis
- intake-trend analysis and visualization
- likely-adequacy screening against DRIs
- food-based improvement ideas
- general supplement-safety prompting and interaction awareness

### This Skill Must Not Do

- diagnose nutrient-deficiency disorders
- prescribe therapeutic diets for complex disease without escalation
- replace a registered dietitian or physician
- recommend medication changes
- infer food allergy or intolerance from weak pattern data alone

### Red-Flag Detection

Surface and escalate when the data suggests any of the following. Excess thresholds use established Tolerable Upper Intake Levels; the deficiency and energy figures are screening prompts, not diagnoses.

**Sustained nutrient excess**
- Vitamin A `> 3000 µg RAE/day`
- Vitamin D `> 100 µg/day`
- Iron `> 45 mg/day`
- Selenium `> 400 µg/day`
- Sodium `> 2300 mg/day` (sustained)

**Possible nutrient deficiency (intake screen)**
- Vitamin D `< 10 µg/day`, or serum `< 12 ng/mL`
- Vitamin B12 `< 1.5 µg/day` (especially vegetarians and vegans)
- Iron `< 6 mg/day` (women of reproductive age)
- Calcium `< 500 mg/day`

**Abnormal energy intake**
- Sustained `< 1200 kcal/day` (possible under-fueling or undernutrition)
- Sustained `> 3500 kcal/day`

**Concerning dietary pattern**
- Fiber `< 10 g/day`
- Added sugars `> 25%` of calories
- Saturated fat `> 15%` of calories

### Escalate to a Clinician or Registered Dietitian When

- intake is extremely low or highly restricted
- there is rapid or unintentional weight loss
- there are signs of eating-disorder risk
- the user is pregnant, postpartum, or breastfeeding
- the user has kidney disease, liver disease, GI malabsorption, or advanced diabetes-management needs
- the user is considering high-dose supplementation
- symptoms and estimated intake together suggest a clinically important deficiency

### Recommendation Tiers

- **General guidance** — based on DRIs/RDA and suitable for the general population without medical supervision.
- **Contextual guidance** — based on the user's data and conditions; should be combined with personal context and, where relevant, a dietitian's input.
- **Medical guidance** — anything touching disease management, therapeutic diets, or supplementation; requires clinician confirmation. Never advise adjusting prescribed medication.

### Supplement Safety Rules

- Prefer food-first correction whenever practical.
- Flag sustained intake above established `UL` values.
- Remind users that supplements can interact with medications.
- Use extra caution with fat-soluble vitamins (A, D, E, K), iron, and multi-ingredient weight-loss products.

### Medical Disclaimer

This skill provides educational nutrition guidance only. It does not diagnose, treat, or replace medical care or a registered dietitian.

## Reference Resources

- [NIDDK: Eating & Physical Activity to Lose or Maintain Weight](https://www.niddk.nih.gov/health-information/weight-management/adult-overweight-obesity/eating-physical-activity)
- [Dietary Guidelines for Americans](https://www.dietaryguidelines.gov/)
- [USDA FoodData Central](https://fooddatacentral.usda.gov/)
- [WHO Nutrition](https://www.who.int/health-topics/nutrition)
- [NIH Office of Dietary Supplements — Fact Sheets](https://ods.od.nih.gov/factsheets/list-all/)

---

**Skill version**: v1.3 (Plate adaptation — data sources point at Plate tools; full nutrient panel via Health Sync; locale-aware DRIs; conservative screening framing with source-specific Red-Flag thresholds preserved.)
**Last updated**: 2026-05-29
**Maintainer**: Acara App
