# Acara Design System

## 1. Visual Theme & Atmosphere

Acara is a modern, confident platform built on clarity and momentum. The design communicates precision and growth through a vibrant emerald-green identity set against clean, airy surfaces. Unlike traditional corporate green (muted, conservative) or neon green (aggressive, trendy), Acara's green feels organic and intentional—like fresh growth.

The visual language is **structured but warm**. We use generous whitespace, crisp edges, and purposeful scale to guide users without overwhelming them. The aesthetic is less "billboard protest" and more "architectural blueprint"—every element has a reason for being where it is.

**Key Characteristics:**
- Emerald Green (`#10b981`) as the primary brand color—fresh, trustworthy, alive
- Near-Black (`#111827`) for primary text and strong structural elements
- Clean geometric shapes with a deliberate radius system
- Subtle, functional animations (lift, not bounce)
- Layered neutral surfaces for depth without heavy shadows
- OpenType `"calt"` and `"liga"` enabled for refined typography

---

## 2. Color Palette & Roles

### Primary Brand

| Token | Hex | Role |
|-------|-----|------|
| **Acara Emerald** | `#10b981` | Primary CTA, active states, brand moments |
| **Acara Dark** | `#064e3b` | Button text on emerald, deep accents, hover states |
| **Acara Night** | `#111827` | Primary text, headings, dark sections |
| **Acara Mint** | `#d1fae5` | Soft surfaces, badge backgrounds, success tints |
| **Acara Light** | `#ecfdf5` | Subtle green-tinted backgrounds, hover surfaces |

### Semantic Colors

| Token | Hex | Role |
|-------|-----|------|
| **Success** | `#059669` | Positive states, confirmations, growth indicators |
| **Danger** | `#dc2626` | Errors, destructive actions, critical alerts |
| **Warning** | `#d97706` | Cautions, pending states, attention required |
| **Info** | `#0ea5e9` | Informational highlights, tips, neutral accents |

### Neutral Scale

| Token | Hex | Role |
|-------|-----|------|
| **Neutral 50** | `#f9fafb` | Page background, cards |
| **Neutral 100** | `#f3f4f6` | Subtle backgrounds, dividers |
| **Neutral 200** | `#e5e7eb` | Borders, inactive states |
| **Neutral 400** | `#9ca3af` | Placeholder text, disabled elements |
| **Neutral 600** | `#4b5563` | Secondary text, captions |
| **Neutral 900** | `#111827` | Primary text, headings |

### Usage Rules
- **Emerald on white**: Primary CTAs, key actions
- **White on emerald**: Reversed buttons, dark mode CTAs
- **Night on mint**: Success messages, positive badges
- **Never use emerald as large background areas**—it is for accents and actions only
- **Neutral 200** is the default border color; use **Emerald** borders only for focus or active states

---

## 3. Typography Rules

### Font Families

```css
/* Sans-serif (Display, Headings, Body, UI) */
--font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif,
    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
    'Noto Color Emoji';

/* Monospace (Code, Data, API Keys) */
--font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Monaco,
    Consolas, 'Liberation Mono', 'Courier New', monospace;
```

**CDN Loading (Bunny Fonts):**
```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500" rel="stylesheet" />
```

> Acara uses **Inter** for *everything*—display, headings, body, and UI. Hierarchy is created through **weight, size, and tracking**, not font mixing. This keeps the system fast, consistent, and unmistakably Acara.

### Hierarchy

