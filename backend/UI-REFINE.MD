# Enterprise UI / UX Design System — Premium Tailwind Edition

## Purpose

This file defines the visual, interaction, layout, and product-design standards for the Ticketing System.

Claude must read this document before making any frontend design decision.

This document is the primary authority for the application's visual system.

The UI should feel like a premium, high-investment enterprise SaaS product built for serious business operations.

The design must communicate:

```text
Trust
Control
Speed
Operational clarity
Precision
Security
Professionalism
Maturity
Scalability
Business confidence
```

The product must never feel like:

```text
A generic admin template
A school project
A low-budget dashboard
A stock Bootstrap theme
A collection of unrelated screens
A visually noisy CRM clone
A flashy consumer app
```

The target is a polished enterprise application with design discipline comparable to top-tier B2B SaaS products.

## Design Maturity Benchmark

Use these products as the visual and interaction quality bar — not to copy their trade dress, but to match their level of craft:

```text
Linear       — speed, keyboard-first interaction, restrained motion
Stripe       — typographic discipline, data density, trustworthiness
Vercel       — contrast control, monochrome confidence, precision
Ramp / Mercury — financial-grade seriousness, numeric clarity
```

If a screen would look out of place in a product review of one of the above, it is not finished.

## The Genericism Trap

`indigo-600` + `slate` + `rounded-xl` + `shadow-sm` + Inter is now the single most common look in AI-generated and template-driven SaaS UI — it is the new "generic Bootstrap." Following this document's tokens literally is necessary but not sufficient. A screen only reaches premium quality when it also has:

```text
One considered, non-default color decision (not just "indigo because it's the default")
A refined, custom shadow/elevation system instead of default Tailwind shadow-sm/md/lg
Deliberate letter-spacing and numeric (tabular) alignment, not browser defaults
At least one signature interaction moment per major surface (see "Signature Product Moments")
```

Spend that intentionality with restraint: one confident decision per surface, everything else quiet and disciplined. See "Design Token Foundation" and "Signature Product Moments" below for exactly where to apply it.

---

# Required Frontend Design Stack

Use:

```text
ReactJS
Tailwind CSS
SweetAlert2
Lucide React or the already-approved icon library
```

Tailwind CSS is the required styling system.

Do not introduce:

```text
Bootstrap
Material UI
Ant Design
Chakra UI
Bulma
Semantic UI
Foundation
another utility CSS framework
```

unless explicitly approved.

Do not mix multiple visual systems.

---

# Design Token Foundation

Every value below should exist once, centrally, and be referenced everywhere. This is what makes a UI feel engineered rather than assembled page-by-page.

## Tailwind v4 Theme Block

```css
@theme {
  /* Brand */
  --color-primary-50: oklch(0.97 0.014 264);
  --color-primary-100: oklch(0.93 0.034 264);
  --color-primary-500: oklch(0.585 0.187 264);
  --color-primary-600: oklch(0.51 0.19 264);
  --color-primary-700: oklch(0.44 0.18 264);

  /* Signature accent — reserved for the ONE brand flourish per surface */
  --color-signature-500: oklch(0.64 0.16 300);

  /* Neutral ink (slightly warmer / deeper than default Tailwind slate) */
  --color-ink-950: oklch(0.16 0.014 264);
  --color-ink-900: oklch(0.21 0.014 264);
  --color-ink-700: oklch(0.37 0.014 264);
  --color-ink-500: oklch(0.54 0.014 264);
  --color-ink-300: oklch(0.83 0.01 264);
  --color-ink-200: oklch(0.90 0.008 264);
  --color-ink-100: oklch(0.95 0.006 264);
  --color-ink-50:  oklch(0.98 0.004 264);

  /* Radius scale */
  --radius-xs: 6px;
  --radius-sm: 8px;
  --radius-md: 10px;
  --radius-lg: 12px;
  --radius-xl: 16px;

  /* Elevation — see full spec below */
  --shadow-xs: 0 1px 2px 0 rgb(15 23 42 / 0.04);
  --shadow-sm: 0 1px 2px -1px rgb(15 23 42 / 0.06), 0 2px 4px -2px rgb(15 23 42 / 0.04);
  --shadow-md: 0 2px 4px -2px rgb(15 23 42 / 0.06), 0 8px 16px -4px rgb(15 23 42 / 0.06);
  --shadow-lg: 0 4px 8px -4px rgb(15 23 42 / 0.08), 0 16px 32px -8px rgb(15 23 42 / 0.10);
  --shadow-xl: 0 8px 16px -8px rgb(15 23 42 / 0.10), 0 24px 48px -12px rgb(15 23 42 / 0.16);
  --shadow-focus: 0 0 0 2px var(--color-primary-500) / 0.3, 0 0 0 4px white;

  /* Motion curves */
  --ease-standard: cubic-bezier(0.2, 0, 0, 1);
  --ease-decelerate: cubic-bezier(0, 0, 0, 1);
  --ease-accelerate: cubic-bezier(0.3, 0, 1, 1);
  --ease-emphasized: cubic-bezier(0.16, 1, 0.3, 1);

  /* Z-index scale */
  --z-dropdown: 20;
  --z-sticky: 30;
  --z-drawer: 40;
  --z-modal: 50;
  --z-toast: 60;
  --z-command-palette: 70;
}
```

Why this over raw Tailwind defaults:

```text
Default Tailwind shadow-sm/md/lg are a single flat drop shadow — they read as "template."
The layered shadows above (two shadows: a tight contact shadow + a soft ambient one)
are how Linear, Stripe, and Vercel achieve a shadow that feels like real light,
not a CSS default.
```

## 8pt Grid Discipline

Every spacing, sizing, and layout value must resolve to a multiple of 4px, with 8px as the primary rhythm. This is non-negotiable — it is one of the highest-leverage, lowest-effort signals of engineering discipline.

