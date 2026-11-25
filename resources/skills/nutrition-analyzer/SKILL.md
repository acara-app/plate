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

> **Data origin in Plate.** Health Sync imports time-series data from Apple HealthKit. Altani can also record entries with `log_health_entry`. For a meal photo, `analyze_photo` estimates nutrients and the user confirms the entry before logging. Use the tools listed in *Where to Get the Data* for recorded values.

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

Reference intake standards vary by region. When the user specifies a region, use the applicable reference and name it. Otherwise use the US Dietary Guidelines / WHO fallback and name the standard used.

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

Use the records returned by Plate and information the user provides. Identify gaps explicitly.

Call `get_user_profile` directly. Delegate health records and goals to `health_specialist`, diet references and serving guidelines to `nutrition_specialist`, and fitness goals to `fitness_specialist`. Include the date range, metric and relevant profile facts in each task.

| What you need | Plate upstream | Tool to call | Notes |
|---|---|---|---|
| Profile (age, sex, height, dietary preferences, conditions, medications) | manual onboarding + profile edits | `get_user_profile` | Request the smallest relevant section, such as `dietary_preferences` for food preferences or `safety` for allergies, conditions and medications. |
| Calorie, macronutrient & micronutrient intake (calories, carbs, protein, fat and its subtypes, fiber, sugar, sodium, potassium, vitamins, minerals) | synced and manually logged food entries | `get_health_summary` (daily totals), `get_health_data` (individual entries) | Use `type: "food"` for the available daily nutrient panel. Treat days without entries as logging gaps. Report missing nutrients explicitly. |
| Food groups, serving sizes and diet reference data | bundled reference content | `nutrition_specialist` | Ask for the relevant USDA guideline, daily servings for the user's calorie target, or a specific diet reference such as the Mediterranean nutrient score card. |
| Body weight | Apple HealthKit via **Health Sync** + manual | `get_health_summary`, `get_health_data` | For diet ↔ weight correlation. |
| Activity (steps, exercise minutes, training) | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_fitness_goals` | For fueling adequacy and under-eating checks in active users. |
| Sleep | Apple HealthKit via **Health Sync** | `get_health_summary`, `get_health_data` | For caffeine-timing and late-meal correlations. |
| Blood pressure | Apple HealthKit via **Health Sync** + manual | `get_health_summary`, `get_health_data` | For sodium and potassium-to-sodium correlation; flag uncontrolled BP and escalate. |
| Glucose | Apple HealthKit (CGM) via **Health Sync** + manual | `get_health_summary`, `get_health_data`, `get_meal_glucose_response` | Ask `health_specialist` for observed responses to logged meals. Use `glucose_spike_specialist` for a prospective food comparison or spike estimate. |
| Medications | manual records + Health Sync (where supported) | `get_health_data` (medication-typed entries); `get_user_profile` for the static medication list | Flag medications affecting appetite, glucose, fluid balance, or nutrient absorption. |
| Health goals & phase | profile + goal store | `get_health_goals`, `get_fitness_goals` | Reconcile the user's stated goal with what the data shows. |

If a tool call returns no data over the requested window, say so explicitly and ask the user whether to widen the window or continue with what is available — never extrapolate from missing data.

**Missing nutrient data.** Health Sync tracks total sugar. Neither Health Sync nor meal photo analysis provides added sugar, trans fat, omega-3 (EPA/DHA/ALA), or choline values. Include these only when the user supplies the values.

## Workflow

### Step 1: Clarify the Analysis Scope

Identify:

- the main question: trends, adequacy, quality, correlation, or recommendations
- time range: week, month, quarter, or custom
- analysis depth: macros only, targeted nutrients, or full review
- relevant context: weight goal, training status, disease history, dietary restrictions

### Step 2: Read the Data

Ask `health_specialist` for `get_health_summary` with `type: "food"` over the chosen window. This returns the daily nutrient values that were logged. Have it drill into specific days with `get_health_data` where the summary surfaces an anomaly. Ask for weight, activity, sleep, blood-pressure, or glucose streams when the question calls for a correlation.

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

## Report Examples

When you need a report format for nutrition trends, nutrient adequacy, correlations, or recommendations, call `LoadSkill` with `name: "nutrition-analyzer"` and `path: "report-examples.md"`. Use only the user’s own data in the report.

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

Use these methods to interpret the values the tools return. For a rolling average, ask `health_specialist` to calculate it from the daily aggregates returned by `get_health_summary` and report how many days had data.

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

**Skill version**: v1.4 (Plate adaptation)
**Last updated**: 2026-11-25
**Maintainer**: Acara App
