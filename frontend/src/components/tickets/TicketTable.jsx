import { Link } from 'react-router-dom'
import { ChevronRight, Eye, Inbox } from 'lucide-react'
import Button from '../ui/Button'
import EmptyState from '../ui/EmptyState'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../ui/Table'
import TicketPriorityBadge from './TicketPriorityBadge'
import TicketStatusBadge from './TicketStatusBadge'

function canResolve(ticket, isStaff) {
  return isStaff && ['in_progress', 'waiting_for_customer'].includes(ticket.status)
}

function canClose(ticket, canManageFinalState) {
  return canManageFinalState && ticket.status === 'resolved'
}

function canCancel(ticket, canManageFinalState) {
  return canManageFinalState && ['new', 'open', 'assigned', 'in_progress'].includes(ticket.status)
}

export default function TicketTable({ tickets, basePath = '/tickets', isStaff = false, canManageFinalState = false, onResolve, onClose, onCancel }) {
  if (tickets.length === 0) {
    return <EmptyState description="No tickets match your current filters." icon={Inbox} title="No tickets found" />
  }

  return (
    <>
      <TableContainer className="hidden md:block">
        <Table>
          <Thead>
            <tr>
              <Th>Ticket</Th>
              <Th>Subject</Th>
              <Th>Priority</Th>
              <Th>Status</Th>
              <Th>Updated</Th>
              <Th>Actions</Th>
            </tr>
          </Thead>
          <Tbody>
            {tickets.map((ticket) => (
              <Tr key={ticket.id}>
                <Td primary>
                  <Link className="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline" to={`${basePath}/${ticket.id}`}>
                    {ticket.ticket_number}
                  </Link>
                </Td>
                <Td className="max-w-xs truncate" primary>
                  {ticket.subject}
                </Td>
                <Td>
                  <TicketPriorityBadge priority={ticket.priority} />
                </Td>
                <Td>
                  <TicketStatusBadge status={ticket.status} />
                </Td>
                <Td>{new Date(ticket.updated_at).toLocaleString()}</Td>
                <Td>
                  <div className="flex flex-wrap items-center gap-2">
                    <Button as={Link} size="sm" to={`${basePath}/${ticket.id}`} variant="secondary">
                      <Eye aria-hidden="true" className="h-4 w-4" />
                      View
                    </Button>
                    {canResolve(ticket, isStaff) ? <Button onClick={() => onResolve(ticket)} size="sm" variant="secondary">Resolve</Button> : null}
                    {canClose(ticket, canManageFinalState) ? <Button onClick={() => onClose(ticket)} size="sm" variant="secondary">Close</Button> : null}
                    {canCancel(ticket, canManageFinalState) ? <Button onClick={() => onCancel(ticket)} size="sm" variant="danger">Cancel</Button> : null}
                  </div>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>

      <ul className="space-y-3 md:hidden">
        {tickets.map((ticket) => (
          <li key={ticket.id}>
            <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
              <div className="min-w-0">
                <p className="text-xs font-semibold text-indigo-600">{ticket.ticket_number}</p>
                <p className="mt-1 truncate text-sm font-medium text-slate-900">{ticket.subject}</p>
                <div className="mt-2 flex flex-wrap items-center gap-2">
                  <TicketStatusBadge status={ticket.status} />
                  <TicketPriorityBadge priority={ticket.priority} />
                </div>
                <p className="mt-2 text-xs text-slate-500">Updated {new Date(ticket.updated_at).toLocaleString()}</p>
              </div>
              <div className="mt-3 flex flex-wrap items-center gap-2">
                <Button as={Link} size="sm" to={`${basePath}/${ticket.id}`} variant="secondary">
                  View
                  <ChevronRight aria-hidden="true" className="h-4 w-4" />
                </Button>
                {canResolve(ticket, isStaff) ? <Button onClick={() => onResolve(ticket)} size="sm" variant="secondary">Resolve</Button> : null}
                {canClose(ticket, canManageFinalState) ? <Button onClick={() => onClose(ticket)} size="sm" variant="secondary">Close</Button> : null}
                {canCancel(ticket, canManageFinalState) ? <Button onClick={() => onCancel(ticket)} size="sm" variant="danger">Cancel</Button> : null}
              </div>
            </div>
          </li>
        ))}
      </ul>
    </>
  )
}
