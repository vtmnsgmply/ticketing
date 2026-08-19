import * as TooltipPrimitive from '@radix-ui/react-tooltip'
import { clsx } from 'clsx'

export default function Tooltip({ children, content, side = 'bottom', align = 'center' }) {
  if (!content) return children

  return (
    <TooltipPrimitive.Provider delayDuration={250} skipDelayDuration={100}>
      <TooltipPrimitive.Root>
        <TooltipPrimitive.Trigger asChild>{children}</TooltipPrimitive.Trigger>
        <TooltipPrimitive.Portal>
          <TooltipPrimitive.Content
            align={align}
            className={clsx(
              'z-60 rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg',
              'data-[state=delayed-open]:motion-safe:animate-in data-[state=closed]:motion-safe:animate-out',
              'data-[state=delayed-open]:motion-safe:fade-in-0 data-[state=closed]:motion-safe:fade-out-0',
            )}
            side={side}
            sideOffset={8}
          >
            {content}
            <TooltipPrimitive.Arrow className="fill-slate-950" />
          </TooltipPrimitive.Content>
        </TooltipPrimitive.Portal>
      </TooltipPrimitive.Root>
    </TooltipPrimitive.Provider>
  )
}

