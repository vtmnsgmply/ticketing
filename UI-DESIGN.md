# Enterprise UI / UX Design System — Tailwind CSS

## Purpose

This document defines the visual, interaction, and user-experience standards for the Ticketing System.

Claude must read this file before making frontend design changes.

**Tailwind CSS is the required styling system for this project.**

Use Tailwind for:

```text
Layout
Spacing
Typography
Colors
Borders
Shadows
Responsive Design
Hover States
Focus States
Animations
Transitions
Tables
Forms
Cards
Badges
Modals
Drawers
Navigation
Dashboards
Ticket Screens
Reporting
Audit Logs
Notifications
```

Do not introduce another CSS framework.

---

# Required Frontend Styling Stack

Use:

```text
ReactJS
Tailwind CSS
SweetAlert2
Lucide React (preferred icons, if no icon library already exists)
```

If the project already has an approved React icon library, preserve it.

Do not install:

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

unless explicitly requested.

---

# Tailwind Setup

Inspect the existing frontend first.

If Tailwind CSS is already configured:

```text
Preserve the existing configuration.
Extend it instead of replacing it.
```

If Tailwind CSS is not yet installed, install the version compatible with the existing React/Vite project and configure it using the official Tailwind setup appropriate to the installed version.

Do not assume an outdated Tailwind setup.

---

# Tailwind Rules

Claude must:

1. Use Tailwind utility classes for the primary UI implementation.
2. Reuse shared React components instead of repeating long class strings everywhere.
3. Use Tailwind responsive modifiers.
4. Use Tailwind state modifiers.
5. Use Tailwind transition utilities.
6. Use Tailwind focus utilities.
7. Use Tailwind accessibility-friendly patterns.
8. Centralize recurring design values through the project's Tailwind theme/configuration or CSS theme variables supported by the installed Tailwind version.
9. Avoid random arbitrary colors when a design token exists.
10. Avoid large standalone CSS files.

---

# Do Not Mix Styling Systems

Do not combine Tailwind with another UI framework.

Avoid:

```text
Bootstrap classes + Tailwind
Material UI + Tailwind
Ant Design + Tailwind
Bulma + Tailwind
```

The interface should have one consistent visual system.

---

# Custom CSS

Use custom CSS only when Tailwind utilities cannot reasonably handle the requirement.

Examples where small custom CSS may be acceptable:

```text
Very specific scrollbar behavior
Complex third-party component overrides
Special print styles
Very specific animation unavailable through Tailwind
```

Do not create large page-specific CSS files.

Preferred approach:

```text
Tailwind utilities
Shared React components
Theme tokens
```

---

# Core Design Direction

The application should feel:

```text
Professional
Enterprise-grade
Modern
Clean
Reliable
Trustworthy
Calm
Efficient
Fast
Organized
Premium
```

Avoid designs that feel:

```text
Playful
Game-like
Neon-heavy
Cheap
Template-like
Cluttered
Over-animated
Glassmorphism-heavy
Consumer-social-app-like
```

The design should communicate:

```text
Operational clarity
Trust
Control
Security
Efficiency
Business readiness
```

---

# Design Philosophy

Use a restrained enterprise visual system.

Prioritize:

```text
Clarity over decoration
Information hierarchy over visual noise
Consistency over novelty
Fast scanning over dense decoration
Readable data over excessive graphics
Subtle motion over flashy animation
Useful feedback over unnecessary effects
```

---

# Primary Theme

Use a light enterprise application theme with a dark navigation shell.

Recommended direction:

```text
Soft off-white application background
White content surfaces
Deep slate/navy sidebar
Indigo primary actions
Slate borders
Muted gray secondary text
Semantic status colors
```

---

# Tailwind Color Strategy

Prefer Tailwind's built-in palette where possible.

Use primarily:

```text
slate
indigo
blue
emerald
amber
red
violet
```

Avoid random combinations of unrelated colors.

---

# Primary Brand Color

Use Indigo as the main action color.

Recommended Tailwind classes:

```text
bg-indigo-600
hover:bg-indigo-700
text-indigo-600
border-indigo-600
ring-indigo-500/20
bg-indigo-50
```

Primary button example:

```html
class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5
       text-sm font-semibold text-white shadow-sm transition-colors duration-150
       hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30
       disabled:pointer-events-none disabled:opacity-50"
```

