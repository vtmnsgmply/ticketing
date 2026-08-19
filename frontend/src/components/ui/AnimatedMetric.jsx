import SlidingNumber from '../animate-ui/sliding-number'

function parseMetric(value) {
  if (typeof value === 'number') {
    return { numeric: value, suffix: '', decimalPlaces: Number.isInteger(value) ? 0 : 1 }
  }

  if (typeof value !== 'string') return null

  const match = value.trim().match(/^(-?\d+(?:\.\d+)?)(.*)$/)
  if (!match) return null

  const numeric = Number(match[1])
  if (!Number.isFinite(numeric)) return null

  return {
    numeric,
    suffix: match[2] ?? '',
    decimalPlaces: match[1].includes('.') ? match[1].split('.')[1].length : 0,
  }
}

export default function AnimatedMetric({ value, animated = false }) {
  const metric = parseMetric(value)

  if (!metric) return value
  if (!animated) return `${metric.numeric.toLocaleString()}${metric.suffix}`

  return (
    <>
      <SlidingNumber decimalPlaces={metric.decimalPlaces} number={metric.numeric} thousandSeparator="," />
      {metric.suffix}
    </>
  )
}
