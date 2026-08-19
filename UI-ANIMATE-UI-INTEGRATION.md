# UI-ANIMATE-UI-INTEGRATION.md

## Purpose

This document defines how Codex should selectively integrate **Animate UI** into the existing enterprise Ticketing System.

Codex must read this file together with:

```text
1. AGENTS.md
UI-DESIGN.md
UI-REFINEMENT-PREMIUM-LAYOUT.md
UI-LIVE-ENTERPRISE-UPGRADE.md
```

This file does **not** authorize a full UI rewrite. The existing system is already working.

The goal is to use only the Animate UI pieces that actually fit a serious B2B ticketing platform so the interface feels:

```text
More alive
More responsive
More premium
More polished
More intentional
Enterprise-grade
```

without becoming:

```text
Flashy
Consumer-like
Playful
Distracting
Slow
Over-animated
```

---

# Official Animate UI Direction

Animate UI is a React component distribution built with Tailwind CSS and Motion, using the shadcn registry approach. Use the **current** Animate UI documentation and registry identifiers before installing any component.

Do not use outdated beta APIs or third-party tutorials as the implementation source.

---

# Preserve the Existing Project

Do not migrate the application just to use Animate UI.

Preserve:

```text
React architecture
Tailwind CSS
SweetAlert2
Authentication
Authorization
Ticket workflows
Existing backend APIs
Existing performance optimizations
Responsive behavior
```

If the frontend currently uses JavaScript/JSX, do **not** convert the whole application to TypeScript just because Animate UI examples may use TypeScript. Adapt installed components to the current project convention where necessary.

---

# Installation Strategy

Before running any install command:

1. Inspect `package.json`.
2. Inspect the Tailwind setup.
3. Inspect whether shadcn is already initialized.
4. Inspect whether Motion is already installed.
5. Inspect existing Radix/Base UI primitives.
6. Inspect existing reusable components.
7. Reuse existing equivalents where possible.
8. Install only the Animate UI components actually used.
9. Run the production build after dependency changes.

Do **not** bulk-install the Animate UI catalog.

Animate UI uses shadcn-style registry installation. Current official installation uses commands such as:

```bash
npx shadcn@latest add @animate-ui/...
```

Codex must verify the exact current registry identifier before executing a command.

---

# Approved Animate UI Components

## 1. Sliding Number — HIGH PRIORITY

Use Animate UI **Sliding Number** for important KPI values.

Good uses:

```text
Open tickets
Overdue tickets
Critical tickets
Resolved today
Active users
SLA percentage
Unread notification count
Manager workload totals
Reporting summary values
```

Do not animate every small number on the page.

Behavior:

```text
previous value → new value
```

should animate smoothly and only when the value changes.

Do not replay the animation on every React render.

Current official registry pattern includes:

```bash
npx shadcn@latest add @animate-ui/primitives-texts-sliding-number
```

Verify before installation.

---

## 2. Progress — HIGH PRIORITY

Use Animate UI **Progress** for:

```text
Ticket-themed initial page loading
SLA progress / time-consumed visualization
Real file-upload progress
Export progress when real progress exists
Long-running operations
```

Do not use fake timer-based progress.

For the ticket loader, keep the architecture:

```text
LoadingProgressProvider
        ↓
real weighted loading tasks
        ↓
progress percentage
        ↓
TicketLoadingProgress
        ↓
Animate UI Progress + animated percentage
```

The ticket-shaped fill can remain custom SVG/Tailwind while Animate UI handles smooth progress interpolation and animated values.

Choose **one** Progress family only, for example Radix or Base UI. Do not install multiple implementations for the same purpose.

Current official registry patterns include forms such as:

```bash
npx shadcn@latest add @animate-ui/primitives-radix-progress
```

Verify the current docs before installation.

---

## 3. Sheet — HIGH VALUE

Use Animate UI **Sheet** for side panels and mobile workflows.

Recommended uses:

