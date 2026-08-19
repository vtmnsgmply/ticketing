import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import AppShell from '../../components/layout/AppShell'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import Field from '../../components/ui/Field'
import Input from '../../components/ui/Input'
import PageHeader from '../../components/ui/PageHeader'
import Select from '../../components/ui/Select'
import Textarea from '../../components/ui/Textarea'
import { adminService } from '../../services/adminService'
import { createTicket, getTicketOptions } from '../../services/ticketService'
import { showError, showSuccess } from '../../utils/alerts'
import { useAuth } from '../../hooks/useAuth'

export default function TicketCreate() {
  const navigate = useNavigate()
  const { user } = useAuth()
  const isAdmin = user?.role?.slug === 'administrator'
  const basePath = user?.role?.slug === 'customer' ? '/customer/tickets' : user?.role?.slug === 'agent' ? '/staff/tickets' : user?.role?.slug === 'manager' ? '/manager/tickets' : '/tickets'
  const [options, setOptions] = useState({ departments: [], categories: [], priorities: [] })
  const [customers, setCustomers] = useState([])
  const [customerRoleId, setCustomerRoleId] = useState('')
  const [customerSearch, setCustomerSearch] = useState('')
  const [customerOpen, setCustomerOpen] = useState(false)
  const [customerLoading, setCustomerLoading] = useState(false)
  const [form, setForm] = useState({ subject: '', description: '', customer_id: '', department_id: '', category_id: '', priority_id: '', attachments: [] })
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    getTicketOptions().then(setOptions).catch((error) => showError(error.message || 'Unable to load ticket options.'))
  }, [])

  useEffect(() => {
    if (!isAdmin) return

    async function loadCustomerRole() {
      try {
        const roles = await adminService.getRoles()
        const customerRole = roles.find((role) => role.slug === 'customer')
        if (!customerRole) return
        setCustomerRoleId(String(customerRole.id))
      } catch (error) {
        await showError(error.message || 'Unable to load customers.')
      }
    }

    loadCustomerRole()
  }, [isAdmin])

  useEffect(() => {
    if (!isAdmin || !customerRoleId || !customerOpen) return

    const timeout = window.setTimeout(async () => {
      try {
        setCustomerLoading(true)
        const data = await adminService.getUsers({
          role_id: customerRoleId,
          search: customerSearch,
          per_page: 8,
        })
        setCustomers(data.users)
      } catch (error) {
        await showError(error.message || 'Unable to search customers.')
      } finally {
        setCustomerLoading(false)
      }
    }, 180)

    return () => window.clearTimeout(timeout)
  }, [customerOpen, customerRoleId, customerSearch, isAdmin])

  const selectedCustomer = customers.find((customer) => String(customer.id) === String(form.customer_id))

  function updateField(event) {
    setForm((current) => ({ ...current, [event.target.name]: event.target.value }))
  }

  function selectCustomer(customer) {
    setForm((current) => ({ ...current, customer_id: String(customer.id) }))
    setCustomerSearch(`${customer.name} (${customer.email})`)
    setCustomerOpen(false)
  }

  function addFiles(event) {
    setForm((current) => ({ ...current, attachments: [...current.attachments, ...Array.from(event.target.files)] }))
    event.target.value = ''
  }

  function removeFile(index) {
    setForm((current) => ({ ...current, attachments: current.attachments.filter((_, fileIndex) => fileIndex !== index) }))
  }

  async function submit(event) {
    event.preventDefault()
    setSubmitting(true)
    setErrors({})
    if (isAdmin && !form.customer_id) {
      setErrors({ customer_id: ['Please select a customer.'] })
      await showError('Please select a customer before creating the ticket.')
      setSubmitting(false)
      return
    }

    try {
      const ticket = await createTicket(form)
      await showSuccess(`Ticket ${ticket.ticket_number} was created successfully.`)
      navigate(`${basePath}/${ticket.id}`)
    } catch (error) {
      setErrors(error.payload?.errors ?? {})
      await showError(error.message || 'Unable to create ticket.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <AppShell width="standard">
      <PageHeader
        actions={
          <Button as={Link} to={basePath} variant="secondary">
            <ArrowLeft aria-hidden="true" className="h-4 w-4" />
            Back to tickets
          </Button>
        }
        eyebrow="Tickets"
        title="Create Ticket"
      />
      <Card className="max-w-3xl">
        <form className="space-y-5" onSubmit={submit}>
          {isAdmin ? (
            <Field error={errors.customer_id?.[0]} htmlFor="ticket-customer" label="Customer">
              <div className="relative">
                <Input
                  autoComplete="off"
                  id="ticket-customer"
                  invalid={Boolean(errors.customer_id)}
                  onBlur={() => window.setTimeout(() => setCustomerOpen(false), 120)}
                  onChange={(event) => {
                    setCustomerSearch(event.target.value)
                    setForm((current) => ({ ...current, customer_id: '' }))
                    setCustomerOpen(true)
                  }}
                  onFocus={() => setCustomerOpen(true)}
                  placeholder="Search by customer name, email, company, or phone"
                  value={customerSearch}
                />
                {customerOpen ? (
                  <div className="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                    {customerLoading ? (
                      <p className="px-4 py-3 text-sm text-slate-500">Searching...</p>
                    ) : customers.length ? customers.map((customer) => (
                      <button
                        className="flex w-full px-4 py-3 text-left text-sm font-semibold text-slate-900 hover:bg-slate-50 focus:bg-slate-50 focus:outline-none"
                        key={customer.id}
                        onMouseDown={(event) => {
                          event.preventDefault()
                          selectCustomer(customer)
                        }}
                        type="button"
                      >
                        {customer.name}
                      </button>
                    )) : (
                      <p className="px-4 py-3 text-sm text-slate-500">No customers found.</p>
                    )}
                  </div>
                ) : null}
              </div>
              {selectedCustomer ? (
                <div className="mt-3 grid grid-cols-1 gap-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-600 sm:grid-cols-2">
                  <p><span className="font-semibold text-slate-700">Email:</span> {selectedCustomer.email}</p>
                  <p><span className="font-semibold text-slate-700">Phone:</span> {selectedCustomer.phone ?? '-'}</p>
                  <p><span className="font-semibold text-slate-700">Company:</span> {selectedCustomer.company ?? '-'}</p>
                  <p><span className="font-semibold text-slate-700">Status:</span> {selectedCustomer.is_active ? 'Active' : 'Disabled'}</p>
                </div>
              ) : null}
            </Field>
          ) : null}

          <Field error={errors.subject?.[0]} htmlFor="ticket-subject" label="Subject">
            <Input id="ticket-subject" invalid={Boolean(errors.subject)} name="subject" onChange={updateField} value={form.subject} />
          </Field>

          <Field error={errors.description?.[0]} htmlFor="ticket-description" label="Description">
            <Textarea
              id="ticket-description"
              invalid={Boolean(errors.description)}
              name="description"
              onChange={updateField}
              value={form.description}
            />
          </Field>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Field htmlFor="ticket-department" label="Department">
              <Select id="ticket-department" name="department_id" onChange={updateField} value={form.department_id}>
                <option value="">Default</option>
                {options.departments.map((department) => (
                  <option key={department.id} value={department.id}>
                    {department.name}
                  </option>
                ))}
              </Select>
            </Field>

            <Field htmlFor="ticket-category" label="Category">
              <Select id="ticket-category" name="category_id" onChange={updateField} value={form.category_id}>
                <option value="">Default</option>
                {options.categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </Select>
            </Field>

            <Field htmlFor="ticket-priority" label="Priority">
              <Select id="ticket-priority" name="priority_id" onChange={updateField} value={form.priority_id}>
                <option value="">Default</option>
                {options.priorities.map((priority) => (
                  <option key={priority.id} value={priority.id}>
                    {priority.name}
                  </option>
                ))}
              </Select>
            </Field>
          </div>

          <Field htmlFor="ticket-attachments" label="Attachments">
            <Input
              accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
              id="ticket-attachments"
              multiple
              name="attachments"
              onChange={addFiles}
              type="file"
            />
            {form.attachments.length ? (
              <ul className="mt-3 space-y-2">
                {form.attachments.map((file, index) => (
                  <li className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm" key={`${file.name}-${index}`}>
                    <span className="truncate">{file.name} ({Math.ceil(file.size / 1024)} KB)</span>
                    <Button onClick={() => removeFile(index)} size="sm" variant="ghost">Remove</Button>
                  </li>
                ))}
              </ul>
            ) : null}
          </Field>

          <Button disabled={submitting} loading={submitting} type="submit">
            {submitting ? 'Creating...' : 'Create ticket'}
          </Button>
        </form>
      </Card>
    </AppShell>
  )
}
