import { clsx } from 'clsx'

// Centralized workspace width tiers (see index.css). Never reach for a
// one-off max-w-[...] on a page — pick the tier that matches its archetype.
const widths = {
  // No max-w cap — the true operational canvas (dashboards, ticket queues,
  // user management) should keep using the screen on very large monitors
  // instead of centering inside a narrow column.
  full: 'px-4 sm:px-5 lg:px-6 xl:px-7 2xl:px-8',
  wide: 'max-w-[var(--width-workspace-wide)] px-6 xl:px-8',
  dense: 'max-w-[var(--width-workspace-dense)] px-6 xl:px-8',
  catalog: 'max-w-[1280px] px-4 sm:px-6 lg:px-8',
  standard: 'max-w-[var(--width-workspace-standard)] px-4 sm:px-6 lg:px-8',
}

export default function PageContainer({ width = 'standard', className, children }) {
  return <div className={clsx('mx-auto w-full', widths[width], className)}>{children}</div>
}