| Role | Size | Weight | Line Height | Letter Spacing | Usage |
|------|------|--------|-------------|----------------|-------|
| **Display** | 72px (4.5rem) | 800 | 1.0 | -0.02em | Hero headlines, landing pages |
| **H1** | 48px (3rem) | 700 | 1.1 | -0.02em | Page titles, major sections |
| **H2** | 36px (2.25rem) | 700 | 1.2 | -0.015em | Section headings |
| **H3** | 24px (1.5rem) | 600 | 1.3 | -0.01em | Card titles, subsections |
| **H4** | 20px (1.25rem) | 600 | 1.4 | -0.005em | Labels, small headings |
| **Body Large** | 18px (1.125rem) | 400 | 1.6 | 0 | Primary reading text |
| **Body** | 16px (1rem) | 400 | 1.6 | 0 | Standard reading text |
| **Body Strong** | 16px (1rem) | 600 | 1.6 | 0 | Emphasized body text |
| **Small** | 14px (0.875rem) | 400 | 1.5 | 0 | Captions, metadata |
| **Small Strong** | 14px (0.875rem) | 600 | 1.5 | 0 | Labels, badges |
| **Tiny** | 12px (0.75rem) | 500 | 1.4 | 0.01em | Timestamps, legal |

### Principles
- **Weight 700 is the display workhorse**: It creates confident headlines without the heaviness of 900
- **Line-height 1.1–1.3 for headings**: Tight but not overlapping—breathable density
- **Line-height 1.6 for body**: Comfortable for extended reading
- **Negative letter-spacing on large text**: Keeps headlines compact and intentional
- **`"calt"`, `"liga"`, `"kern"` enabled globally**: Refines text rendering
- **Never use font-weight below 400**: Acara is confident, not fragile

---

## 4. Component Stylings

### Buttons

**Primary Button**
- Background: `#10b981` (Acara Emerald)
- Text: `#ffffff` (white)
- Padding: 12px 24px
- Border-radius: 8px
- Font: 16px / weight 600
- Hover: `translateY(-1px)`, background shifts to `#059669`
- Active: `translateY(0)`, background `#047857`
- Focus: `ring-2 ring-offset-2 ring-emerald-500`

**Secondary Button**
- Background: `#ffffff`
- Border: 1px solid `#e5e7eb`
- Text: `#111827`
- Padding: 12px 24px
- Border-radius: 8px
- Hover: border color `#10b981`, background `#ecfdf5`
- Active: background `#d1fae5`

**Ghost Button**
- Background: transparent
- Text: `#10b981`
- Padding: 12px 24px
- Hover: background `#ecfdf5`
- Active: background `#d1fae5`

**Danger Button**
- Background: `#dc2626`
- Text: `#ffffff`
- Hover: `#b91c1c`
- Active: `#991b1b`

> **Animation rule**: All buttons use `transform` for hover/active states (GPU-accelerated). Duration: `150ms`. Easing: `cubic-bezier(0.4, 0, 0.2, 1)`.

### Cards

**Standard Card**
- Background: `#ffffff`
- Border: 1px solid `#e5e7eb`
- Border-radius: 12px
- Padding: 24px
- Shadow: none (flat by default)
- Hover (interactive): `translateY(-2px)`, shadow `0 4px 6px -1px rgba(0,0,0,0.05)`

**Elevated Card**
- Background: `#ffffff`
- Border-radius: 12px
- Padding: 24px
- Shadow: `0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03)`

**Feature Card**
- Background: `#f9fafb`
- Border: 1px solid `#e5e7eb`
- Border-radius: 16px
- Padding: 32px
- Optional: top border 4px solid `#10b981`

### Inputs

**Text Input**
- Background: `#ffffff`
- Border: 1px solid `#e5e7eb`
- Border-radius: 8px
- Padding: 10px 14px
- Font: 16px / weight 400
- Placeholder: `#9ca3af`
- Focus: border `#10b981`, ring `0 0 0 3px rgba(16,185,129,0.15)`
- Error: border `#dc2626`, ring `0 0 0 3px rgba(220,38,38,0.15)`

### Badges

| Variant | Background | Text | Usage |
|---------|-----------|------|-------|
| Default | `#f3f4f6` | `#374151` | Neutral status |
| Emerald | `#d1fae5` | `#065f46` | Success, active, positive |
| Dark | `#111827` | `#ffffff` | Strong emphasis |
| Outline | transparent | `#10b981` | Border 1px `#10b981` | Subtle highlight |

- Border-radius: 9999px (pill)
- Padding: 4px 12px
- Font: 12px / weight 600

---

