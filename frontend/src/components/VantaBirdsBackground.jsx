import { useEffect, useRef } from 'react'
import { clsx } from 'clsx'

// Vite's dev-mode CJS interop wraps this UMD module's export as `.default`
// one level deeper than the production Rollup build does, so the factory
// ends up at `module.default` in dev but `module.default` is already the
// function in a prod build. Walk `.default` until we hit a callable instead
// of hardcoding a nesting depth that only holds for one of the two.
function unwrapFactory(module) {
  let value = module
  while (value && typeof value !== 'function' && 'default' in value) {
    value = value.default
  }
  return value
}

// three.js + vanta are loaded lazily so they only enter the bundle for the
// Login route, not every authenticated page. Respects reduced-motion.
export default function VantaBirdsBackground({ className }) {
  const containerRef = useRef(null)

  useEffect(() => {
    const node = containerRef.current
    if (!node) return undefined
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return undefined

    let effect
    let cancelled = false

    Promise.all([import('three'), import('vanta/dist/vanta.birds.min')])
      .then(([THREE, vantaBirds]) => {
        if (cancelled) return
        const BIRDS = unwrapFactory(vantaBirds)
        effect = BIRDS({
          el: node,
          THREE,
          mouseControls: true,
          touchControls: true,
          gyroControls: false,
          minHeight: 200.0,
          minWidth: 200.0,
          scale: 1.0,
          scaleMobile: 1.0,
        })
      })
      .catch((error) => {
        console.error('Unable to start Vanta birds background.', error)
      })

    return () => {
      cancelled = true
      effect?.destroy()
    }
  }, [])

  return <div aria-hidden="true" className={clsx('pointer-events-none', className)} ref={containerRef} />
}