```text
Micro spacing (icon-to-label): 4px / 6px
Component internal padding:    8px / 12px / 16px
Section spacing:                24px / 32px
Page-level spacing:             40px / 48px / 64px
```

If a designer or engineer reaches for `13px`, `19px`, or `p-[17px]`, that is a signal to stop and map it back to the scale.

## Numeric & Tabular Data

Every numeric column, KPI value, SLA countdown, and ID must use tabular figures so digits align vertically as they update:

```css
font-variant-numeric: tabular-nums;
```

Apply via `tabular-nums` utility on: KPI values, table numeric columns, SLA countdowns, currency, ticket/reference IDs.

---

# Design Standard

Every page must look intentionally designed.

The frontend should feel:

```text
Calm
Structured
High-signal
Minimal
Fast
Premium
Enterprise-grade
Highly readable
Consistent
Purposeful
```

The design should not rely on decoration to feel premium.

Premium quality should come from:

```text
Excellent hierarchy
Perfect spacing
Controlled density
Strong typography
Reliable component behavior
Consistent status language
Crisp interactions
Good empty states
Professional loading states
Intentional responsive behavior
```

---

# Core Design Principles

## 1. Hierarchy Before Decoration

The user should understand the page within 2–3 seconds.

Every screen must clearly communicate:

```text
Where am I?
What matters?
What needs action?
What is urgent?
What can I do next?
```

Do not make every element visually equal.

Use hierarchy through:

```text
Size
Weight
Spacing
Position
Contrast
Surface elevation
Color
```

---

## 2. Information Density Must Be Controlled

Enterprise software needs data density, but it must remain readable.

Customer screens:

```text
Low-to-moderate density
```

Staff screens:

```text
Moderate density
```

Manager screens:

```text
Moderate-to-high density
```

Admin screens:

```text
Structured high density
```

Do not use huge cards for tiny amounts of information.

Do not compress tables until they become hard to scan.

---

## 3. Primary Actions Must Be Obvious

Every page should have one clear primary action where applicable.

Examples:

```text
Create Ticket
Send Reply
Assign Ticket
Save Changes
Export Report
Add User
```

Primary actions should use:

```text
bg-indigo-600
hover:bg-indigo-700
text-white
```

Secondary actions should not compete visually.

---

## 4. Functional Color, Not Decorative Color

Color must communicate purpose.

Use color primarily for:

```text
Primary action
Status
Priority
SLA
Success
Warning
Danger
Information
```

Do not create rainbow dashboards.

---

## 5. Motion Must Reinforce Quality

Motion should feel:

```text
Fast
Subtle
Controlled
Confident
```

Not:

```text
Playful
Bouncy
Flashy
Attention-seeking
```

---

# Global Theme Direction

Use:

```text
Dark enterprise navigation shell
Light application workspace
White content surfaces
Soft slate background
Indigo primary actions
Strong slate typography
Reserved semantic colors
Subtle border-driven depth
```

The application should visually feel stable.

---

# Core Tailwind Palette

Use the tokens defined in "Design Token Foundation" where available; fall back to Tailwind palette tokens elsewhere.

Primary (functional — buttons, links, focus, active nav):

```text
indigo / --color-primary-*
```

Signature accent (brand distinctiveness — used sparingly, see below):

```text
--color-signature-500
```

Neutral:

```text
slate / --color-ink-*
```

Supporting semantic colors:

```text
blue
violet
emerald
amber
orange
red
```

Avoid:

```text
random hex colors
random page-specific palettes
rainbow accents
```

## Signature Accent Rule

The signature accent exists to stop the product from reading as "default indigo SaaS template." It is used in exactly these places and nowhere else:

```text
The product logomark / wordmark
The command palette trigger and active state
One deliberate empty-state or onboarding illustration accent
Loading/progress indicators (spinner, progress bar fill)
```

It must never appear on a primary button, a status badge, or a data visualization — those remain functional-color-only per "Functional Color, Not Decorative Color." This separation (functional indigo vs. signature accent) is itself the premium signal: it shows color was assigned by role, not by taste.

---

# Application Background

Primary application background:

```text
bg-slate-50
```

Main elevated surface:

```text
bg-white
```

Secondary surface:

```text
bg-slate-50
```

Subtle section background:

```text
bg-slate-100/60
```

Borders:

```text
border-slate-200
```

Stronger separators:

```text
border-slate-300
```

---

# Text Hierarchy

Primary:

```text
text-slate-950
```

Secondary:

```text
text-slate-700
```

Supporting:

```text
text-slate-600
```

Muted:

```text
text-slate-500
```

Disabled:

```text
text-slate-400
```

Do not use washed-out low-contrast body text.

---

# Primary Action

Use:

```text
bg-indigo-600
hover:bg-indigo-700
active:bg-indigo-800
text-white
```

Focus:

```text
focus-visible:ring-2
focus-visible:ring-indigo-500/30
focus-visible:ring-offset-2
```

---

# Global Layout Architecture

All authenticated portals should use the same base shell.

Recommended desktop structure:

```text
┌────────────────────────────────────────────────────────────────┐
│ Sidebar │ Topbar                                               │
│         ├──────────────────────────────────────────────────────┤
│         │ Page Header                                          │
│         │                                                      │
│         │ Primary Content                                      │
│         │                                                      │
│         │ Secondary / Supporting Content                       │
└────────────────────────────────────────────────────────────────┘
```

The shell should feel stable between pages.

Do not redesign navigation per role.

---

# Sidebar

Recommended width:

```text
w-64
```

Optional expanded desktop:

```text
w-72
```

Recommended:

```html
class="flex h-screen w-64 shrink-0 flex-col border-r border-slate-800 bg-slate-950"
```

Sidebar must feel solid and premium.

Use:

```text
Logo / Product Identity
Role-aware navigation
Section grouping
Bottom profile/account block
```

---

# Sidebar Navigation Grouping

