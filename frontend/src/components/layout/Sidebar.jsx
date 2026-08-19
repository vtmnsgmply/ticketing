import { NavLink } from 'react-router-dom'
import { clsx } from 'clsx'
import { getNavSections } from './navConfig'

function NavContent({ role, onNavigate }) {
  const sections = getNavSections(role)

  return (
    <>
      <div className="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-5">
        <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">TS</span>
        <span className="text-sm font-semibold text-slate-900">Ticketing System</span>
      </div>
      <nav aria-label="Primary navigation" className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        {sections.map((section) => (
          <div key={section.title ?? 'workspace'}>
            {section.title ? (
              <p className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-400">{section.title}</p>
            ) : null}
            <div className="space-y-1">
              {section.items.map((item) => (
                <NavLink
                  className={({ isActive }) =>
                    clsx(
                      'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 transition-colors duration-150 ease-standard hover:bg-slate-100 hover:text-slate-950',
                      isActive && 'bg-indigo-50 font-semibold text-indigo-700 hover:bg-indigo-50 hover:text-indigo-700',
                    )
                  }
                  end={item.end}
                  key={item.to}
                  onClick={onNavigate}
                  to={item.to}
                >
                  {({ isActive }) => (
                    <>
                      <item.icon aria-hidden="true" className={clsx('h-4 w-4 shrink-0', isActive ? 'text-indigo-600' : 'text-slate-400')} />
                      {item.label}
                    </>
                  )}
                </NavLink>
              ))}
            </div>
          </div>
        ))}
      </nav>
    </>
  )
}

export default function Sidebar({ role, mobileOpen, onCloseMobile }) {
  return (
    <>
      <div className="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r border-slate-200 bg-white lg:flex">
        <NavContent role={role} />
      </div>

      {mobileOpen ? (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div aria-hidden="true" className="fixed inset-0 bg-slate-950/40" onClick={onCloseMobile} />
          <div className="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white shadow-2xl transition-transform duration-200 ease-decelerate">
            <NavContent onNavigate={onCloseMobile} role={role} />
          </div>
        </div>
      ) : null}
    </>
  )
}
