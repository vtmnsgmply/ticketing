export const tones = {
  slate: 'slate',
  blue: 'blue',
  indigo: 'indigo',
  violet: 'violet',
  emerald: 'emerald',
  amber: 'amber',
  orange: 'orange',
  red: 'red',
}

export function normalizeTone(tone, fallback = tones.slate) {
  if (tone === 'info') return tones.slate
  return Object.hasOwn(tones, tone) ? tone : fallback
}