Group links.

Example:

```text
Overview
  Dashboard

Work
  Tickets
  Queue
  Notifications

Management
  Team
  Reports

Administration
  Users
  Departments
  Categories
  SLA
  Settings
  Audit
```

Do not present 15 unrelated links with no grouping.

Group label:

```text
px-3
text-[11px]
font-semibold
uppercase
tracking-[0.08em]
text-slate-500
```

---

# Sidebar Item

Default:

```text
flex items-center gap-3
rounded-lg
px-3 py-2.5
text-sm font-medium
text-slate-300
transition-colors duration-150
hover:bg-slate-800/80
hover:text-white
```

Active:

```text
bg-slate-800
text-white
shadow-sm
```

Active icon:

```text
text-indigo-400
```

---

# Topbar

Recommended:

```text
sticky top-0 z-30
h-16
border-b border-slate-200
bg-white/95
backdrop-blur-sm
```

Topbar should include only useful controls.

Recommended:

```text
Breadcrumb / page context
Global search where useful
Notifications
Quick actions
Profile
```

Avoid oversized branding in the topbar.

---

# Main Page Container

Recommended:

```text
px-4 py-6
sm:px-6
lg:px-8
```

Vertical spacing between major page sections:

```text
space-y-6
```

Large analytical pages:

```text
space-y-8
```

---

# Page Header Architecture

Every major page should use a consistent header.

Structure:

```text
Eyebrow / Breadcrumb
Page Title
Description
Primary Action
Secondary Action(s)
```

Example layout:

```html
<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
```

Title:

```text
text-2xl font-bold tracking-tight text-slate-950
sm:text-3xl
```

Description:

```text
mt-1 max-w-2xl text-sm leading-6 text-slate-600
```

Actions:

```text
flex flex-wrap items-center gap-2
```

---

# Page Archetypes

Claude should design pages based on one of these standard patterns.

---

## Dashboard Page

Use:

```text
Page Header
KPI Row
Primary Operational Panel
Secondary Supporting Panels
Recent / Priority Items
```

Recommended:

```text
space-y-6
```

KPI grid:

```text
grid grid-cols-1 gap-4
sm:grid-cols-2
xl:grid-cols-4
```

Do not use 7 giant KPI cards in one row.

---

## List / Management Page

Use:

```text
Page Header
Filter / Search Toolbar
Data Surface
Pagination
```

Example:

```text
Users
Departments
Tickets
Audit Logs
```

The page should feel operational, not decorative.

---

## Detail Page

Use:

```text
Page Header
Primary Work Surface
Context / Metadata Panel
Activity / History
Actions
```

Ticket detail should use a two-column desktop composition.

---

## Settings Page

Use:

```text
Page Header
Left settings navigation or section tabs
Grouped settings cards
Persistent or clearly visible save action
```

Do not create one enormous 50-field form.

---

## Reporting Page

Use:

```text
Page Header
Date Filter Toolbar
KPI Row
Primary Chart
Secondary Charts
Analytical Tables
```

Charts should be subordinate to decisions, not decoration.

---

# Grid Discipline

Use predictable grid patterns.

Four KPI cards:

```text
grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4
```

Two major panels:

```text
grid grid-cols-1 gap-6 xl:grid-cols-2
```

Main + sidebar detail:

```text
grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]
```

Main + wider context:

```text
grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1fr)_400px]
```

---

# Surface Hierarchy & Elevation System

Use five elevation levels, mapped to the shadow tokens in "Design Token Foundation" — not raw Tailwind `shadow-sm`/`shadow-md`, which are single flat shadows and read as default. Each token below is a two-layer shadow (a tight contact shadow plus a soft ambient one), which is what makes elevation feel physical rather than decorative.

## Level 0 — App Background

```text
bg-slate-50
no shadow
```

## Level 1 — Resting Card

```text
rounded-xl border border-slate-200 bg-white shadow-[var(--shadow-xs)]
```

Used for: static content cards, table containers, page sections.

## Level 2 — Primary Interactive Card

```text
rounded-xl border border-slate-200 bg-white shadow-[var(--shadow-sm)]
```

Used for: KPI cards, settings cards, list items that are clickable.

## Level 3 — Elevated Surface

```text
rounded-xl border border-slate-200 bg-white shadow-[var(--shadow-md)]
```

Used for: dropdowns, popovers, hover-elevated cards. Use sparingly.

## Level 4 — Overlay Surface

```text
rounded-2xl bg-white shadow-[var(--shadow-lg)]
```

Used for: modals, command palette.

## Level 5 — Maximum Overlay

```text
rounded-2xl bg-white shadow-[var(--shadow-xl)]
```

Used for: drawers, full-screen takeovers. Reserve for the highest z-index surface only.

Do not make every card float — increasing elevation should always correspond to increasing z-index priority and interactivity, never be applied for decoration alone.

---

# Premium Card System

Base card:

```text
rounded-xl
border border-slate-200
bg-white
shadow-sm
```

Internal padding:

```text
p-5
sm:p-6
```

Header separator where useful:

```text
border-b border-slate-100
```

Do not use oversized 24–32px border radius on normal enterprise cards.

---

# KPI Card Design

KPI cards must be compact.

Recommended:

```text
Label
Value
Supporting metric
Small icon / state
```

Example:

```html
<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
  <div class="flex items-start justify-between gap-4">
    <div>
      <p class="text-sm font-medium text-slate-500">Open Tickets</p>
      <p class="mt-2 text-3xl font-bold tracking-tight text-slate-950">128</p>
      <p class="mt-1 text-xs font-medium text-slate-500">12 require attention today</p>
    </div>
    <div class="rounded-lg bg-indigo-50 p-2.5 text-indigo-600">
      <!-- icon -->
    </div>
  </div>
</div>
```

Avoid decorative trend lines unless useful.

---

# High-Priority KPI Treatment