```text
Mobile sidebar
Mobile filters
Create User
Edit User
Ticket filter panel
Quick ticket metadata edit
Audit detail
Notification detail
Manager assignment panel
```

Preferred behavior:

```text
Desktop → right-side sheet where appropriate
Mobile → side or bottom sheet depending on context
```

Do not use Sheet for a full complex multi-page workflow.

Strong page-specific uses:

```text
Users: Create/Edit User → right Sheet
Tickets: mobile filters → Sheet
Reports: mobile advanced filters → Sheet
Audit: audit-record detail → right Sheet
```

---

## 4. Tooltip — HIGH VALUE

Use Animate UI **Tooltip** for:

```text
Icon-only buttons
Collapsed sidebar items
SLA explanations
Technical values
Truncated labels
Quick actions
Audit actions
```

Tooltips must remain short and accessible.

Do not hide essential operational information inside tooltips.

---

## 5. Tabs — HIGH VALUE

Use animated Tabs where they genuinely improve information organization.

Recommended:

### Ticket Detail

```text
Conversation
Activity
Attachments
```

Conversation remains the default.

### Admin Settings

```text
General
Email
Notifications
Security
```

### Reports

```text
Overview
SLA
Agents
Departments
Categories
```

### Manager

```text
Queue
Team
SLA
```

### Profile

```text
Profile
Security
```

Do not convert every page section into tabs.

Use restrained indicator/content animation.

---

## 6. Dialog — APPROVED

Use Animate UI Dialog for focused interactive content such as:

```text
Short edit forms
Quick detail views
Attachment metadata
Small category/department edits
Contextual information
```

Continue using **SweetAlert2** for:

```text
Destructive confirmations
Critical warnings
Important success/error confirmations
```

Rule:

```text
Animate UI Dialog = content / form interaction
SweetAlert2 = confirmation / warning / critical feedback
```

Do not replace SweetAlert2 globally.

---

## 7. Switch — APPROVED

Use Animate UI Switch for genuine boolean settings:

```text
Enable Web Notifications
Enable Email Notifications
Allow Customer Reopen
Department Active
Category Active
Priority Active
```

Do not use instant switches for destructive account actions if confirmation is safer.

Example:

```text
Disable User
```

should generally retain confirmation.

---

## 8. Accordion / Collapsible — CONDITIONAL

Use for:

```text
Advanced report filters
Secondary settings
Help information
Rare configuration
Mobile secondary metadata
```

Do not hide important desktop ticket information behind collapsed panels.

---

## 9. Animated Icons — APPROVED

Animate UI includes animated Lucide-style icons.

Use sparingly for:

```text
Refresh
Notification bell
Search/filter actions
Chevron expansion
Upload/download
Success completion
Quick Actions
```

Animation should normally occur on:

```text
hover
focus
state change
active operation
```

Examples:

```text
Refresh rotates only while refreshing
Chevron rotates when expanding
Bell subtly responds to a new notification
Check animates once after success
```

Do not continuously animate navigation icons.

---

## 10. Avatar Group — CONDITIONAL

Use for:

```text
Manager team summary
Department member preview
Assigned support team preview
```

Only when real user data makes it useful.

Recommended maximum visible avatars:

```text
4 + "+N"
```

Do not use decoratively.

---

# Use Community Components With Caution

Animate UI community components may have APIs and visual behavior that vary.

Do not automatically adopt community components.

For example, an animated stacked Notification List may feel too playful for this product. Evaluate it first. If it weakens the enterprise tone, keep the current professional notification list and add only subtle Animate UI motion.

---

# Components / Effects to Avoid

Do **not** use the following for normal enterprise ticketing screens:

```text
Stars Background
Bubble Background
Particles Background
Motion Carousel
Flip Card
Morphing Text
large animated gradients
heavy Zoom effects
heavy Blur effects
marketing-style animated backgrounds
```

Do not hide operational information inside carousels or flip cards.

---

# Approved Effects

Animate UI exposes effects such as Fade, Slide, Zoom, and Blur.

For this system:

Approved:

