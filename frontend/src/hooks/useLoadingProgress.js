import { useContext } from 'react'
import { LoadingProgressContext } from '../context/loadingProgressContextValue'

export function useLoadingProgress() {
  const context = useContext(LoadingProgressContext)

  if (context === null) {
    throw new Error('useLoadingProgress must be used within a LoadingProgressProvider')
  }

  return context
}