---

# Application Background

Recommended:

```text
bg-slate-50
```

Main surfaces:

```text
bg-white
```

Secondary surfaces:

```text
bg-slate-50
bg-slate-100
```

Borders:

```text
border-slate-200
```

Strong borders:

```text
border-slate-300
```

---

# Text Colors

Recommended:

```text
Primary Text      text-slate-900
Secondary Text    text-slate-600
Muted Text        text-slate-500
Disabled Text     text-slate-400
Inverse Text      text-white
```

Avoid:

```text
text-slate-300 on white
text-gray-300 on white
```

for important content.

---

# Sidebar

Recommended Tailwind foundation:

```html
class="flex h-screen w-64 flex-col border-r border-slate-800 bg-slate-950 text-slate-300"
```

Sidebar item:

```html
class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium
       text-slate-300 transition-colors duration-150
       hover:bg-slate-800 hover:text-white"
```

Active sidebar item:

```html
class="bg-slate-800 text-white"
```

Optional active icon/accent:

```text
text-indigo-400
```

---

# App Shell

Recommended structure:

```text
min-h-screen
bg-slate-50
```

Desktop layout:

```html
<div class="min-h-screen bg-slate-50 lg:flex">
```

Sidebar:

```text
hidden lg:flex
```

Main:

```text
min-w-0 flex-1
```

---

# Top Header

Recommended:

```html
class="sticky top-0 z-30 flex h-16 items-center justify-between
       border-b border-slate-200 bg-white/95 px-4 backdrop-blur
       sm:px-6 lg:px-8"
```

Keep blur subtle.

Do not use heavy glassmorphism.

---

# Page Content

Recommended:

```text
px-4 py-6
sm:px-6
lg:px-8
```

Example:

```html
<main class="px-4 py-6 sm:px-6 lg:px-8">
```

---

# Maximum Content Width

Use as needed:

```text
max-w-7xl
```

for centered pages.

Forms/settings may use:

```text
max-w-3xl
max-w-4xl
```

Do not stretch narrow forms across the entire screen.

---

# Typography

Preferred font:

```text
Inter
```

If Inter is already configured, use:

```text
font-sans
```

Recommended hierarchy:

Page title:

```text
text-2xl font-bold tracking-tight text-slate-900
sm:text-3xl
```

Section title:

```text
text-lg font-semibold text-slate-900
sm:text-xl
```

Card title:

```text
text-sm font-semibold text-slate-900
```

Body:

```text
text-sm leading-6 text-slate-600
```

Metadata:

```text
text-xs text-slate-500
```

---

# Spacing

Use Tailwind's standard spacing scale.

Preferred values:

```text
gap-1
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

space-y-2
space-y-3
space-y-4
space-y-6
```

Avoid excessive arbitrary spacing such as:

```text
p-[19px]
gap-[13px]
```

unless truly necessary.

---

# Border Radius

Recommended:

```text
rounded-md
rounded-lg
rounded-xl
```

Use:

```text
rounded-lg
```

for most controls.

Use:

```text
rounded-xl
```

for cards and larger panels.

Use:

```text
rounded-full
```

for badges, avatars, and pills.

Avoid excessive:

```text
rounded-3xl
```

throughout the application.

---

# Shadows

Use subtle Tailwind shadows.

Cards:

```text
shadow-sm
```

Elevated panels:

```text
shadow-md
```

Modals:

```text
shadow-2xl
```

Do not use heavy shadows on every component.

---

# Enterprise Card

Recommended:

```html
class="rounded-xl border border-slate-200 bg-white shadow-sm"
```

Card padding:

```text
p-4 sm:p-5 lg:p-6
```

---

# KPI Cards

Recommended structure:

```html
<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
  <div class="flex items-start justify-between gap-4">
    <div>
      <p class="text-sm font-medium text-slate-500">Open Tickets</p>
      <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">128</p>
      <p class="mt-1 text-xs text-slate-500">12 due today</p>
    </div>
  </div>
</div>
```

---

# Grid Layouts

Dashboard summary cards:

```text
grid grid-cols-1 gap-4
sm:grid-cols-2
xl:grid-cols-4
```

Two-column operational layouts:

