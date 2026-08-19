import { motion, useReducedMotion } from 'motion/react'

function digitCharacters(value, decimalPlaces, thousandSeparator, decimalSeparator) {
  const formatter = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: decimalPlaces,
    maximumFractionDigits: decimalPlaces,
    useGrouping: Boolean(thousandSeparator),
  })

  return formatter
    .format(value)
    .replaceAll(',', thousandSeparator || ',')
    .replace('.', decimalSeparator)
    .split('')
}

function Digit({ value, delay = 0 }) {
  const reduceMotion = useReducedMotion()

  return (
    <span className="relative inline-block h-[1em] overflow-hidden align-[-0.08em]">
      <motion.span
        animate={{ y: `${value * -10}%` }}
        className="block"
        initial={false}
        transition={reduceMotion ? { duration: 0 } : { type: 'spring', stiffness: 200, damping: 20, mass: 0.4, delay }}
      >
        {'0123456789'.split('').map((digit) => (
          <span className="block h-[1em] tabular-nums" key={digit}>
            {digit}
          </span>
        ))}
      </motion.span>
    </span>
  )
}

export default function SlidingNumber({
  number,
  decimalPlaces = 0,
  decimalSeparator = '.',
  thousandSeparator = '',
  delay = 0,
  ...props
}) {
  const safeNumber = Number.isFinite(Number(number)) ? Number(number) : 0
  const characters = digitCharacters(safeNumber, decimalPlaces, thousandSeparator, decimalSeparator)

  return (
    <motion.span className="inline-flex tabular-nums" layout="position" {...props}>
      {characters.map((character, index) => (
        /\d/.test(character)
          ? <Digit delay={delay} key={index} value={Number(character)} />
          : <span key={index}>{character}</span>
      ))}
    </motion.span>
  )
}
