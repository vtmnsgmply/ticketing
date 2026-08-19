import Card from './Card'
import SectionHeader from './SectionHeader'
import ActionTile from './ActionTile'

// Role-aware quick actions rail. Each dashboard supplies its own `actions`
// array so the tiles stay meaningful per role instead of a generic shared set.
export default function QuickActionsPanel({ title = 'Quick Actions', description, actions = [], className }) {
  if (!actions.length) return null

  return (
    <Card className={className} padded={false}>
      <SectionHeader description={description} title={title} />
      <div className="grid grid-cols-2 gap-3 p-5">
        {actions.map((action) => (
          <ActionTile key={action.label} {...action} />
        ))}
      </div>
    </Card>
  )
}