Critical cards may use:

```text
border-red-200
bg-red-50/30
```

But do not turn the whole page red.

---

# Typography System

## Font Stack

```text
UI text:     Inter Variable (fallback: ui-sans-serif, system-ui)
Technical:   ui-monospace, "JetBrains Mono", "SF Mono", Menlo (IDs, tokens, IPs, code)
```

Use the variable font where possible so weight transitions (hover/active state changes) are smooth rather than jumping between static cuts.

## Type Scale

Every size below pairs a Tailwind size with an explicit tracking value — do not leave tracking at browser default above `text-lg`; unadjusted tracking at large sizes is one of the fastest tells of an unpolished UI.

Page title:

```text
text-2xl sm:text-3xl
font-bold
tracking-tight     /* -0.02em */
text-slate-950
```

Section title:

```text
text-lg
font-semibold
tracking-tight      /* -0.015em */
text-slate-950
```

Card title:

```text
text-sm
font-semibold
text-slate-900
```

Body:

```text
text-sm
leading-6
tracking-normal      /* -0.006em via base body tuning, see below */
text-slate-600
```

Metadata:

```text
text-xs
text-slate-500
```

Table header:

```text
text-xs
font-semibold
uppercase
tracking-wide
text-slate-500
```

## Base Body Tuning

Apply once, globally, rather than per component:

```css
body {
  font-feature-settings: "cv11", "ss01";
  letter-spacing: -0.011em;
  text-rendering: optimizeLegibility;
}
```

## Numeric Text

All KPI values, table numeric columns, currency, SLA countdowns, and reference IDs must use `tabular-nums` (see Design Token Foundation) so values do not visually shift width as digits change — this single detail is disproportionately responsible for tables and dashboards feeling "financial-grade" rather than "template."

---

# Spacing System

Use Tailwind spacing consistently.

Preferred:

```text
gap-2
gap-3
gap-4
gap-5
gap-6
gap-8

p-3
p-4
p-5
p-6
p-8

space-y-3
space-y-4
space-y-6
space-y-8
```

Avoid arbitrary values unless truly necessary.

---

# Buttons

Buttons must feel crisp and professional.

---

## Primary Button

```text
inline-flex
items-center
justify-center
gap-2
rounded-lg
bg-indigo-600
px-4 py-2.5
text-sm
font-semibold
text-white
shadow-sm
transition-colors
duration-150
hover:bg-indigo-700
active:bg-indigo-800
focus-visible:outline-none
focus-visible:ring-2
focus-visible:ring-indigo-500/30
focus-visible:ring-offset-2
disabled:pointer-events-none
disabled:opacity-50
```

---

## Secondary Button

```text
rounded-lg
border border-slate-300
bg-white
px-4 py-2.5
text-sm font-semibold text-slate-700
shadow-sm
transition-colors duration-150
hover:bg-slate-50
```

---

## Ghost Button

```text
rounded-lg
px-3 py-2
text-sm font-medium text-slate-600
hover:bg-slate-100
hover:text-slate-900
```

---

## Danger Button

```text
bg-red-600
text-white
hover:bg-red-700
```

Danger buttons must be rare.

---

# Button Hierarchy Rules

Do not place:

```text
3 primary buttons
```

side-by-side.

Only the most important action should be visually dominant.

---

# Forms

Forms should feel disciplined and premium.

Input:

```text
block w-full
rounded-lg
border border-slate-300
bg-white
px-3 py-2.5
text-sm
text-slate-950
shadow-sm
placeholder:text-slate-400
transition
focus:border-indigo-500
focus:ring-2
focus:ring-indigo-500/20
```

Label:

```text
mb-1.5 block
text-sm
font-semibold
text-slate-700
```

Help text:

```text
mt-1
text-xs
text-slate-500
```

---

# Form Grouping

Related fields should be grouped.

Example:

```text
Personal Information
Account Access
Department
Notifications
Security
```

Use section separators and descriptions.

Do not show 30 unrelated inputs in one uninterrupted form.

---

# Settings Layout

Settings cards should use:

```text
rounded-xl
border border-slate-200
bg-white
shadow-sm
```

Section header:

```text
border-b border-slate-100 px-5 py-4
```

Body:

```text
p-5
```

---

# Tables

Tables are one of the most important surfaces in the product.

They must feel enterprise-grade.

Container:

```text
overflow-hidden
rounded-xl
border border-slate-200
bg-white
shadow-sm
```

Header:

```text
bg-slate-50
```

Header cell:

```text
px-4 py-3
text-left
text-xs
font-semibold
uppercase
tracking-wide
text-slate-500
```

Row:

```text
border-b border-slate-100
transition-colors duration-100
hover:bg-slate-50/80
```

Cell:

```text
px-4 py-4
text-sm
text-slate-600
```

Primary cell:

```text
font-semibold
text-slate-900
```

---

# Table Density

Default row height should visually land around:

```text
52–60px
```

Use tighter tables only where users process high volumes.

---

# Sticky Table Header

Use when useful:

```text
sticky top-0 z-10
bg-slate-50/95
backdrop-blur
```

---

# Data Table Toolbar

Use:

```text
flex flex-col gap-3
border-b border-slate-200
bg-white p-4
lg:flex-row
lg:items-center
lg:justify-between
```

Include:

```text
Search
Filters
Bulk actions only if needed
View options
Export only if relevant
```

---

# Search Input

Recommended:

```text
relative
```

Input:

```text
w-full rounded-lg border border-slate-300
bg-white pl-9 pr-9 py-2.5
text-sm
focus:border-indigo-500
focus:ring-2 focus:ring-indigo-500/20
```

Use debounce where appropriate.

---

# Filter Design

Filters should feel compact.

Avoid:

```text
10 full-width selects stacked on desktop
```

Use:

```text
inline filters
dropdown filter
filter drawer on mobile
```

---

