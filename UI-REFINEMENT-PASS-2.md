# UI-REFINEMENT-PASS-2.md

## Purpose

This is the next implementation pass for Codex based on Claude's completed design review.

Codex must read this file together with:

```text
AGENTS.md
UI-DESIGN.md
UI-REFINEMENT-PREMIUM-LAYOUT.md
UI-LIVE-ENTERPRISE-UPGRADE.md
UI-ANIMATE-UI-INTEGRATION.md
```

The current system is already working.

This pass is NOT a redesign.

The goal is to fix the remaining issues preventing the admin experience from feeling fully premium and enterprise-grade.

Implementation order is strict:

```text
P0 first
P1 second
P2 third
```

Do not spend time on lower-priority polish while higher-priority issues remain.

---

# P0 — VISUAL / LAYOUT ISSUES

## 1. Stop using full-width layout on bounded catalog pages

The following pages should NOT use the same full-width workspace treatment as large operational screens:  

```text
Departments
Categories
Priorities
SLA
```

These pages normally contain:

```text
small configuration datasets
a few KPI / summary values
short tables
```

Using the full 1600px+ workspace makes these screens look unfinished when only a handful of rows exist.

### Required behavior

Keep full-width layouts for:

```text
Ticket List
Users
Admin Dashboard
Staff Dashboard
Manager Dashboard
Reporting
Audit when data volume warrants it
```

Use a narrower content tier for small catalog/configuration pages.

Recommended new page width tier:

```text
catalog
```

or:

```text
dense
```

Example:

```text
max-w-[1180px]
max-w-[1280px]
```

depending on the content.

Do not make these pages overly narrow.

They still need to feel enterprise-grade.

---

# 2. Give short pages a visual floor

Current short pages visually stop too early and leave a large empty tinted canvas.

Fix this deliberately.

Possible solutions:

```text
meaningful minimum content height
compact lower summary/status section
help / configuration note
empty-state / onboarding action when appropriate
small footer/status band
```

Do NOT fill the page with fake data.

Do NOT create meaningless decorative cards just to consume space.

The goal is:

```text
short page feels intentionally complete
```

not:

```text
table ends halfway down screen
then empty background
```

---

# 3. Unbox some content

Do not put every single page section inside:

```text
rounded card
border
shadow
```

The current product overuses cards.

Introduce controlled visual variety.

Good candidates for unboxed content:

```text
Page summary line
KPI group wrapper
Inline stats
Breadcrumb/header metadata
Filter summary
Small contextual status line
```

Example:

Instead of:

```text
Card
  KPI
Card
  KPI
Card
  KPI
```

the page may use:

```text
Unboxed summary / heading area
KPI cards
Primary table surface
```

Not every UI block needs a bordered white rectangle.

---

# P1 — REQUIRED ANIMATE UI / ACCESSIBILITY GAPS

## 4. Add prefers-reduced-motion support to AnimatedMetric

The existing `AnimatedMetric` currently animates every value through Sliding Number.

This must respect:

```text
prefers-reduced-motion
```

When reduced motion is enabled:

```text
render value immediately
or use minimal/no numeric animation
```

Do not force sliding number animation.

Possible implementation:

```text
useReducedMotion()
```

from the installed motion system if available,

or:

```text
window.matchMedia('(prefers-reduced-motion: reduce)')
```

using a stable reusable hook.

Do not introduce a second animation dependency.

---

# 5. Stop animating every numeric card

Do not use Sliding Number blanket-wide for every `MetricCard`.

Only animate metrics that:

```text
matter operationally
change during the session
represent meaningful live state
```

Good animated metrics:

```text
Open Tickets
Overdue Tickets
Critical Tickets
SLA Compliance
Assigned to Me
Resolved Today
Active Users
Unread Notifications
```

Usually static catalog counts should not animate:

```text
Total Priorities
Total Categories
Business Hours Rules
Static department count
```

unless there is a compelling reason.

Add an explicit prop such as:

```text
animated={true}
```

or equivalent.

Do not infer animation purely from the fact that a value is numeric.

---

# 6. Add Animate UI Tooltip

Implement Tooltip for icon-only controls at minimum.

Required first targets:

```text
Topbar notification bell
Topbar logout icon
Refresh buttons
Export icon buttons
Filter icon buttons
More-action icon buttons
Collapsed sidebar items if sidebar can collapse
```

Tooltip text must be short and useful.

Examples:

```text
Notifications
Sign out
Refresh
Export CSV
More actions
```

