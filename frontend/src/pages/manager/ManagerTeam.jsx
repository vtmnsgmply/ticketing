import { useEffect, useState } from 'react'
import AppShell from '../../components/layout/AppShell'
import { ActiveBadge, Card, EmptyState, PageHeader, Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui'
import { getManagerTeam } from '../../services/managerService'
import { showError } from '../../utils/alerts'

export default function ManagerTeam() {
  const [team, setTeam] = useState([])

  useEffect(() => {
    getManagerTeam().then(setTeam).catch((error) => showError(error.message || 'Unable to load team.'))
  }, [])

  return (
    <AppShell width="dense">
      <PageHeader eyebrow="Manager Workspace" title="Team" />
      <Card>
        {team.length ? (
          <TableContainer>
            <Table>
              <Thead><tr><Th>Agent</Th><Th>Email</Th><Th>Department</Th><Th>Status</Th><Th>Open</Th><Th>Waiting</Th><Th>Overdue</Th><Th>High Priority</Th><Th>Resolved Today</Th></tr></Thead>
              <Tbody>
                {team.map((agent) => (
                  <Tr key={agent.id} sla={agent.overdue > 0 ? 'overdue' : undefined}>
                    <Td primary>{agent.name}</Td>
                    <Td>{agent.email}</Td>
                    <Td>{agent.department?.name ?? '-'}</Td>
                    <Td><ActiveBadge active={agent.is_active} /></Td>
                    <Td numeric>{agent.assigned_tickets}</Td>
                    <Td numeric>{agent.waiting_for_customer}</Td>
                    <Td numeric>{agent.overdue}</Td>
                    <Td numeric>{agent.high_priority}</Td>
                    <Td numeric>{agent.resolved_today}</Td>
                  </Tr>
                ))}
              </Tbody>
            </Table>
          </TableContainer>
        ) : (
          <EmptyState title="No team members" description="Agents in your managed departments will appear here." />
        )}
      </Card>
    </AppShell>
  )
}