# Mobile Filter Drawer

Use:

```text
fixed inset-0 z-50
```

Backdrop:

```text
bg-slate-950/40
```

Panel:

```text
absolute inset-x-0 bottom-0
max-h-[85vh]
rounded-t-2xl
bg-white
p-5
shadow-2xl
```

---

# Ticket List Design

Ticket rows should visually prioritize:

```text
Ticket number
Subject
Customer
Status
Priority
SLA
Assigned agent
Last update
```

Ticket number:

```text
font-mono text-xs font-semibold text-slate-500
```

Subject:

```text
font-semibold text-slate-900
```

---

# Ticket Detail Layout

Desktop:

```text
grid grid-cols-1 gap-6
xl:grid-cols-[minmax(0,1fr)_360px]
```

Main:

```text
Conversation
Reply composer
Attachments
Timeline
```

Sidebar:

```text
Status
Priority
Assignment
SLA
Department
Category
Customer summary
```

Sidebar should use:

```text
xl:sticky
xl:top-24
xl:self-start
```

---

# Ticket Header

Ticket detail header should display:

```text
Ticket number
Subject
Status
Priority
Customer
Created time
```

Actions should be grouped.

Do not scatter action buttons randomly.

---

# Conversation Design

Conversation should feel like a professional support record, not a social chat app.

Customer message:

```text
rounded-xl
border border-slate-200
bg-white
p-4
```

Agent reply:

```text
rounded-xl
border border-indigo-100
bg-indigo-50/40
p-4
```

Internal note:

```text
rounded-xl
border border-amber-200
bg-amber-50
p-4
```

---

# Message Header

Use:

```text
flex items-center justify-between gap-3
```

Sender:

```text
text-sm font-semibold text-slate-900
```

Timestamp:

```text
text-xs text-slate-500
```

---

# Internal Notes

Internal notes must be extremely obvious.

Label:

```text
inline-flex
rounded-full
bg-amber-100
px-2 py-1
text-[11px]
font-bold
uppercase
tracking-wide
text-amber-800
```

Helper:

```text
text-xs text-amber-700
```

---

# Reply Composer

Reply composer should feel important but compact.

Use:

```text
rounded-xl
border border-slate-200
bg-white
shadow-sm
```

Textarea:

```text
min-h-32
border-0
focus:ring-0
```

Footer:

```text
flex items-center justify-between
border-t border-slate-100
px-4 py-3
```

---

# Status Badges

Use shared components.

Base:

```text
inline-flex items-center
rounded-full
px-2.5 py-1
text-xs font-semibold
ring-1 ring-inset
```

New/Open:

```text
bg-blue-50 text-blue-700 ring-blue-200
```

Assigned:

```text
bg-violet-50 text-violet-700 ring-violet-200
```

In Progress:

```text
bg-amber-50 text-amber-700 ring-amber-200
```

Waiting for Customer:

```text
bg-yellow-50 text-yellow-700 ring-yellow-200
```

Resolved:

```text
bg-emerald-50 text-emerald-700 ring-emerald-200
```

Closed:

```text
bg-slate-100 text-slate-600 ring-slate-200
```

Cancelled:

```text
bg-red-50 text-red-700 ring-red-200
```

---

# Priority Badges

Low:

```text
bg-slate-100 text-slate-600
```

Medium:

```text
bg-yellow-50 text-yellow-700
```

High:

```text
bg-orange-50 text-orange-700
```

Critical:

```text
bg-red-50 text-red-700
```

---

# SLA Design

SLA should be visually prominent without feeling alarmist.

On Track:

```text
bg-emerald-50 text-emerald-700
```

Due Soon:

```text
bg-amber-50 text-amber-700
```

Overdue:

```text
bg-red-50 text-red-700
```

Responded:

```text
bg-blue-50 text-blue-700
```

Resolved:

```text
bg-emerald-50 text-emerald-700
```

No flashing.

No pulsing red animations.

---

# SLA Attention Row

An overdue ticket row may use:

```text
border-l-2 border-red-500
```

A due-soon row:

```text
border-l-2 border-amber-400
```

Keep the rest of the row neutral.

---

# Notifications

Bell:

```text
relative inline-flex
h-10 w-10
items-center justify-center
rounded-lg
text-slate-500
transition-colors
hover:bg-slate-100
hover:text-slate-900
```

Unread count:

```text
absolute -right-0.5 -top-0.5
min-w-4 rounded-full
bg-red-600
px-1
text-[10px]
font-bold
leading-4
text-white
```

Dropdown:

```text
absolute right-0 mt-2
w-80 sm:w-96
overflow-hidden
rounded-xl
border border-slate-200
bg-white
shadow-xl
```

---

# Command Palette (⌘K)

A command palette is one of the highest-leverage signals that a product was built by a team that respects power users' time. It is required, not optional, for this product.

Trigger:

```text
Cmd+K / Ctrl+K from anywhere in the app
Visible trigger in the topbar showing the shortcut hint
```

Trigger button:

```text
inline-flex items-center gap-2
rounded-lg border border-slate-200
bg-slate-50 px-3 py-1.5
text-sm text-slate-500
hover:bg-slate-100
```

Show the shortcut as a `kbd`-styled hint inside the trigger:

```text
rounded border border-slate-300 bg-white
px-1.5 py-0.5
text-[11px] font-medium text-slate-500
```

Palette surface (Level 4 elevation):

```text
fixed inset-x-0 top-24 z-[var(--z-command-palette)]
mx-auto w-full max-w-xl
rounded-2xl border border-slate-200 bg-white
shadow-[var(--shadow-lg)]
```

Contents, top to bottom:

```text
Search input (autofocus, no visible border — input IS the header)
Grouped results: Recent, Navigate, Tickets, People, Actions
Keyboard hints for select (↵) and navigate (↑↓) pinned to the footer
```

Behavior rules:

