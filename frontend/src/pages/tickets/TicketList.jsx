import { useCallback, useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PlusCircle } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import TicketFilters from '../../components/tickets/TicketFilters'
import TicketTable from '../../components/tickets/TicketTable'
import Button from '../../components/ui/Button'
import PageHeader from '../../components/ui/PageHeader'
import Pagination from '../../components/ui/Pagination'
import { TableContainer, TableSkeleton } from '../../components/ui'
import { confirmAction, showError, showSuccess } from '../../utils/alerts'
import { cancelTicket, changeStatus, closeTicket, getTicketOptions, getTickets } from '../../services/ticketService'
import { useAuth } from '../../hooks/useAuth'

const defaultOptions = { statuses: [], priorities: [] }

export default function TicketList() {
  const { user } = useAuth()
  const [searchParams] = useSearchParams()
  const queueParam = searchParams.get('queue') ?? ''
  const isCustomer = user?.role?.slug === 'customer'
  const isAgent = user?.role?.slug === 'agent'
  const isManager = user?.role?.slug === 'manager'
  const isStaff = ['agent', 'manager', 'administrator'].includes(user?.role?.slug)
  const canManageFinalState = ['manager', 'administrator'].includes(user?.role?.slug)
  const basePath = isCustomer ? '/customer/tickets' : isAgent ? '/staff/tickets' : isManager ? '/manager/tickets' : '/tickets'
  const [tickets, setTickets] = useState([])
  const [options, setOptions] = useState(defaultOptions)
  const [pagination, setPagination] = useState(null)
  const [loading, setLoading] = useState(true)
  const [filters, setFilters] = useState({ search: '', status: '', priority: '', queue: queueParam, per_page: 20 })

  const load = useCallback(async (params) => {
    setLoading(true)
    try {
      const [optionData, ticketData] = await Promise.all([getTicketOptions(), getTickets(params)])
      setOptions(optionData)
      setTickets(ticketData.tickets)
      setPagination(ticketData.pagination)
    } catch (error) {
      await showError(error.message || 'Unable to load tickets.')
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    setFilters((current) => {
      const next = { ...current, queue: queueParam }
      load(next)
      return next
    })
  }, [load, queueParam])

  function updateFilter(event) {
    setFilters((current) => ({ ...current, [event.target.name]: event.target.value }))
  }

  function submitFilters(event) {
    event.preventDefault()
    const next = { ...filters, page: 1 }
    setFilters(next)
    load(next)
  }

  function goToPage(page) {
    const next = { ...filters, page }
    setFilters(next)
    load(next)
  }

  async function runTicketAction(ticket, label, successLabel, action) {
    const result = await confirmAction(`${label} ${ticket.ticket_number}?`, 'Confirm ticket action')
    if (!result.isConfirmed) return

    try {
      await action(ticket)
      await showSuccess(`Ticket ${successLabel}.`)
      await load(filters)
    } catch (error) {
      await showError(error.message || `Unable to ${label.toLowerCase()} ticket.`)
    }
  }

  return (
    <AppShell width="full">
      <PageHeader
        actions={
          <Button as={Link} to={`${basePath}/create`}>
            <PlusCircle aria-hidden="true" className="h-4 w-4" />
            Create Ticket
          </Button>
        }
        eyebrow="Tickets"
        title="Ticket List"
      />
      <div className="space-y-4">
        <TicketFilters filters={filters} onChange={updateFilter} onSubmit={submitFilters} options={options} />
        {loading ? (
          <TableContainer>
            <TableSkeleton columns={5} rows={6} />
          </TableContainer>
        ) : (
          <TicketTable
            basePath={basePath}
            canManageFinalState={canManageFinalState}
            isStaff={isStaff}
            onCancel={(ticket) => runTicketAction(ticket, 'Cancel', 'cancelled', () => cancelTicket(ticket.id))}
            onClose={(ticket) => runTicketAction(ticket, 'Close', 'closed', () => closeTicket(ticket.id))}
            onResolve={(ticket) => runTicketAction(ticket, 'Resolve', 'resolved', () => changeStatus(ticket.id, { status: 'resolved' }))}
            tickets={tickets}
          />
        )}
        <Pagination onPageChange={goToPage} pagination={pagination} />
      </div>
    </AppShell>
  )
}
