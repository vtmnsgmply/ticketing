import { useCallback, useMemo, useState } from 'react'
import { LoadingProgressContext } from './loadingProgressContextValue'

const initialTasks = {
  auth: { label: 'Secure session', weight: 45, status: 'pending' },
  notifications: { label: 'Notifications', weight: 25, status: 'pending' },
  workspace: { label: 'Workspace', weight: 30, status: 'pending' },
}

function taskMessage(tasks) {
  const failed = Object.values(tasks).find((task) => task.status === 'failed')
  if (failed) return "We couldn't finish loading your workspace."

  if (tasks.auth.status !== 'complete') return 'Starting secure session...'
  if (tasks.notifications.status !== 'complete') return 'Checking notifications...'
  if (tasks.workspace.status !== 'complete') return 'Preparing your workspace...'

  return 'Ready'
}

export function LoadingProgressProvider({ children }) {
  const [tasks, setTasks] = useState(initialTasks)

  const updateTask = useCallback((id, status, options = {}) => {
    setTasks((current) => ({
      ...current,
      [id]: {
        ...(current[id] ?? { label: options.label ?? id, weight: options.weight ?? 1 }),
        ...options,
        status,
      },
    }))
  }, [])

  const startTask = useCallback((id, options) => updateTask(id, 'active', options), [updateTask])
  const completeTask = useCallback((id) => updateTask(id, 'complete'), [updateTask])
  const failTask = useCallback((id) => updateTask(id, 'failed'), [updateTask])

  const resetProgress = useCallback(() => {
    setTasks(initialTasks)
  }, [])

  const value = useMemo(() => {
    const taskList = Object.entries(tasks).map(([id, task]) => ({ id, ...task }))
    const totalWeight = taskList.reduce((sum, task) => sum + task.weight, 0)
    const completedWeight = taskList.reduce((sum, task) => sum + (task.status === 'complete' ? task.weight : 0), 0)
    const failed = taskList.some((task) => task.status === 'failed')
    const progress = totalWeight === 0 ? 100 : Math.round((completedWeight / totalWeight) * 100)

    return {
      tasks: taskList,
      progress,
      status: taskMessage(tasks),
      failed,
      startTask,
      completeTask,
      failTask,
      resetProgress,
    }
  }, [tasks, startTask, completeTask, failTask, resetProgress])

  return <LoadingProgressContext.Provider value={value}>{children}</LoadingProgressContext.Provider>
}