```text
Opens in under 100ms — no fade/scale animation longer than duration-150
Fuzzy-matches, not just prefix-matches
Supports direct actions ("Create ticket", "Assign to me"), not just navigation
Closes on Escape and on backdrop click
Remembers nothing across sessions unless explicitly a "recent" feature
```

The signature accent (see "Signature Accent Rule") may be used for the active/selected row highlight here — this is one of its sanctioned locations.

---

# Keyboard-First Power User Layer

Enterprise operators (staff, managers, admins) live in this product for hours a day. Mouse-only interaction is a tell of an unfinished product.

Required shortcuts:

```text
Cmd/Ctrl + K     Command palette
/                Focus search on current page
G then D         Go to Dashboard
G then T         Go to Tickets / Queue
J / K            Move selection down / up in a list or table
Enter            Open selected row
Esc              Close modal, drawer, or palette
?                 Open shortcut cheat sheet
```

Shortcut cheat sheet overlay:

```text
Triggered by "?"
Modal-style (Level 4 elevation)
Grouped by category, matching the list above
Dismiss with Esc or click outside
```

Do not implement shortcuts that conflict with native browser or OS shortcuts.

---

# Manager Dashboard Design

Manager dashboard should prioritize:

```text
SLA risk
Team workload
Unassigned tickets
Overdue tickets
Critical tickets
Department performance
```

Suggested structure:

```text
Page Header
KPI Cards
SLA Risk Panel
Team Workload Table
High-Priority Tickets
Department Summary
```

---

# Admin Dashboard Design

Admin dashboard should feel:

```text
Structured
Controlled
Secure
Organized
```

Suggested structure:

```text
Page Header
System KPI Cards
User / Department Summary
System Health / Notifications
Recent Administrative Activity
Quick Access to Configuration
```

Do not turn Admin into another ticket queue page.

---

# Customer Dashboard Design

Customer experience should be calmer and simpler.

Suggested:

```text
Welcome / Page Header
Open / In Progress / Resolved KPIs
Create Ticket CTA
Recent Tickets
Help / Guidance
```

Do not expose unnecessary internal operational data.

---

# Staff Dashboard Design

Staff dashboard should feel efficient.

Suggested:

```text
Page Header
Assigned / Unassigned / Overdue / Critical KPIs
Primary Queue
SLA Risk
Recently Updated
```

---

# Reporting Design

Reporting should feel analytical, not decorative.

Structure:

```text
Page Header
Date Range / Filters
Summary KPIs
Primary Trend Chart
Secondary Breakdown Charts
Detailed Tables
```

Chart surfaces:

```text
rounded-xl
border border-slate-200
bg-white
p-5
shadow-sm
```

---

# Chart Visual System

Use restrained colors.

Primary sequence:

```text
indigo
blue
emerald
amber
slate
red only for negative / critical
```

Avoid rainbow palettes.

No 3D charts.

No heavy gradients.

No animated chart explosions.

---

# Audit Log Design

Audit logs should feel technical and trustworthy.

Use:

```text
Filter toolbar
Dense but readable table
Detail drawer
Old vs New diff
```

Technical fields:

```text
font-mono
text-xs
```

Only for:

```text
IDs
IP addresses
technical keys
```

---

# Detail Drawers

For quick secondary inspection, use drawers.

Recommended:

```text
fixed inset-y-0 right-0
w-full
max-w-xl
bg-white
shadow-2xl
```

Backdrop:

```text
bg-slate-950/40
```

Use for:

```text
Audit detail
Quick user detail
Quick ticket metadata
```

Do not use drawers for full complex workflows.

---

# Modals

Use only for focused actions.

Examples:

```text
Confirm assignment
Confirm close
Confirm disable
Small settings edit
```

Modal:

```text
w-full max-w-lg
rounded-2xl
bg-white
p-6
shadow-2xl
```

---

# SweetAlert2

SweetAlert2 should match the visual system.

Use:

```text
rounded-xl or rounded-2xl
clean typography
indigo confirm action
slate secondary action
red destructive action
```

Use SweetAlert2 for:

```text
Important confirmation
Destructive action
Critical success/error
```

Do not use it for routine background actions.

---

# Motion System

Use the named easing tokens from "Design Token Foundation" instead of default `ease-out`/`ease-in-out` — generic easing is one of the more subtle but consistent tells of an unpolished UI, because default eases feel mechanical rather than physical.

Default (hover, color, background):

```text
transition-colors duration-150 [transition-timing-function:var(--ease-standard)]
```

Panels appearing (dropdowns, popovers):

```text
transition-all duration-200 [transition-timing-function:var(--ease-emphasized)]
```

Drawers / sheets entering:

```text
transition-transform duration-200 [transition-timing-function:var(--ease-decelerate)]
```

Elements leaving (dismiss, collapse):

```text
duration-150 [transition-timing-function:var(--ease-accelerate)]
```

Maximum typical duration:

```text
duration-300
```

