import { Link } from 'react-router-dom'

export default function PageHeader({ eyebrow, title, actions, children, breadcrumbs }) {
  return (
    <header className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div className="min-w-0">
        {breadcrumbs?.length ? (
          <nav aria-label="Breadcrumb" className="mb-2 flex flex-wrap items-center gap-1 text-xs font-semibold text-slate-500">
            {breadcrumbs.map((item, index) => (
              <span className="flex items-center gap-1" key={`${item.label}-${index}`}>
                {index > 0 ? <span aria-hidden="true" className="text-slate-300">/</span> : null}
                {item.to ? <Link className="hover:text-indigo-600" to={item.to}>{item.label}</Link> : <span className="text-slate-700">{item.label}</span>}
              </span>
            ))}
          </nav>
        ) : null}
        {eyebrow ? <p className="text-xs font-bold uppercase tracking-wide text-indigo-600">{eyebrow}</p> : null}
        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{title}</h1>
        {children ? <div className="mt-1 max-w-2xl text-sm leading-6 text-slate-600">{children}</div> : null}
      </div>
      {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
    </header>
  )
}
