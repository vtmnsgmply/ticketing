import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { CheckCircle2, LifeBuoy, Loader2, Pause, PlusCircle, Ticket } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import { Button, Card, MetricCard, PageHeader, SectionHeader, Table, TableContainer, Tbody, Td, Th, Thead, Tr, EmptyState, Skeleton } from '../../components/ui'
import TicketPriorityBadge from '../../components/tickets/TicketPriorityBadge'
import TicketStatusBadge from '../../components/tickets/TicketStatusBadge'
import { getCustomerDashboard } from '../../services/customerService'
import { showError } from '../../utils/alerts'

export default function CustomerDashboard() {
  const [data, setData] = useState(null)

  useEffect(() => {
    getCustomerDashboard().then(setData).catch((error) => showError(error.message || 'Unable to load dashboard.'))
  }, [])

  return (
    <AppShell width="wide">
      <PageHeader
        actions={<Button as={Link} to="/customer/tickets/create"><PlusCircle className="h-4 w-4" />Create New Ticket</Button>}
        eyebrow="Customer Portal"
        title="Dashboard"
      />
      {!data ? (
        <div className="space-y-4"><Skeleton className="h-28 w-full" /><Skeleton className="h-72 w-full" /></div>
      ) : (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
            <MetricCard icon={Ticket} label="Open Tickets" tone="blue" topAccent value={data.summary.open} />
            <MetricCard icon={Loader2} label="In Progress" tone="amber" topAccent value={data.summary.in_progress} />
            <MetricCard icon={Pause} label="Waiting for You" tone="slate" value={data.summary.waiting_for_customer} />
            <MetricCard icon={CheckCircle2} label="Resolved" tone="emerald" topAccent value={data.summary.resolved} />
          </div>
          <Card padded={false}>
            <div className="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
              <h2 className="text-lg font-semibold tracking-tight text-slate-950">Recent Tickets</h2>
              <Button as={Link} to="/customer/tickets" variant="secondary">View All Tickets</Button>
            </div>
            {data.recent_tickets.length ? (
              <TableContainer>
                <Table>
                  <Thead><tr><Th>Ticket</Th><Th>Subject</Th><Th>Category</Th><Th>Priority</Th><Th>Status</Th><Th>Updated</Th></tr></Thead>
                  <Tbody>
                    {data.recent_tickets.map((ticket) => (
                      <Tr key={ticket.id}>
                        <Td><Link className="font-mono text-xs font-semibold text-slate-500 hover:text-indigo-600" to={`/customer/tickets/${ticket.id}`}>{ticket.ticket_number}</Link></Td>
                        <Td primary>{ticket.subject}</Td>
                        <Td>{ticket.category?.name ?? '-'}</Td>
                        <Td><TicketPriorityBadge priority={ticket.priority} /></Td>
                        <Td><TicketStatusBadge status={ticket.status} /></Td>
                        <Td>{new Date(ticket.updated_at).toLocaleString()}</Td>
                      </Tr>
                    ))}
                  </Tbody>
                </Table>
              </TableContainer>
            ) : (
              <div className="p-5">
                <EmptyState description="Create a ticket whenever you need assistance." icon={Ticket} title="You don't have any tickets yet." />
              </div>
            )}
          </Card>

          <Card padded={false}>
            <SectionHeader description="Reach out if you need a hand with anything." title="Need help?" />
            <div className="flex items-start gap-3 p-5">
              <LifeBuoy aria-hidden="true" className="h-5 w-5 shrink-0 text-indigo-600" strokeWidth={1.75} />
              <p className="text-sm text-slate-600">
                Create a ticket for any issue and our support team will respond according to its priority. You can track replies and
                updates from the ticket detail page at any time.
              </p>
            </div>
          </Card>
        </div>
      )}
    </AppShell>
  )
}