```text
grid grid-cols-1 gap-6
xl:grid-cols-[minmax(0,1fr)_360px]
```

Do not use fixed widths that break smaller screens.

---

# Tables

Enterprise tables should use Tailwind.

Container:

```html
class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
```

Table wrapper:

```text
overflow-x-auto
```

Table:

```text
min-w-full divide-y divide-slate-200
```

Header:

```text
bg-slate-50
```

Header cell:

```text
px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500
```

Row:

```text
border-b border-slate-100 transition-colors hover:bg-slate-50
```

Cell:

```text
px-4 py-4 text-sm text-slate-600
```

Primary cell:

```text
font-medium text-slate-900
```

---

# Table Selected State

Recommended:

```text
bg-indigo-50/60
```

Optional left accent:

```text
border-l-2 border-indigo-500
```

---

# Responsive Tables

Desktop:

```text
table
```

Mobile:

Prefer:

```text
cards
```

for ticket queues where practical.

Do not make users horizontally scroll through 12 columns on a phone unless unavoidable.

---

# Ticket Status Badges

Use reusable React components.

Base:

```text
inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
```

## New / Open

```text
bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200
```

## Assigned

```text
bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200
```

## In Progress

```text
bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200
```

## Waiting for Customer

```text
bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-200
```

## Resolved

```text
bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200
```

## Closed

```text
bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200
```

## Cancelled

```text
bg-red-50 text-red-700 ring-1 ring-inset ring-red-200
```

Always show text.

---

# Priority Badges

Base:

```text
inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
```

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

Critical / Urgent:

```text
bg-red-50 text-red-700
```

---

# SLA Badges

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

Do not flash SLA badges.

---

# Inputs

Recommended Tailwind input:

```html
class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5
       text-sm text-slate-900 shadow-sm outline-none transition
       placeholder:text-slate-400
       focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20
       disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
```

---

# Select

Use the same general style:

```text
rounded-lg
border-slate-300
bg-white
text-sm
focus:border-indigo-500
focus:ring-indigo-500/20
```

---

# Textarea

Recommended:

```text
min-h-28
resize-y
```

with the same input styling.

---

# Labels

Recommended:

```text
mb-1.5 block text-sm font-semibold text-slate-700
```

Never rely only on placeholders.

---

# Validation Errors

Input:

```text
border-red-300 focus:border-red-500 focus:ring-red-500/20
```

Error text:

```text
mt-1 text-xs font-medium text-red-600
```

---

# Primary Button

Recommended reusable Tailwind classes:

```text
inline-flex items-center justify-center gap-2
rounded-lg
bg-indigo-600
px-4 py-2.5
text-sm font-semibold text-white
shadow-sm
transition-colors duration-150
hover:bg-indigo-700
focus:outline-none
focus:ring-2
focus:ring-indigo-500/30
disabled:pointer-events-none
disabled:opacity-50
```

---

# Secondary Button

```text
inline-flex items-center justify-center gap-2
rounded-lg
border border-slate-300
bg-white
px-4 py-2.5
text-sm font-semibold text-slate-700
shadow-sm
transition-colors duration-150
hover:bg-slate-50
focus:outline-none
focus:ring-2
focus:ring-slate-300
```

---

# Danger Button

```text
bg-red-600
text-white
hover:bg-red-700
focus:ring-red-500/30
```

Only for destructive actions.

---

# Ghost Button

Recommended:

```text
rounded-lg
px-3 py-2
text-sm font-medium text-slate-600
transition-colors
hover:bg-slate-100
hover:text-slate-900
```

---

# Icon Buttons

Recommended:

```text
inline-flex h-10 w-10 items-center justify-center
rounded-lg
text-slate-500
transition-colors
hover:bg-slate-100
hover:text-slate-900
focus:outline-none
focus:ring-2
focus:ring-indigo-500/20
```

Use:

```text
aria-label
title where useful
```

---

# Ticket Detail Layout

Desktop:

```text
grid grid-cols-1 gap-6
xl:grid-cols-[minmax(0,1fr)_360px]
```

Main conversation:

```text
min-w-0
```

Metadata panel:

```text
space-y-4
xl:sticky
xl:top-24
xl:self-start
```

---

# Ticket Conversation

Conversation wrapper:

```text
space-y-4
```

Customer message:

```text
rounded-xl border border-slate-200 bg-white p-4
```

Agent reply:

```text
rounded-xl border border-indigo-100 bg-indigo-50/40 p-4
```

Internal note:

```text
rounded-xl border border-amber-200 bg-amber-50 p-4
```

---

# Internal Note Label

Recommended:

```text
text-xs font-bold uppercase tracking-wide text-amber-700
```

Helper:

```text
text-xs text-amber-700/80
```

---

# Attachment Chip

Recommended:

```text
inline-flex items-center gap-2 rounded-lg border border-slate-200
bg-slate-50 px-3 py-2 text-xs font-medium text-slate-700
hover:bg-slate-100
```

---

# Filter Bar

Recommended:

```text
flex flex-col gap-3
rounded-xl border border-slate-200 bg-white p-4 shadow-sm
lg:flex-row lg:items-center
```

Search:

```text
min-w-0 flex-1
```

Filters:

```text
grid grid-cols-1 gap-2
sm:grid-cols-2
lg:flex
```

---

# Mobile Filter Drawer

On mobile, secondary filters may open inside a drawer/sheet.

Use Tailwind:

```text
fixed inset-0 z-50
```

Backdrop:

```text
bg-slate-950/40
```

Panel:

```text
absolute inset-x-0 bottom-0 rounded-t-2xl bg-white p-5 shadow-2xl
```

or side drawer.

---

# Modals

Backdrop:

```text
fixed inset-0 z-50 bg-slate-950/40
```

Container:

```text
flex min-h-full items-center justify-center p-4
```

Modal:

```text
w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl
```

---

# Motion

Use Tailwind transitions.

Typical:

```text
transition-colors duration-150
transition-all duration-200 ease-out
```

Approved durations:

```text
duration-150
duration-200
duration-300 only for drawers/large panels
```

Do not use long animations.

---

# Hover Effects

Approved:

```text
hover:bg-slate-50
hover:bg-slate-100
hover:bg-indigo-700
hover:border-slate-300
```

Avoid:

```text
hover:scale-110
hover:rotate-*
huge transforms
```

Small transforms may be used rarely:

```text
active:translate-y-px
```

---

# Focus States

Do not remove focus styling.

Use:

```text
focus:outline-none
focus:ring-2
focus:ring-indigo-500/20
```

or:

```text
focus-visible:outline-none
focus-visible:ring-2
focus-visible:ring-indigo-500/30
```

---

# Skeleton Loading

Use Tailwind:

```text
animate-pulse
```

Example:

```html
<div class="animate-pulse rounded-lg bg-slate-200"></div>
```

Keep skeleton layout close to real content to prevent layout shifts.

---

# Button Loading

Example:

```html
<button disabled class="...">
  <Loader2 className="h-4 w-4 animate-spin" />
  Saving...
</button>
```

Keep button width stable where practical.

---

# Empty State

Recommended:

```text
flex flex-col items-center justify-center
rounded-xl border border-dashed border-slate-300
bg-white px-6 py-12 text-center
```

Icon:

```text
text-slate-400
```

Heading:

```text
mt-4 text-sm font-semibold text-slate-900
```

Body:

```text
mt-1 max-w-sm text-sm text-slate-500
```

---

# Error State

Recommended:

```text
rounded-xl border border-red-200 bg-red-50 p-4
```

Title:

```text
font-semibold text-red-800
```

Body:

```text
text-sm text-red-700
```

---

# Notification Bell

Bell button:

```text
relative inline-flex h-10 w-10 items-center justify-center rounded-lg
text-slate-500 hover:bg-slate-100 hover:text-slate-900
```

Unread badge:

```text
absolute -right-0.5 -top-0.5 min-w-4 rounded-full
bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white
```

---

# Notification Dropdown

Recommended:

```text
absolute right-0 mt-2 w-80 overflow-hidden rounded-xl
border border-slate-200 bg-white shadow-xl
sm:w-96
```

Unread item:

```text
bg-indigo-50/40
```

Read item:

```text
bg-white
```

---

# Reports

Reporting page:

```text
space-y-6
```

Summary grid:

```text
grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4
```

Charts:

```text
rounded-xl border border-slate-200 bg-white p-5 shadow-sm
```

Avoid rainbow chart colors.