## 5. Layout Principles

### Spacing Scale

Based on 4px grid:

| Token | Value |
|-------|-------|
| `space-1` | 4px |
| `space-2` | 8px |
| `space-3` | 12px |
| `space-4` | 16px |
| `space-5` | 20px |
| `space-6` | 24px |
| `space-8` | 32px |
| `space-10` | 40px |
| `space-12` | 48px |
| `space-16` | 64px |
| `space-20` | 80px |
| `space-24` | 96px |

> Use `space-6` (24px) as the default gap between related elements. Use `space-16` (64px) for major section breaks.

### Container
- Max-width: 1280px
- Padding: `space-4` (16px) mobile, `space-8` (32px) desktop
- Centered with auto margins

### Grid
- 12-column system
- Gap: `space-6` (24px) default
- Breakpoints:
  - **sm**: 640px
  - **md**: 768px
  - **lg**: 1024px
  - **xl**: 1280px

---

## 6. Depth & Elevation

Acara uses a restrained shadow system. Depth is earned, not default.

| Level | Shadow | Usage |
|-------|--------|-------|
| **Flat** | none | Default state, cards on neutral backgrounds |
| **Raised** | `0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03)` | Cards on white backgrounds, dropdowns |
| **Elevated** | `0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -2px rgba(0,0,0,0.03)` | Hover states, modals |
| **Floating** | `0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -4px rgba(0,0,0,0.03)` | Dropdown menus, tooltips |
| **Overlay** | `0 25px 50px -12px rgba(0,0,0,0.15)` | Dialogs, drawers |

**Shadow Philosophy**: Shadows are desaturated and subtle. They suggest lift, not glow. We never use colored shadows (e.g., green-tinted shadows).

---

## 7. Border Radius Scale

| Token | Value | Usage |
|-------|-------|-------|
| `radius-sm` | 6px | Small buttons, tags, inline elements |
| `radius-md` | 8px | Standard buttons, inputs, small cards |
| `radius-lg` | 12px | Cards, panels, containers |
| `radius-xl` | 16px | Feature cards, modals |
| `radius-2xl` | 24px | Hero sections, large cards |
| `radius-full` | 9999px | Badges, pills, avatars |

> Default card radius is 12px. Buttons are 8px. Avoid mixing too many radii in a single view.

---

## 8. Motion & Animation

### Principles
- **Purposeful**: Every animation guides attention or confirms action
- **Fast**: Most transitions are 150ms–200ms
- **Subtle**: We fade, lift, and slide—we don't bounce, jiggle, or spin

### Standard Durations
| Context | Duration |
|---------|----------|
| Micro (hover, active) | 150ms |
| Small (dropdowns, tooltips) | 200ms |
| Medium (modals, drawers) | 300ms |
| Large (page transitions) | 400ms |

### Easing
| Name | Value | Usage |
|------|-------|-------|
| **Ease default** | `cubic-bezier(0.4, 0, 0.2, 1)` | Most UI transitions |
| **Ease in** | `cubic-bezier(0.4, 0, 1, 1)` | Elements exiting |
| **Ease out** | `cubic-bezier(0, 0, 0.2, 1)` | Elements entering |
| **Ease bounce** | `cubic-bezier(0.34, 1.56, 0.64, 1)` | Rare—only for celebratory moments |

### Patterns
- **Button hover**: `translateY(-1px)` + color shift
- **Card hover**: `translateY(-2px)` + shadow elevation
- **Modal enter**: `opacity 0→1` + `scale(0.98→1)` + `translateY(8px→0)`
- **Toast enter**: `translateX(100%→0)` + `opacity 0→1`
- **Skeleton**: `shimmer` animation, `1.5s` loop

---

## 9. Responsive Behavior

| Breakpoint | Width | Key Changes |
|------------|-------|-------------|
| **Mobile** | < 640px | Single column, stacked layout, reduced padding (`space-4`), hamburger nav |
| **Tablet** | 640px – 1024px | 2-column grids, side-by-side layouts where appropriate, full nav visible if space allows |
| **Desktop** | 1024px – 1280px | Full multi-column layouts, max content width |
| **Wide** | > 1280px | Centered container, increased whitespace, optional sidebar expansions |