```text
Fade
Very small Slide
```

Use sparingly.

Avoid dramatic:

```text
Zoom
Blur
scale
bounce
```

for normal dashboard content.

---

# Motion Hierarchy

## Level 1 — Micro Interaction

```text
100–180ms
```

Use for:

```text
button hover
icon feedback
tooltip
small badge state
```

## Level 2 — Component Transition

```text
160–260ms
```

Use for:

```text
Sheet
Dialog
Dropdown
Tabs
Accordion
```

## Level 3 — Major Surface

```text
200–320ms maximum
```

Use for:

```text
initial dashboard content reveal
major route content
```

Do not create long cascading transitions.

---

# Dashboard Entrance Animation

The dashboard may animate **once** after initial data loading.

Recommended:

```text
KPI cards → subtle fade + 4–8px rise
Main table → soft fade
Quick Actions → short controlled stagger
Live Activity → soft fade
```

If stagger is used:

```text
30–60ms between cards
maximum total stagger ~250ms
```

Do not replay the full entrance animation when:

```text
notifications poll
a KPI updates
a ticket status changes
```

---

# KPI Number Animation

Use Sliding Number.

Example:

```text
148 → 151
```

Only the changed number should animate.

Do not remount every KPI card.

---

# SLA Progress

Use Progress only where the underlying SLA values are meaningful.

Suggested semantic progression:

```text
0–70% consumed    emerald / blue
70–90%            amber
90–100%           orange
breached          red
```

Do not create misleading SLA percentages.

Use the existing backend SLA rules as the source of truth.

---

# Ticket Loading Progress Integration

Upgrade the existing ticket-themed loading experience using Animate UI.

Required:

```text
Ticket-shaped visual
Real task-based percentage
Animated percentage value
Smooth progress fill
Current loading task
100% ready state
Error state
Retry
```

Recommended use:

```text
Sliding Number → loading percentage
Progress → smooth fill/interpolation
Animated Check icon → completion once
```

Suggested color progression:

```text
0–24%   slate
25–49%  blue
50–74%  indigo
75–99%  emerald
100%    emerald success
```

Red is reserved for an actual loading failure.

Do not artificially delay the page so the animation can finish.

---

# Route Transition Behavior

For normal authenticated navigation:

Keep visible:

```text
Sidebar
Topbar
Application shell
```

Animate only the new content area with:

```text
small Fade
or
very slight Slide
```

Do not blank the whole application between routes.

---

# Sidebar Decision Rule

Animate UI has an animated composable Sidebar.

Do not replace the existing sidebar automatically.

First verify whether replacement improves:

```text
Collapse/expand
Mobile behavior
Nested groups
Role-aware menu display
Active route state
Accessibility
```

If the existing sidebar already works well, retain it and add subtle transitions only.

If replaced, preserve:

```text
Customer/Staff/Manager/Admin role visibility
Current routes
Profile footer
Notification behavior
Authorization boundaries
Responsive behavior
```

Collapsed mode may use:

```text
icons only + Tooltip
```

Do not auto-collapse while the user is working.

---

# Notification Animation

When a real new notification arrives, allow:

```text
Bell icon subtle one-time animation
Unread badge transition
New list item soft fade/slide
```

Do not:

```text
shake the entire header
open the dropdown automatically
loop animations
```

---

# Live Activity Animation

When a new activity item is inserted:

```text
soft fade
small slide from top
```

Limit feed size, for example:

```text
latest 5–10 items
```

Do not animate an unbounded activity feed.

---

# Table Animation Rules

Tables must remain fast and stable.

Approved:

```text
row hover
selected state transition
new-row subtle background fade
status badge transition
```

Avoid:

```text
staggering dozens of rows
layout animation on every filter change
large row transforms
```

Enterprise data tables prioritize speed and scanability.

---

# Page-by-Page Fit

## Login

Use only:

```text
subtle form entrance
animated password visibility icon if available
button state animation
```

No animated background.

## Customer Dashboard

Use:

