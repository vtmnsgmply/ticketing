export function Label({ children, htmlFor, className }) {
  return (
    <label className={`mb-1.5 block text-sm font-semibold text-slate-700 ${className ?? ''}`} htmlFor={htmlFor}>
      {children}
    </label>
  )
}

export function FieldError({ children }) {
  if (!children) return null
  return <p className="mt-1 text-xs font-medium text-red-600">{children}</p>
}

export default function Field({ label, htmlFor, error, hint, children, className }) {
  return (
    <div className={className}>
      {label ? <Label htmlFor={htmlFor}>{label}</Label> : null}
      {children}
      {hint ? <p className="mt-1 text-xs text-slate-500">{hint}</p> : null}
      <FieldError>{error}</FieldError>
    </div>
  )
}