Rule of thumb: entrances decelerate (start fast, settle softly), exits accelerate (leave quickly, don't linger). This asymmetry — not just duration — is what makes motion feel "confident" rather than "default."

Avoid:

```text
bounce
overshoot
elastic
continuous pulse
background animation
```

---

# Interaction Feel

Buttons:

```text
Immediate
Crisp
No lag
```

Dropdowns:

```text
Fast opening
Stable position
```

Panels:

```text
No layout jump
```

Tables:

```text
Stable during loading
```

---

# Loading States

Use skeletons for:

```text
Dashboard
Tables
Ticket detail
Reports
Audit
```

Skeleton:

```text
animate-pulse
rounded-md
bg-slate-200
```

Button loading:

```text
Loader icon
animate-spin
button disabled
stable width
```

---

# Empty States

Empty states should look intentional.

Container:

```text
rounded-xl
border border-dashed border-slate-300
bg-white
px-6 py-12
text-center
```

Use:

```text
Small icon
Clear title
Helpful description
Action if relevant
```

Do not use oversized illustrations.

---

# Error States

Use:

```text
rounded-xl
border border-red-200
bg-red-50
p-4
```

Title:

```text
font-semibold text-red-800
```

Body:

```text
text-sm text-red-700
```

Offer:

```text
Retry
Go back
Contact administrator
```

where appropriate.

---

# Mobile Design

Mobile should feel designed, not merely collapsed.

Use:

```text
Responsive cards
Stacked metadata
Drawer navigation
Collapsible filters
Mobile-safe action placement
```

Avoid:

```text
Tiny text
12-column tables
Off-screen controls
Horizontal overflow
```

---

# Mobile Navigation

Sidebar becomes drawer.

Panel:

```text
fixed inset-y-0 left-0 z-50
w-72
bg-slate-950
transition-transform duration-200
```

Backdrop:

```text
fixed inset-0 z-40
bg-slate-950/40
lg:hidden
```

---

# Responsive Breakpoints

Use Tailwind defaults:

```text
sm
md
lg
xl
2xl
```

Do not create random breakpoint values throughout the app.

---

# Responsive QA Sizes

Verify:

```text
375px
430px
768px
1024px
1280px
1440px+
```

---

# Accessibility

Use:

```text
Semantic HTML
Keyboard navigation
Visible focus
ARIA labels
Color contrast
Form labels
Status text
Error associations
```

Never remove focus states without replacement.

---

# Reduced Motion

Respect:

```text
motion-reduce:transition-none
motion-reduce:animate-none
```

where appropriate.

---

# Icon System

Use one icon library.

Preferred:

```text
Lucide React
```

Recommended icon sizes:

```text
h-4 w-4
h-5 w-5
```

Use:

```text
strokeWidth={1.75}
```

or the existing standard.

Do not mix emoji into enterprise navigation.

---

# Avatar System

Use:

```text
Photo if provided
Initials fallback
```

Recommended:

```text
h-8 w-8
rounded-full
bg-slate-200
text-slate-700
```

---

# Tooltip System

Use for:

```text
Icon-only controls
Truncated technical values
SLA explanations
Advanced options
```

Do not hide important information behind tooltips.

---

# Dropdown Menus

Dropdowns should:

```text
Have clear grouping
Use concise actions
Separate destructive actions
Support keyboard navigation
```

Menu:

```text
rounded-xl
border border-slate-200
bg-white
p-1.5
shadow-xl
```

---

# Breadcrumbs

Use on deeper pages.

Example:

```text
Admin / Users / John Smith
```

Use:

```text
text-sm text-slate-500
```

Current page:

```text
font-medium text-slate-700
```

---

# Pagination

Use classic pagination.

Recommended:

```text
Previous
Page numbers
Next
Results count
Rows per page where useful
```

Avoid infinite scrolling for:

```text
Tickets
Audit Logs
Admin Users
Reports
```

---

# Data Refresh

Use subtle refresh behavior.

Do not hard reload the page.

Use:

```text
background re-fetch
query refresh
controlled polling
```

for:

```text
Notifications
Queues
SLA
```

---

# Premium Micro-Details

High-quality details include:

```text
Aligned icon/text baselines
Consistent row heights
Stable button widths
Accurate truncation
Subtle separators
Predictable hover states
High-quality empty states
Good skeletons
Meaningful metadata hierarchy
Consistent card padding
Consistent focus rings
Consistent badge sizes
```

These details matter more than visual effects.

---

# No Generic Admin Template Look

Avoid visual patterns such as:

```text
Huge colorful statistic cards
Gradient-filled headers
Rainbow icon circles
Random shadows
Every section boxed
Oversized rounded corners
Large empty hero sections
Generic stock dashboard layouts
```

The product should feel custom-designed for ticket operations.

---

# Shared Component Requirement

Claude should create/reuse shared components.

Required candidates:

```text
AppShell
Sidebar
MobileSidebar
Topbar
PageHeader
Breadcrumbs
Card
MetricCard
Button
IconButton
Input
Textarea
Select
Checkbox
Toggle
Badge
StatusBadge
PriorityBadge
SLABadge
Avatar
DataTable
TableToolbar
SearchInput
FilterBar
Pagination
Modal
Drawer
Dropdown
Tooltip
Skeleton
EmptyState
ErrorState
Toast
ConfirmDialog
NotificationBell
TicketConversation
TicketReplyComposer
```

Do not duplicate styling per page.

---

# Component Variant Strategy

Button variants:

```text
primary
secondary
ghost
danger
```

Badge variants:

```text
status
priority
sla
neutral
```

Card variants:

```text
default
interactive
warning
danger
```

---

# Tailwind Class Strategy

Prefer built-in classes.

Avoid:

```text
bg-[#4F46E5]
text-[#0F172A]
rounded-[13px]
p-[19px]
```

when equivalents exist.

Use arbitrary values only when justified.

---

# Tailwind Theme Strategy

Centralize repeated values.

If using Tailwind configuration:

```text
Extend theme
```

If using CSS-first Tailwind theme syntax:

```text
Use supported theme variables
```

Do not create a second competing theme file.

---

# Inline Style Policy

Avoid:

```jsx
style={{ ... }}
```

for normal layout and styling.

Use inline styles only for:

```text
Runtime-calculated values
Charts
Third-party components
Dynamic dimensions impossible to represent cleanly in Tailwind
```

---

# Performance Rules

Do not damage performance for aesthetics. A premium enterprise product is judged as much on latency as on pixels — every interaction budget below is a design requirement, not just an engineering one.

Numeric budgets:

```text
Interaction feedback (hover, press, focus):  <16ms  (same frame)
Button click → visible response:              <100ms (optimistic UI where possible)
Route/page transition:                        <200ms perceived
Table/dashboard skeleton → real data:         <1s on typical connection
Animation frame rate:                         60fps sustained, no dropped frames
Cumulative Layout Shift (CLS):                <0.1
Table virtualization threshold:               required above ~200 rows
```

Avoid:

```text
Heavy animation libraries
Huge icon imports
Unnecessary chart libraries
Repeated API calls
Massive component trees
Uncontrolled rerenders
```

Use lazy loading where beneficial. Use optimistic UI updates (reflect the action immediately, reconcile with the server in the background) for low-risk actions like status changes, replies, and assignment — waiting for a round-trip before showing any feedback is one of the most common ways a fast backend still feels like a slow product.

---

# Chart Library Rule

Use one chart library only.

If one already exists:

```text
Reuse it
```

Do not install multiple chart libraries.

---

# Interaction QA Checklist

Before completion verify:

```text
Buttons respond immediately
Disabled states are obvious
Forms validate smoothly
Loading blocks duplicate actions
Dropdowns open/close correctly
Modals open/close correctly
Mobile drawer behaves correctly
Filters reset correctly
Search is debounced
Pagination is stable
Notification menu works
Ticket reply feels responsive
No focus traps
No broken keyboard navigation
```

---

# Visual QA Checklist

Verify:

```text
No clipping
No overflow
No broken responsive layout
No random colors
No random typography
No inconsistent card radii
No inconsistent table spacing
No overuse of shadows
No unnecessary gradients
No unreadable muted text
No mixed icon systems
No missing loading states
No missing empty states
No missing error states
No layout shifts
No ugly native controls where styled alternatives exist
```

---

# Screen-Specific QA

Review:

```text
Login
Customer Dashboard
Customer Ticket List
Customer Ticket Detail
Staff Dashboard
Staff Queue
Staff Ticket Detail
Manager Dashboard
Manager Team
Admin Dashboard
Users
Departments
Categories
Priorities
SLA
Settings
Notifications
Reporting
Audit Logs
```

---

# Claude Implementation Rules

Claude must:

1. Read this file before designing.
2. Inspect the existing frontend first.
3. Preserve working functionality.
4. Use Tailwind CSS as the visual implementation system.
5. Preserve the current React architecture.
6. Reuse shared components.
7. Maintain one visual system across all roles.
8. Use strong information hierarchy.
9. Avoid generic admin-template design.
10. Avoid excessive decoration.
11. Use subtle motion only.
12. Use proper responsive layouts.
13. Maintain accessibility.
14. Keep page density appropriate to the user role.
15. Avoid unnecessary dependencies.
16. Run the production frontend build after major UI changes.
17. Fix UI-related build errors before completion.
18. Verify desktop, tablet, and mobile layouts.
19. Report major shared components created or changed.
20. Do not sacrifice usability for visual novelty.

---

# Do Not

Do not:

```text
Install Bootstrap
Install Material UI
Install Ant Design
Install Chakra UI
Install another CSS framework
Create random page-specific palettes
Create giant gradient headers
Use neon effects
Use glowing cards
Use animated backgrounds
Use particle effects
Use excessive glassmorphism
Use enormous border radii everywhere
Use rainbow KPI cards
Use emoji as primary navigation icons
Use native alert/confirm instead of SweetAlert2
Use multiple icon libraries
Use multiple chart libraries
Use huge CSS files
Use excessive inline styles
Use inconsistent spacing
Use inconsistent button styles
Use inconsistent table styles
Use inconsistent status colors
Use excessive page animation
Use dark mode unless explicitly requested
```

---

# Signature Product Moments

Restraint everywhere else is what makes these land. Do not add more than these — a premium product is defined as much by what it declines to animate as by what it does.

## 1. SLA Countdown Precision

The SLA timer on an at-risk ticket should tick with real precision (updating minute-to-minute, using `tabular-nums` so it never jitters in width), not a static "2h left" label that goes stale. No color flashing — the border-left accent (see "SLA Attention Row") is the only visual escalation.

## 2. Optimistic Reply Send

When an agent sends a reply, it appears in the conversation instantly (optimistic), with a subtle, brief "sending" affordance on the message itself (not a page-level spinner). If it fails, the message stays visible with a clear inline retry — never silently disappears.

## 3. Resolution Confirmation

Resolving a ticket should have exactly one small, quiet moment of acknowledgment — a status badge transition with `--ease-emphasized`, not a modal, not a confetti burst, not a toast that lingers past 3 seconds. The goal is "the system registered this," not celebration.

Nothing outside these three moments should carry bespoke animation. If a fourth "delight moment" is proposed, cut one of the existing three first.

---

# Final Design Standard

The product is not complete merely because it works.

It is complete only when it feels like a single premium enterprise application.

Every major screen should feel:

```text
Intentional
Stable
Polished
Fast
Professional
Trustworthy
Consistent
Operationally clear
```

The UI should look like significant design effort and product thinking were invested in it.

The visual quality must be appropriate for presenting the system to:

```text
Business owners
Department managers
Corporate staff
Operational teams
Enterprise clients
```

The end result should feel like a premium enterprise SaaS platform designed for real daily operations—not a generic admin dashboard.

## Pre-Ship Checklist

Before calling any major screen done, verify all of the following are true — not just the visual QA checklist above, but the specific signals that separate "correctly implemented spec" from "billion-dollar craft":

```text
Shadows use the layered elevation tokens, not raw Tailwind shadow-sm/md/lg
All numeric values (KPIs, tables, SLA, IDs) use tabular-nums
Headings above text-lg have deliberate negative tracking, not browser default
Every transition uses a named easing token, not default ease-out/ease-in-out
Cmd/Ctrl+K opens a working command palette in under 100ms
At least the three defined "Signature Product Moments" are implemented — and nothing more
The signature accent color appears only in its sanctioned locations
The screen would not look out of place next to Linear, Stripe, or Vercel
```

If any box is unchecked, the screen is functionally complete but not premium-complete.