### Responsive Typography
- Display scales down to 48px on mobile
- H1 scales down to 32px on mobile
- Body remains 16px across all breakpoints (never smaller than 16px for inputs)

---

## 10. Do's and Don'ts

### Do
- Use **Acara Emerald** for primary actions and brand moments
- Use **weight 700** for headings—confident without being heavy
- Apply `letter-spacing: -0.02em` on display text for tight, intentional headlines
- Use `translateY(-1px)` or `translateY(-2px)` for hover lifts
- Enable `calt`, `liga`, `kern` on all text
- Use **Neutral 200** (`#e5e7eb`) as the default border color
- Maintain generous whitespace—let elements breathe
- Use the 4px spacing scale consistently

### Don't
- Don't use font-weight 900—Acara tops out at 800 for display
- Don't use the emerald green for large background surfaces (hero sections should be neutral or dark, not green)
- Don't use bounce or elastic animations for standard UI
- Don't use colored shadows (green-tinted shadows are not part of the system)
- Don't use border-radius larger than 16px for standard cards
- Don't mix multiple font families—Inter is the only typeface
- Don't use light gray text (`Neutral 400`) for primary content—it's for placeholders only
- Don't skip focus rings for accessibility

---

## 11. Dark Mode

When dark mode is active:

| Element | Light Mode | Dark Mode |
|---------|-----------|-----------|
| Background | `#f9fafb` | `#0f172a` |
| Card background | `#ffffff` | `#1e293b` |
| Primary text | `#111827` | `#f9fafb` |
| Secondary text | `#4b5563` | `#94a3b8` |
| Border | `#e5e7eb` | `#334155` |
| Primary button | `#10b981` | `#10b981` |
| Primary button hover | `#059669` | `#34d399` |
| Success surface | `#d1fae5` | `#064e3b` |

> Emerald green becomes even more vibrant in dark mode. Use it sparingly to create moments of emphasis.

---

## 12. Example Component Prompts

### Hero Section
"Create a hero: Neutral-50 (`#f9fafb`) background. Headline at 72px Inter weight 800, line-height 1.0, letter-spacing -0.02em, `#111827` text. Subheadline at 18px weight 400, `#4b5563`. Primary CTA button (`#10b981`, 8px radius, white text, 12px 24px padding). Secondary CTA ghost button. Hover: translateY(-1px) on buttons."

### Feature Card
"Build a feature card: White background, 12px radius, 1px `#e5e7eb` border, 24px padding. Icon in 40px circle with `#ecfdf5` background and `#10b981` icon. Title at 20px weight 600, `#111827`. Body at 16px weight 400, `#4b5563`. Hover: translateY(-2px) + shadow elevation."

### Data Table
"Create a data table: White background, 12px radius, 1px `#e5e7eb` border. Header row: `#f9fafb` background, 14px weight 600 text. Rows: 16px weight 400, `#111827`. Hover row: `#f9fafb`. Sorted column header: `#10b981` text with arrow icon."

### Toast Notification
"Build a success toast: `#064e3b` background, `#ffffff` text, 8px radius, 16px padding. Icon: white checkmark in circle. Slide in from right with 300ms ease-out. Auto-dismiss after 4s with fade-out."

---

## 13. Quick Reference

| Element | Value |
|---------|-------|
| Brand color | `#10b981` |
| Brand dark | `#064e3b` |
| Text primary | `#111827` |
| Text secondary | `#4b5563` |
| Background | `#f9fafb` |
| Card background | `#ffffff` |
| Border default | `#e5e7eb` |
| Button radius | 8px |
| Card radius | 12px |
| Default shadow | none |
| Hover shadow | `0 4px 6px -1px rgba(0,0,0,0.05)` |
| Default duration | 150ms |
| Default easing | `cubic-bezier(0.4, 0, 0.2, 1)` |
