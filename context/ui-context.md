# UI Context

## Purpose

This document defines the global visual system for every BuatPRD screen. It is
the default rule set for public pages, forms, workspaces, settings, empty
states, and future product surfaces. Do not create page-specific visual
systems unless a documented product requirement genuinely needs one.

## Product Character

BuatPRD should feel like a focused, high-trust product workspace: calm,
capable, editorial, and expensive without being ornamental.

- Use an obsidian foundation, luminous violet intelligence accent, and a
  restrained champagne highlight.
- Avoid generic navy, pale corporate blue, flat white panels, noisy gradients,
  excessive glassmorphism, and decorative effects without a purpose.
- Establish hierarchy through scale, spacing, weight, and surface depth before
  adding color.
- Use one clear primary action per view. Secondary actions must remain quiet.
- Keep copy concise, concrete, and sentence case. Product copy is Indonesian
  unless the surrounding product requirement says otherwise.
- Use Figtree for all interface text and outline Heroicons or consistent inline
  SVGs for icons. Never mix unrelated icon styles.
- Class-based dark mode is `.dark`.

## Global Canvas

- Every page root must explicitly set `background-color: var(--bg-main)` and
  `color: var(--text-main)` so transparent sections never reveal the browser
  or body fallback background.
- The canvas is a layered system, not a collection of unrelated cards:
  `--bg-main` is the default, `--bg-elevated` marks a section or toolbar,
  `--bg-card` groups related content, and `--bg-sidebar` anchors navigation or
  closing areas.
- Keep visual noise behind content. Aurora, grid, glow, and blur effects must
  be low opacity and must never sit directly beneath important text.
- Use a centered content container with a maximum width of `80rem`. Very wide
  data-heavy surfaces may use `90rem` with an explicit reason.
- Use consistent horizontal padding: `1.5rem` on small screens and `2rem` on
  large screens. Never let content touch the viewport edge.
- Use `py-16` on compact screens and `py-24` on large screens for major
  sections. Reduce spacing only when content density requires it.

## Surface Hierarchy

- **Canvas**: `--bg-main`, used for page background and breathing room.
- **Navigation**: `--bg-sidebar`, used for sidebars, compact headers, and
  footer-like closing surfaces.
- **Card**: `--bg-card`, used for grouped content with `--border-soft`.
- **Elevated**: `--bg-elevated`, used for toolbars, highlighted rows, input
  areas, and deliberate section bands.
- Use a dark layered gradient on important cards when it improves depth:
  blend `--bg-card` with 3-6% of `--brand`, never with white.
- A card is for a meaningful group of information. Do not wrap every paragraph
  or small metric in its own card.
- Use borders before shadows. Shadows are soft, wide, and low-opacity; avoid
  default black drop shadows and heavy floating panels.
- Interactive cards may lift by 2px on hover. Static cards must not move.

## Color System

### Brand

- **Light**: `#6257dc` (premium violet)
- **Light Hover**: `#4d43c4`
- **Dark**: `#8b7cf6` (luminous violet)
- **Dark Hover**: `#b0a6ff`
- **Light Foreground**: `#ffffff`
- **Dark Foreground**: `#120f1b`
- **Glow Light**: `rgba(98, 87, 220, 0.22)`
- **Glow Dark**: `rgba(139, 124, 246, 0.30)`
- **Secondary Accent**: `#e5ae7d` (champagne, use sparingly)

### Background Tokens

| Token | Light | Dark |
|-------|-------|------|
| `--bg-main` | `#f7f7f5` | `#080a12` |
| `--bg-sidebar` | `#ffffff` | `#0d101b` |
| `--bg-card` | `#ffffff` | `#121624` |
| `--bg-elevated` | `#efefeb` | `#1a1f30` |

### Text Tokens

| Token | Light | Dark |
|-------|-------|------|
| `--text-main` | `#191a24` | `#f5f1ea` |
| `--text-muted` | `#5d6170` | `#b9bdc9` |
| `--text-soft` | `#767b89` | `#8d93a5` |

Use `--text-main` for titles, values, active labels, and important metadata.
Use `--text-muted` for descriptions and secondary labels. Use `--text-soft`
only for tertiary metadata, timestamps, and low-priority hints.

### Border and Semantic Tokens

| Token | Light | Dark |
|-------|-------|------|
| `--border-soft` | `#e5e3de` | `#24283a` |
| `--border-strong` | `#cfccd9` | `#3a4058` |
| `--success` | `#138a69` | `#43d3a8` |
| `--warning` | `#b36e16` | `#f0bd73` |
| `--danger` | `#c8485b` | `#ff788a` |

Semantic colors communicate state, not decoration. Always pair status color
with a label, icon, or text explanation. Do not use semantic colors for large
background areas or arbitrary feature decoration.

## Contrast Rules

- Normal text must meet at least 4.5:1 contrast. Large text must meet at least
  3:1. Placeholder text is not a substitute for a label.
- Do not place muted text over gradients, glows, images, or busy grid patterns.
  Put the text on a stable surface or add a solid surface behind it.
