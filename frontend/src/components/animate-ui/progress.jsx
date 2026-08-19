import * as ProgressPrimitive from '@radix-ui/react-progress'
import { motion, useReducedMotion } from 'motion/react'
import { clsx } from 'clsx'

export function Progress({ className, value = 0, max = 100, children, ...props }) {
  return (
    <ProgressPrimitive.Root
      className={clsx('relative overflow-hidden', className)}
      max={max}
      value={value}
      {...props}
    >
      {children}
    </ProgressPrimitive.Root>
  )
}

export function ProgressIndicator({ className, value = 0, max = 100, transition, ...props }) {
  const reduceMotion = useReducedMotion()
  const clamped = Math.min(Math.max(value, 0), max)
  const width = max === 0 ? 0 : (clamped / max) * 100

  return (
    <ProgressPrimitive.Indicator asChild>
      <motion.div
        animate={{ width: `${width}%` }}
        className={clsx('h-full', className)}
        initial={false}
        transition={reduceMotion ? { duration: 0 } : (transition ?? { type: 'spring', stiffness: 100, damping: 30 })}
        {...props}
      />
    </ProgressPrimitive.Indicator>
  )
}