Use primarily:

```text
indigo
blue
emerald
amber
slate
red for negative states only
```

---

# Audit Logs

Audit table:

```text
rounded-xl border border-slate-200 bg-white shadow-sm
```

Technical values:

```text
font-mono text-xs text-slate-600
```

Use monospace only for:

```text
IDs
IP addresses
technical keys
```

---

# Settings

Settings layout:

```text
space-y-6
```

Section:

```text
rounded-xl border border-slate-200 bg-white p-5 shadow-sm
sm:p-6
```

Section title:

```text
text-base font-semibold text-slate-900
```

Description:

```text
mt-1 text-sm text-slate-500
```

---

# Login Screen

Recommended Tailwind layout:

```text
min-h-screen
bg-slate-50
lg:grid
lg:grid-cols-2
```

Brand panel:

```text
hidden lg:flex
bg-slate-950
text-white
```

Form panel:

```text
flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8
```

Login card/form:

```text
w-full max-w-md
```

Avoid flashy gradients and large animated backgrounds.

---

# Mobile Sidebar

Desktop sidebar:

```text
hidden lg:flex
```

Mobile drawer:

```text
fixed inset-y-0 left-0 z-50 w-72 bg-slate-950
transition-transform duration-200 ease-out
```

Closed:

```text
-translate-x-full
```

Open:

```text
translate-x-0
```

Backdrop:

```text
fixed inset-0 z-40 bg-slate-950/40 lg:hidden
```

---

# Responsive Breakpoints

Use Tailwind standard breakpoints:

```text
sm
md
lg
xl
2xl
```

Do not create random breakpoint logic throughout the project.

---

# Mobile Design Requirements

At small widths:

```text
Cards stack vertically
Tables become cards where practical
Filter controls collapse
Ticket metadata stacks
Sidebar becomes a drawer
Buttons remain tap-friendly
Conversation composer remains usable
```

Use:

```text
min-h-10
min-w-10
```

or equivalent for important tap targets.

---

# Accessibility

Use Tailwind without sacrificing accessibility.

Requirements:

```text
focus-visible rings
visible labels
adequate color contrast
keyboard navigation
aria-label on icon buttons
semantic HTML
text labels with status colors
```

---

# Reduced Motion

Respect:

```text
motion-reduce:transition-none
motion-reduce:animate-none
```

where appropriate.

---

# Dark Mode

Do not implement application-wide dark mode unless explicitly requested.

Do not add:

```text
dark:
```

variants throughout the application unless dark mode is actually part of the approved scope.

The required primary theme is:

```text
Light workspace
Dark sidebar
```

---

# Shared UI Components

Prefer reusable Tailwind-styled React components:

```text
AppShell
Sidebar
Topbar
PageHeader
Card
MetricCard
Button
IconButton
Input
Select
Textarea
Checkbox
Toggle
Badge
StatusBadge
PriorityBadge
SLABadge
Avatar
DataTable
Pagination
SearchInput
FilterBar
Modal
Drawer
Dropdown
Tooltip
Skeleton
EmptyState
ErrorState
Toast
NotificationBell
ConfirmDialog
```

---

# Component Variants

Do not repeat classes manually everywhere.

For example, Button should support variants such as:

```text
primary
secondary
danger
ghost
```

Badge:

```text
status
priority
SLA
```

Use a simple helper such as:

```text
clsx
classnames
existing utility
```

only if already available or justified.

Do not add unnecessary class-management libraries.

---

# Tailwind Configuration / Theme

Centralize repeated custom values.

Preferred design tokens include:

```text
Primary color
Application background
Card radius
Sidebar width
Header height
```

If the installed Tailwind version uses configuration files, extend the Tailwind theme.

If the installed version uses CSS-first theme configuration, use the appropriate supported theme syntax.

Do not rely on an outdated Tailwind setup.

---

# Avoid Excessive Arbitrary Values

Avoid:

```text
bg-[#4F46E5]
text-[#0F172A]
rounded-[13px]
p-[17px]
```

when Tailwind already provides:

```text
bg-indigo-600
text-slate-900
rounded-xl
p-4
```

Arbitrary values are acceptable only when required for a specific design constraint.

---

# No Inline Style Abuse

Avoid:

```jsx
style={{ backgroundColor: "...", padding: "...", margin: "..." }}
```