- Do not use `--text-soft` for headings, buttons, form labels, or required
  instructions.
- Brand backgrounds must use the matching brand foreground token. Do not assume
  white text works on every brand shade.
- Never communicate meaning with color alone. Include a visible label, icon,
  shape, or position cue.

## Typography

- **Family**: `'Figtree', system-ui, sans-serif`.
- **Body**: `1rem / 1.5`, regular 400.
- **Label**: `0.75rem / 1.25`, semibold 600. Uppercase tracking is for short
  metadata labels only.
- **Page title**: `2.25rem` desktop, `1.875rem` mobile, bold 700.
- **Section title**: `1.875rem`, bold 700.
- **Card title**: `1rem`, semibold 600.
- **Metric value**: `2.25rem` or `3rem`, bold 700.
- Use no more than three text sizes in one compact component.
- Use `gradient-text` only for a short phrase or key metric, never for a full
  paragraph or an entire page heading.
- Keep line length around 45-75 characters for readable descriptions.

## Spacing and Shape

- Use a 4px spacing base. Prefer `gap-4`, `gap-6`, `gap-8`, and `gap-12`.
- `sm`: 4px, `md`: 6px, `lg`: 8px, `xl`: 12px, `2xl`: 16px,
  `full`: 9999px.
- Inputs and buttons use `lg`. Cards and major panels use `2xl`.
- Interactive targets must be at least 44px high and have enough horizontal
  padding for touch use.
- Align card headers, values, actions, and list metadata to a shared grid.

## Global Components

- **Primary button**: brand background, matching brand foreground, `rounded-lg`,
  44px minimum height, semibold text, visible focus ring, subtle brand glow,
  and a small hover lift.
- **Secondary button**: transparent or elevated background with
  `--border-strong`; never compete with the primary button.
- **Ghost button**: no border, muted text, subtle elevated background on hover.
- **Danger button**: semantic danger background, clear destructive wording,
  and no decorative glow.
- **Card**: `--bg-card`, `--border-soft`, `rounded-2xl`, responsive padding,
  and layered depth only when it helps grouping.
- **Input**: `--bg-main` or `--bg-elevated`, `--border-soft`, `rounded-lg`,
  44px minimum height, readable placeholder, and a 3px brand focus ring.
- **Form label**: always visible above the input. Errors sit directly below the
  field and do not rely on toast notifications alone.
- **Badge/status**: pill shape with a semantic tint, readable text, and an icon
  or label that remains understandable without color.
- **Navigation**: one clear active state, quiet inactive states, and no more
  than two levels visible at once.
- **List/table**: use aligned columns, dividers, readable row hover, and a
  designed empty state. Do not use dense borders around every cell.
- **Modal**: stable `--bg-card` surface, `rounded-2xl`, clear title, explicit
  close control, and a backdrop that does not erase context completely.

## Interaction and State

Every interactive component must account for these states where applicable:

- Default
- Hover
- Focus-visible
- Active/pressed
- Disabled
- Loading/processing
- Success
- Error/validation
- Empty/no result

Use `transition` for color, border, opacity, and small transforms only. Page
entry may use `animate-fade-in` or `animate-slide-up`; do not animate every
card independently or use motion to convey required information.

## Responsive Rules

- Design from 320px upward. No horizontal scrolling for normal page content.
- Collapse multi-column layouts before text becomes cramped, not only at the
  smallest breakpoint.
- On mobile, preserve the primary action and move secondary actions below or
  into a menu. Never hide the only route to continue.
- Sidebars become a top bar plus accessible drawer. Tables may become stacked
  list rows, but metadata and actions must remain discoverable.
- Keep touch targets at least 44px and maintain readable line height on mobile.

## Accessibility

- Keyboard focus is visible on all links, buttons, inputs, selects, dialogs,
  and menu controls.
- Icon-only controls require an accessible label. Decorative SVGs use
  `aria-hidden="true"`.
- Use semantic headings in order, real buttons for actions, real links for
  navigation, and labels connected to form controls.
- Preserve browser autocomplete attributes on authentication and profile forms.
- Respect `prefers-reduced-motion` and provide equivalent non-animated states.
- Do not remove information on hover only; it must be available to keyboard and
  touch users.

## Implementation Discipline

- Prefer existing tokens and utilities: `btn-primary`, `btn-secondary`,
  `btn-ghost`, `card`, `card-hover`, `landing-card`, `input-base`,
  `section-title`, `section-subtitle`, `container-base`, `gradient-text`,
  `hero-orb`, `glow-brand`, and `bg-grid-pattern`.
- Add a shared token or utility before adding a one-off color, shadow, or
  surface style in a page component.
- Every new page must inherit the global canvas and component rules. Do not add
  page-specific color palettes, radius systems, or typography systems.
- Keep content and layout responsive without duplicating markup for desktop and
  mobile unless the interaction model genuinely differs.
- Do not use placeholder links for important actions. Use real Inertia routes,
  real actions, or intentional anchors.