```text
Sliding Number KPIs
small one-time card entrance
Tooltip
notification motion
Sheet on mobile where useful
```

Keep motion minimal.

## Staff Dashboard

Use:

```text
Sliding Number KPIs
SLA Progress
Quick Action icon feedback
Live Activity insert animation
Tooltip
mobile filter Sheet
```

## Manager Dashboard

Use:

```text
Sliding Number
SLA Progress
Tabs
Avatar Group where useful
Live Activity
Tooltip
advanced-filter Sheet
```

## Admin Dashboard

Use:

```text
Sliding Number
subtle animated action icons
Live Activity
Tooltip
Sheets for management actions
Tabs only where content benefits
```

Do not over-animate the command center.

## Users

Preferred:

```text
Create User → Sheet
Edit User → Sheet
Sliding Number summary cards
Switch where safe
Tooltip
```

## Ticket List / Ticket Detail

Use:

```text
mobile filter Sheet
SLA Progress
Tooltip
Tabs on detail page if useful
status transition polish
ticket loading animation
```

## Notifications

Use:

```text
Bell one-time animation
Badge transition
New item fade/slide
Tooltip
```

## Reports

Use:

```text
Sliding Number
Progress for real percentages
Tabs
mobile filter Sheet
subtle panel entrance
```

Do not add extra Animate UI chart animation if the existing chart library already animates well.

## Audit

Use:

```text
Audit Detail → right Sheet
Tooltips
subtle detail transitions
```

Keep the table stable.

## Settings

Use:

```text
Tabs
Switch
Tooltip
Accordion for advanced settings
Dialog where useful
```

---

# React StrictMode Requirement

Keep:

```text
<React.StrictMode>
```

The application previously fixed duplicate request behavior using shared/single-flight data loading. Do not undo that work.

Animate UI documentation notes that certain animation/highlight behavior can have StrictMode-specific issues.

If a selected Animate UI component has a StrictMode visual issue:

```text
1. Verify the current Animate UI documentation.
2. Prefer a component-specific fix.
3. Replace or avoid that specific effect if needed.
4. Do NOT globally disable StrictMode without explicit approval.
```

---

# Performance Requirements

Animate UI must not reintroduce performance problems.

Do not create:

```text
duplicate API requests
duplicate mount fetches
high-frequency timers
render loops
unbounded animation lists
huge motion trees
```

Preserve:

```text
shared Auth state
shared Notification state
SLA request deduplication
single-flight requests
SANCTUM_LAST_USED_AT=false behavior
optimized Laravel API flow
```

---

# Animation Dependency Rule

Animate UI uses Motion-based animation.

Allow the current Animate UI/shadcn installation process to add its required Motion dependency.

Do not install competing animation systems such as:

```text
GSAP
anime.js
React Spring
another general motion library
```

for this UI upgrade.

Use one primary motion system.

---

# Bundle Size Discipline

After installation:

```bash
npm run build
```

Review the production build output.

Do not import unused Animate UI component groups.

Do not install components that are not used.

Prefer direct imports.

---

# Enterprise Component Selection Summary

## Strongly Recommended

```text
Sliding Number
Progress
Sheet
Tooltip
Tabs
Animated Icons
```

## Recommended Where Needed

```text
Sidebar
Dialog
Switch
Accordion / Collapsible
Avatar Group
```

## Use With Caution

```text
Community Notification List
```

## Avoid

```text
Stars Background
Bubble Background
Particles Background
Motion Carousel
Flip Card
Morphing Text
heavy Zoom
heavy Blur
marketing-style animated backgrounds
```

---

# Shared Integration Layer

Follow the structure created by the Animate UI/shadcn registry where practical.

Possible app-level wrappers:

```text
AnimatedMetric
TicketLoadingProgress
AppSheet
AppTooltip
AppTabs
AnimatedActionIcon
```

These wrappers should enforce the existing Ticketing System visual language.

Animate UI is the **motion/interaction layer**.

The Ticketing System design files remain the **visual authority**.