Do not hide essential information exclusively in tooltips.

Keyboard focus must work.

---

# 7. Make a clear decision on Sheet

The design specification explicitly recommends Sheet for:

```text
Create User
Edit User
Audit Detail
Mobile filters
```

Codex must inspect the existing workflows.

Preferred:

### Users

```text
Create User → right Sheet
Edit User → right Sheet
```

This preserves the user table and improves workspace usage.

### Audit

```text
Audit Log Detail → right Sheet
```

This keeps the log table visible in context.

### Mobile Filters

Use Sheet where appropriate.

If a Sheet is NOT adopted for a listed workflow:

Codex must document:

```text
why
what current implementation remains
why the current approach is better
```

Do not silently ignore this decision.

---

# 8. Improve LiveActivityPanel animation and data limit

The current LiveActivityPanel renders plain items.

Add:

```text
soft fade
small vertical slide
```

for newly inserted activity items.

Use subtle animation only.

Recommended movement:

```text
4–8px
```

Do not animate the entire panel on every poll.

Cap rendered activity items:

```text
5–10 items
```

Recommended default:

```text
8
```

Do not rely on an endlessly growing scroll container.

---

# P2 — CONSISTENCY / POLISH

## 9. Replace regex-based activityTone()

Do NOT assign activity severity using fragile regex matching such as:

```text
delete|disable|cancel → red
create|enable → green
everything else → blue
```

Use an explicit semantic mapping.

Example:

```text
auth.login_success
auth.logout
user.created
user.disabled
user.role_changed
ticket.assigned
ticket.reassigned
ticket.resolved
ticket.reopened
ticket.cancelled
sla.updated
settings.updated
```

Each should map to a meaningful semantic tone.

Preferred strategy:

```text
action -> tone mapping
```

Better long-term option:

```text
backend-supplied severity/tone
```

only if it can be added safely without breaking API compatibility.

Unknown actions should default to:

```text
slate
```

not blue.

Unknown does not mean informational.

---

# 10. Unify tone vocabulary

Shared components must use one common tone vocabulary.

Do not mix:

```text
info
blue
slate
```

in inconsistent ways.

Recommended canonical vocabulary:

```text
slate
blue
indigo
violet
emerald
amber
orange
red
```

All shared components should use the same tone naming:

```text
MetricCard
Badge
InsightStrip
Activity item
Status surfaces
Quick Actions
```

Create a shared mapping if appropriate.

---

# 11. Reserve indigo for brand / primary emphasis

Do not use indigo as a generic extra metric color.

Indigo should communicate:

```text
brand
primary
selected
important primary metric
```

Use other semantic tones for unrelated data.

Examples:

```text
Customers → blue/slate
Agents → violet
Healthy → emerald
Attention → amber
Critical → red
```

Do not use a rainbow palette.

---

# 12. Calm zero-value metrics

A metric such as:

```text
Overdue Tickets: 0
Critical Tickets: 0
Business Hours Rules: 0
```

should not look alarmist.

If the value is zero and zero means "healthy / none":

reduce the strong warning treatment.

Example behavior:

```text
Overdue > 0 → red accent
Overdue = 0 → slate or emerald calm state
```

Similarly:

```text
Critical > 0 → red
Critical = 0 → slate/emerald
```

Do not hardcode this blindly for every zero value.

Zero meaning must depend on metric semantics.

---

# 13. Audit Logs — format timestamps

Do not render raw backend timestamps such as:

```text
2026-08-13T12:01:22.000000Z
```

in the main table.

Display:

```text
Aug 13, 2026
8:01 PM
```

or a compact localized equivalent.

Use:

```text
Asia/Manila
```

for the current product timezone unless existing app timezone configuration says otherwise.

The raw timestamp may remain available in:

```text
Tooltip
Audit Detail Sheet
Technical detail view
```

if useful.

---

# 14. Audit Logs — humanize action names

Do not expose raw dotted action strings as the primary table label.

Instead of:

```text
auth.login_success
user.role_changed
ticket.reassigned
```

display:

```text
Login Successful
User Role Changed
Ticket Reassigned
```

The raw event key may remain available as secondary technical metadata.

Use a centralized mapping / humanization utility.

Known actions should have explicit human-readable names.

Unknown future actions may safely fall back to a generic humanizer:

```text
ticket.priority_changed
→ Ticket Priority Changed
```

---

# 15. Differentiate table density by page

Do not use one table row height for every module.

### Tickets / Customers

Use:

