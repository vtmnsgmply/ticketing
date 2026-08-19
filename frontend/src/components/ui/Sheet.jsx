import * as Dialog from '@radix-ui/react-dialog'
import { X } from 'lucide-react'
import { AnimatePresence, motion, useReducedMotion } from 'motion/react'
import { clsx } from 'clsx'
import IconButton from './IconButton'

export default function Sheet({ open, onOpenChange, title, description, children, footer, className }) {
  const reducedMotion = useReducedMotion()

  return (
    <Dialog.Root onOpenChange={onOpenChange} open={open}>
      <AnimatePresence>
        {open ? (
          <Dialog.Portal forceMount>
            <Dialog.Overlay asChild forceMount>
              <motion.div
                animate={{ opacity: 1 }}
                className="fixed inset-0 z-50 bg-slate-950/35"
                exit={{ opacity: 0 }}
                initial={{ opacity: 0 }}
                transition={{ duration: reducedMotion ? 0 : 0.16 }}
              />
            </Dialog.Overlay>
            <Dialog.Content asChild forceMount>
              <motion.aside
                animate={{ opacity: 1, x: 0 }}
                className={clsx('fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col border-l border-slate-200 bg-white shadow-2xl', className)}
                exit={{ opacity: reducedMotion ? 1 : 0, x: reducedMotion ? 0 : 24 }}
                initial={{ opacity: reducedMotion ? 1 : 0, x: reducedMotion ? 0 : 24 }}
                transition={{ duration: reducedMotion ? 0 : 0.2, ease: [0.16, 1, 0.3, 1] }}
              >
                <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                  <div className="min-w-0">
                    <Dialog.Title className="text-base font-semibold text-slate-950">{title}</Dialog.Title>
                    {description ? <Dialog.Description className="mt-1 text-sm text-slate-500">{description}</Dialog.Description> : null}
                  </div>
                  <Dialog.Close asChild>
                    <IconButton aria-label="Close sheet" tooltip="Close">
                      <X aria-hidden="true" className="h-5 w-5" />
                    </IconButton>
                  </Dialog.Close>
                </div>
                <div className="min-h-0 flex-1 overflow-y-auto px-5 py-5">{children}</div>
                {footer ? <div className="border-t border-slate-200 px-5 py-4">{footer}</div> : null}
              </motion.aside>
            </Dialog.Content>
          </Dialog.Portal>
        ) : null}
      </AnimatePresence>
    </Dialog.Root>
  )
}