for normal UI styling.

Prefer Tailwind classes.

Inline styles may be used for:

```text
dynamic chart values
runtime-calculated dimensions
third-party components
```

where Tailwind classes cannot represent the value.

---

# Effects

Approved Tailwind effects:

```text
shadow-sm
shadow-md
hover:bg-slate-50
hover:border-slate-300
transition-colors
focus:ring-2
animate-pulse
animate-spin
backdrop-blur-sm where subtle
```

Avoid:

```text
neon glow
animated gradients
particles
parallax
continuous pulse on critical items
large scale transforms
3D effects
```

---

# Enterprise Smoothness

Use:

```text
transition-colors duration-150
transition-all duration-200 ease-out
```

for ordinary interaction.

Drawers:

```text
transition-transform duration-200 ease-out
```

Keep interactions stable and fast.

---

# Role-Specific UI

## Customer

Use lower visual density.

Prioritize:

```text
My Tickets
Create Ticket
Conversation
Status
Notifications
Profile
```

---

## Staff / Agent

Use moderate information density.

Prioritize:

```text
Queues
Assignment
Priority
Status
SLA
Conversation
Internal Notes
```

---

## Manager

Use operational overview.

Prioritize:

```text
Team Workload
Overdue
High Priority
SLA
Assignments
Department Summary
```

---

## Administrator

Use structured management layouts.

Prioritize:

```text
Users
Departments
Categories
Priorities
SLA
Settings
Reports
Audit
```

---

# Visual QA

Before completing UI work, verify:

```text
No overflow
No clipped content
No broken tables
No inconsistent spacing
No unreadable badges
No overlapping controls
No random font sizes
No mixed icon styles
No random colors
No excessive shadows
No unnecessary gradients
No missing hover states
No missing focus states
No abrupt layout shifts
```

---

# Responsive QA

Test major pages at approximately:

```text
375px
430px
768px
1024px
1280px
1440px+
```

Verify:

```text
Login
Customer Dashboard
Customer Ticket List
Customer Ticket Detail
Staff Dashboard
Staff Ticket Queue
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

# Interaction QA

Verify:

```text
Buttons provide feedback
Disabled states work
Forms validate cleanly
Loading prevents duplicate submission
Modals open/close smoothly
Drawers work on mobile
Filters reset correctly
Search is debounced where appropriate
Pagination works
Notification dropdown works
Ticket conversation stays readable
SweetAlert2 is styled consistently
```

---

# Claude Implementation Rules

Claude must:

1. Use Tailwind CSS as the primary styling system.
2. Inspect the current Tailwind setup before making changes.
3. Preserve working frontend functionality.
4. Preserve the current React architecture.
5. Reuse shared components.
6. Avoid page-specific duplicate styling.
7. Use Tailwind responsive modifiers.
8. Use Tailwind hover/focus/disabled modifiers.
9. Keep animation subtle.
10. Maintain accessibility.
11. Avoid unnecessary dependencies.
12. Avoid another CSS framework.
13. Run the frontend production build after significant design changes.
14. Fix UI-related build errors before completion.
15. Verify desktop, tablet, and mobile layouts.
16. Report shared components and theme changes created.

---

# Do Not

Do not:

```text
Install Bootstrap
Install Material UI
Install Ant Design
Install Chakra UI
Install another CSS utility framework
Use random inline CSS everywhere
Create large page-specific CSS files
Use random arbitrary hex colors across pages
Use excessive gradients
Use neon effects
Use animated backgrounds
Use emoji as primary icons
Use inconsistent button styling
Use inconsistent badge styling
Use inconsistent table styling
Use excessive rounded corners
Use huge decorative cards
Use excessive glassmorphism
Use dark mode unless requested
Use blocking SweetAlert2 for every tiny action
Use native alert/confirm when SweetAlert2 is already standard
Install multiple icon libraries
Install multiple chart libraries
```

---

# Completion Standard

The Tailwind implementation is complete only when the full application feels like one unified enterprise SaaS platform.

The final interface should communicate:

```text
Professional support operations
Reliable ticket handling
Clear accountability
Fast workflows
Strong management visibility
Secure administration
Enterprise readiness
```

The design must be appropriate for a paying business client and suitable for daily operational use.