---

# Visual Styling Rule

Do not use default installed styling blindly.

Adapt components to the existing enterprise palette:

```text
Primary brand color
Slate neutrals
Semantic emerald/amber/red/blue/violet
rounded-xl / rounded-2xl
subtle enterprise shadows
current typography
current spacing
```

Do not make Animate UI look like a separate product inside the system.

---

# Reduced Motion

Respect:

```text
prefers-reduced-motion
```

For reduced-motion users:

```text
Sliding numbers may update with minimal/no animation
Sheets/Dialog movement should be reduced
Card entrances may become simple fades/no animation
Animated icons must not loop
```

Accessibility takes priority over visual polish.

---

# Codex Implementation Order

Execute in this order:

```text
1. Read all architecture/UI instruction files.
2. Inspect package.json.
3. Inspect Tailwind setup.
4. Inspect existing UI/component dependencies.
5. Inspect shadcn configuration.
6. Verify current Animate UI official docs and registry identifiers.
7. Choose only components that solve an actual UX need.
8. Install Sliding Number.
9. Install one Progress implementation.
10. Upgrade TicketLoadingProgress.
11. Create/refine AnimatedMetric wrapper.
12. Add Tooltip to ambiguous icon-only controls.
13. Add Sheet to Users/mobile filters/Audit detail where useful.
14. Add Tabs to Settings/Reports/Ticket Detail where useful.
15. Add animated icon feedback selectively.
16. Evaluate existing Sidebar before considering replacement.
17. Add Switch/Accordion only when needed.
18. Verify StrictMode behavior.
19. Verify reduced-motion behavior.
20. Verify no duplicate network calls were introduced.
21. Run frontend tests if configured.
22. Run npm run build.
23. Fix implementation-related errors/warnings.
24. Report exact dependencies/components installed.
```

---

# Do Not

Do not:

```text
Install the full Animate UI catalog
Rewrite working pages unnecessarily
Rewrite business logic
Rewrite backend logic
Disable authentication
Disable authorization
Disable React StrictMode globally
Add fake loading delay
Add fake progress
Animate every table row
Continuously animate icons
Use animated marketing backgrounds
Use stars/bubbles/particles
Use carousel dashboards
Use flip cards
Use morphing operational headings
Add multiple animation libraries
Add fake metrics
```

---

# Completion Requirements

Before declaring the integration complete:

```text
Only useful Animate UI components are installed
Unused components are not installed
KPI numbers animate tastefully
Ticket loading progress uses real task progress
Sheets improve selected workflows
Tooltips clarify icon-only actions
Tabs improve information architecture
Animations remain subtle
Tables remain fast
StrictMode remains enabled
Reduced-motion behavior works
No duplicate API requests are introduced
No artificial loading delays exist
Mobile works
Tablet works
Desktop works
Production build succeeds
```

---

# Required Final Report

Codex must report:

## Animate UI Components Installed

List actual components only.

Example:

```text
Sliding Number
Progress
Sheet
Tooltip
Tabs
```

## Dependency Changes

Report:

```text
New npm dependencies
Dependencies automatically added by the registry
Existing dependencies reused
Why each dependency was needed
```

## Pages Updated

Report actual updates to:

```text
Login
Customer
Staff
Manager
Admin
Users
Tickets
Notifications
Reports
Audit
Settings
```

## Motion Behavior

Document:

```text
KPI animation
Ticket loading progress
Sheet transitions
Tooltip behavior
Tabs behavior
Animated icons
Live Activity behavior
```

## Performance

Confirm:

```text
No duplicate API calls introduced
No new request loops
No new aggressive polling
No artificial delay
No expensive continuous animation
```

## Accessibility

Confirm:

```text
Keyboard behavior
Focus management
ARIA where required
prefers-reduced-motion support
```

## Build Verification

Run:

```bash
npm test
```

if configured.

Then:

```bash
npm run build
```

Do not mark the task complete if the production build fails because of the Animate UI integration.