```text
comfortable density
```

because tickets contain more contextual information.

### Audit

Use:

```text
dense but readable
```

because audit data is scanning-heavy.

### Admin catalog tables

Use:

```text
compact enterprise density
```

for:

```text
Departments
Categories
Priorities
SLA
```

Do not sacrifice click targets or readability.

---

# 16. Add real pagination controls

Text such as:

```text
Page 1 of 1 · 2 total
```

is not sufficient.

Add real controls:

```text
Previous
Next
```

and page numbers where useful.

Disabled state must be visually clear.

Example:

```text
[← Previous]  Page 1 of 4  [Next →]
```

For large datasets:

```text
1 2 3 … 10
```

is acceptable.

Do not implement infinite scroll for:

```text
Tickets
Audit
Admin Users
Reports
```

---

# 17. Improve primary vs secondary surface hierarchy

Primary content and utility rails should not look equally important.

Example Admin Dashboard:

### Primary

```text
Recent Administrative Activity
Operational Ticket Summary
```

may use stronger visual weight.

### Secondary

```text
Quick Actions
System Health
Small insight panels
```

should be lighter / more compact.

Use:

```text
different padding
different heading size
subtle background variation
border treatment
```

Do not rely only on width to communicate hierarchy.

---

# 18. Add breadcrumbs on deeper admin pages

Add breadcrumbs where useful:

```text
Administration / Departments
Administration / Categories
Administration / Priorities
Administration / SLA
Administration / Audit Logs
Administration / Users
```

Use the existing PageHeader/Breadcrumb component if available.

Do not add breadcrumbs to the main dashboard if redundant.

---

# 19. Improve Admin Dashboard Row 4

The existing:

```text
Account status
Customer base
Structure
```

summary panels currently feel less polished than the KPI row above them.

Refine them into a more intentional secondary insight style.

Do NOT simply convert them into more identical KPI cards.

Possible direction:

```text
compact insight module
small icon/label
2–3 inline values
no giant number
lighter surface
```

This creates visual variety.

---

# 20. Improve System Health empty/unconfigured state

Do not show a large card containing only:

```text
Health monitoring not configured
```

Use a compact intentional state.

Example:

```text
System Health
Monitoring is not configured yet.
[Configure monitoring]   // only if such action actually exists
```

If no configure action exists:

use a smaller informative status row or compact panel.

Do not fabricate system health values.

---

# VALIDATION

After completing this pass:

## Visual QA

Verify:

```text
Admin Dashboard
Ticket List
Users
Departments
Categories
Priorities
SLA
Audit Logs
```

at:

```text
375px
430px
768px
1024px
1440px
1920px
```

---

# Performance QA

Preserve existing performance improvements:

```text
React StrictMode remains enabled
/api/me remains centralized
Notifications remain shared
SLA calls remain deduped
Single-flight request behavior remains intact
SANCTUM_LAST_USED_AT behavior remains unchanged
```

Check Chrome Network.

Do not introduce duplicate API calls.

---

# Animation QA

Verify:

```text
Reduced motion works
Static catalog metrics do not unnecessarily animate
Important KPIs animate correctly
Tooltips work
Live Activity animation is subtle
No animation loops
No fake loading delay
```

---

# Functional QA

Verify:

```text
Pagination works
Filters work
Create/Edit User works
Audit detail works
Tooltips are keyboard accessible
Sheets close correctly
Mobile filters work
Role-based actions remain restricted
```

---

# Build

Run:

```bash
npm run build
```

Fix all implementation-related errors.

If backend files were changed:

run backend tests.

---

# FINAL REPORT

Codex must report:

## P0

For each item:

```text
Implemented
Skipped
Reason
```

## P1

For each item:

```text
Implemented
Skipped
Reason
```

## P2

For each item:

```text
Implemented
Skipped
Reason
```

## Files Changed

Exact paths.

## Components Added / Updated

List:

```text
AnimatedMetric
Tooltip
Sheet
LiveActivityPanel
Pagination
PageContainer
Audit formatting utilities
Tone mapping utilities
```

as actually applicable.

## Dependencies

List only actual dependency changes.

## Performance

Confirm:

```text
No duplicate requests introduced
No fake delays
StrictMode retained
```

## Responsive QA

Report results for:

```text
375
430
768
1024
1440
1920
```

## Build

Report:

```text
npm run build
backend tests if applicable
remaining warnings
```

Do not mark this pass complete while any P0 issue remains unresolved without an explicit documented reason.
