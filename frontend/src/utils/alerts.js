import Swal from 'sweetalert2'

const baseButtonClass =
  'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors duration-150 focus:outline-none focus:ring-2 motion-reduce:transition-none'

const swalDefaults = {
  buttonsStyling: false,
  reverseButtons: true,
  customClass: {
    popup: 'rounded-2xl',
    title: 'text-slate-900',
    htmlContainer: 'text-slate-600',
    confirmButton: `${baseButtonClass} bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500/30`,
    cancelButton: `${baseButtonClass} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus:ring-slate-300`,
    actions: 'gap-2',
  },
}

export function showSuccess(message, title = 'Success') {
  return Swal.fire({
    ...swalDefaults,
    icon: 'success',
    title,
    text: message,
  })
}

export function showError(message, title = 'Error') {
  return Swal.fire({
    ...swalDefaults,
    icon: 'error',
    title,
    text: message,
  })
}

export function showBlockingNotice({ html, message, title = 'Notice', icon = 'warning' }) {
  return Swal.fire({
    ...swalDefaults,
    allowEscapeKey: false,
    allowOutsideClick: false,
    confirmButtonText: 'I understand',
    html,
    icon,
    text: html ? undefined : message,
    title,
  })
}

export function confirmAction(message, title = 'Confirm action') {
  return Swal.fire({
    ...swalDefaults,
    icon: 'warning',
    title,
    text: message,
    showCancelButton: true,
    confirmButtonText: 'Continue',
    cancelButtonText: 'Cancel',
  })
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
  })[char])
}

const swalInputClass =
  'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20'
const swalSelectClass =
  'block w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2.5 pr-9 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20'
const swalLabelClass = 'mb-1.5 block text-sm font-semibold text-slate-700'
const swalCheckboxClass =
  'h-4 w-4 shrink-0 rounded border-slate-300 text-indigo-600 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/30'
const swalFormClass = { ...swalDefaults.customClass, popup: 'rounded-2xl text-left' }

function swalCheckboxField(id, label, checked = false) {
  return `
    <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700" for="${id}">
      <input ${checked ? 'checked' : ''} class="${swalCheckboxClass}" id="${id}" type="checkbox" />
      ${escapeHtml(label)}
    </label>
  `
}

// Opens the "Add User" form inside a SweetAlert2 modal, matching the app's
// Input/Select/Checkbox styling. Resolves with the submitted field values,
// or undefined if the user cancelled.
export async function promptCreateUser({ roles = [], departments = [] } = {}) {
  const roleOptions = roles.map((role) => `<option value="${role.id}">${escapeHtml(role.name)}</option>`).join('')
  const departmentOptions = departments
    .map((department) => `<option value="${department.id}">${escapeHtml(department.name)}</option>`)
    .join('')

  const { value: formValues } = await Swal.fire({
    ...swalDefaults,
    title: 'Add User',
    width: 640,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Create User',
    cancelButtonText: 'Cancel',
    customClass: swalFormClass,
    html: `
      <div class="grid grid-cols-1 gap-4 pt-2 text-left sm:grid-cols-2">
        <div>
          <label class="${swalLabelClass}" for="swal-name">Name</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-name" type="text" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-email">Email</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-email" type="email" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-password">Password</label>
          <input autocomplete="new-password" class="${swalInputClass}" id="swal-password" type="password" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-password-confirmation">Confirm password</label>
          <input autocomplete="new-password" class="${swalInputClass}" id="swal-password-confirmation" type="password" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-role">Role</label>
          <div class="relative">
            <select class="${swalSelectClass}" id="swal-role">
              <option value="">Select role</option>
              ${roleOptions}
            </select>
          </div>
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-department">Primary department</label>
          <div class="relative">
            <select class="${swalSelectClass}" id="swal-department">
              <option value="">None</option>
              ${departmentOptions}
            </select>
          </div>
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-phone">Phone</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-phone" type="text" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-company">Company</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-company" type="text" />
        </div>
        <div class="sm:col-span-2">
          ${swalCheckboxField('swal-active', 'Active', true)}
        </div>
      </div>
    `,
    preConfirm: () => {
      const name = document.getElementById('swal-name').value.trim()
      const email = document.getElementById('swal-email').value.trim()
      const password = document.getElementById('swal-password').value
      const passwordConfirmation = document.getElementById('swal-password-confirmation').value
      const roleId = document.getElementById('swal-role').value
      const departmentId = document.getElementById('swal-department').value
      const phone = document.getElementById('swal-phone').value.trim()
      const company = document.getElementById('swal-company').value.trim()
      const isActive = document.getElementById('swal-active').checked

      if (!name || !email || !password || !passwordConfirmation || !roleId) {
        Swal.showValidationMessage('Please fill in name, email, password, and role.')
        return false
      }

      if (password !== passwordConfirmation) {
        Swal.showValidationMessage('Passwords do not match.')
        return false
      }

      return {
        name,
        email,
        password,
        password_confirmation: passwordConfirmation,
        role_id: roleId,
        phone,
        company,
        primary_department_id: departmentId,
        is_active: isActive,
      }
    },
  })

  return formValues
}

// Add/Edit Department. Pass an existing department to edit it in place;
// omit it (or pass null) to create a new one.
export async function promptDepartmentForm(department = null) {
  const isEdit = Boolean(department)

  const { value: formValues } = await Swal.fire({
    ...swalDefaults,
    title: isEdit ? 'Edit Department' : 'Add Department',
    width: 560,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: isEdit ? 'Save Changes' : 'Create Department',
    cancelButtonText: 'Cancel',
    customClass: swalFormClass,
    html: `
      <div class="grid grid-cols-1 gap-4 pt-2 text-left sm:grid-cols-2">
        <div>
          <label class="${swalLabelClass}" for="swal-name">Name</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-name" type="text" value="${escapeHtml(department?.name)}" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-slug">Slug</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-slug" type="text" value="${escapeHtml(department?.slug)}" />
        </div>
        <div class="sm:col-span-2">
          <label class="${swalLabelClass}" for="swal-description">Description</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-description" type="text" value="${escapeHtml(department?.description)}" />
        </div>
        <div class="sm:col-span-2">
          ${swalCheckboxField('swal-active', 'Active', isEdit ? Boolean(department.is_active) : true)}
        </div>
      </div>
    `,
    preConfirm: () => {
      const name = document.getElementById('swal-name').value.trim()
      const slug = document.getElementById('swal-slug').value.trim()
      const description = document.getElementById('swal-description').value.trim()
      const isActive = document.getElementById('swal-active').checked

      if (!name || !slug) {
        Swal.showValidationMessage('Please fill in name and slug.')
        return false
      }

      return { name, slug, description, is_active: isActive }
    },
  })

  return formValues
}

// Add/Edit Category. Pass { category } to edit it in place; omit it to
// create a new one. `departments` populates the department select.
export async function promptCategoryForm({ category = null, departments = [] } = {}) {
  const isEdit = Boolean(category)
  const departmentOptions = departments
    .map((department) => `<option value="${department.id}">${escapeHtml(department.name)}</option>`)
    .join('')

  const { value: formValues } = await Swal.fire({
    ...swalDefaults,
    title: isEdit ? 'Edit Category' : 'Add Category',
    width: 560,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: isEdit ? 'Save Changes' : 'Create Category',
    cancelButtonText: 'Cancel',
    customClass: swalFormClass,
    html: `
      <div class="grid grid-cols-1 gap-4 pt-2 text-left sm:grid-cols-2">
        <div>
          <label class="${swalLabelClass}" for="swal-department">Department</label>
          <div class="relative">
            <select class="${swalSelectClass}" id="swal-department">
              <option value="">None</option>
              ${departmentOptions}
            </select>
          </div>
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-name">Name</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-name" type="text" value="${escapeHtml(category?.name)}" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-slug">Slug</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-slug" type="text" value="${escapeHtml(category?.slug)}" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-description">Description</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-description" type="text" value="${escapeHtml(category?.description)}" />
        </div>
        <div class="sm:col-span-2">
          ${swalCheckboxField('swal-active', 'Active', isEdit ? Boolean(category.is_active) : true)}
        </div>
      </div>
    `,
    didOpen: () => {
      if (category?.department_id) {
        document.getElementById('swal-department').value = String(category.department_id)
      }
    },
    preConfirm: () => {
      const departmentId = document.getElementById('swal-department').value
      const name = document.getElementById('swal-name').value.trim()
      const slug = document.getElementById('swal-slug').value.trim()
      const description = document.getElementById('swal-description').value.trim()
      const isActive = document.getElementById('swal-active').checked

      if (!name || !slug) {
        Swal.showValidationMessage('Please fill in name and slug.')
        return false
      }

      return { department_id: departmentId, name, slug, description, is_active: isActive }
    },
  })

  return formValues
}

// Edit Priority (no create — priorities are a fixed, seeded set).
export async function promptPriorityForm(priority) {
  const { value: formValues } = await Swal.fire({
    ...swalDefaults,
    title: `Edit Priority — ${escapeHtml(priority.name)}`,
    width: 480,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Save Changes',
    cancelButtonText: 'Cancel',
    customClass: swalFormClass,
    html: `
      <div class="grid grid-cols-1 gap-4 pt-2 text-left sm:grid-cols-2">
        <div>
          <label class="${swalLabelClass}" for="swal-name">Name</label>
          <input autocomplete="off" class="${swalInputClass}" id="swal-name" type="text" value="${escapeHtml(priority.name)}" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-sort-order">Sort order</label>
          <input class="${swalInputClass}" id="swal-sort-order" min="0" type="number" value="${Number(priority.sort_order) || 0}" />
        </div>
        <div class="sm:col-span-2">
          ${swalCheckboxField('swal-active', 'Active', Boolean(priority.is_active))}
        </div>
      </div>
    `,
    preConfirm: () => {
      const name = document.getElementById('swal-name').value.trim()
      const sortOrder = document.getElementById('swal-sort-order').value
      const isActive = document.getElementById('swal-active').checked

      if (!name) {
        Swal.showValidationMessage('Please enter a name.')
        return false
      }

      return { name, sort_order: Number(sortOrder), is_active: isActive }
    },
  })

  return formValues
}

// Edit an SLA rule (no create — rules map 1:1 to the fixed priority set).
export async function promptSlaForm(rule) {
  const { value: formValues } = await Swal.fire({
    ...swalDefaults,
    title: `Edit SLA — ${escapeHtml(rule.priority?.name ?? '')}`,
    width: 520,
    focusConfirm: false,
    showCancelButton: true,
    confirmButtonText: 'Save Changes',
    cancelButtonText: 'Cancel',
    customClass: swalFormClass,
    html: `
      <div class="grid grid-cols-1 gap-4 pt-2 text-left sm:grid-cols-2">
        <div>
          <label class="${swalLabelClass}" for="swal-first-response">First response (minutes)</label>
          <input class="${swalInputClass}" id="swal-first-response" min="1" type="number" value="${Number(rule.first_response_minutes) || ''}" />
        </div>
        <div>
          <label class="${swalLabelClass}" for="swal-resolution">Resolution (minutes)</label>
          <input class="${swalInputClass}" id="swal-resolution" min="1" type="number" value="${Number(rule.resolution_minutes) || ''}" />
        </div>
        <div class="space-y-3 sm:col-span-2">
          ${swalCheckboxField('swal-pause', 'Pause while waiting on customer', Boolean(rule.pause_on_waiting_customer))}
          ${swalCheckboxField('swal-business-hours', 'Use business hours', Boolean(rule.use_business_hours))}
          ${swalCheckboxField('swal-active', 'Active', Boolean(rule.is_active))}
        </div>
      </div>
    `,
    preConfirm: () => {
      const firstResponse = document.getElementById('swal-first-response').value
      const resolution = document.getElementById('swal-resolution').value
      const pause = document.getElementById('swal-pause').checked
      const businessHours = document.getElementById('swal-business-hours').checked
      const isActive = document.getElementById('swal-active').checked

      if (!firstResponse || !resolution || Number(firstResponse) < 1 || Number(resolution) < 1) {
        Swal.showValidationMessage('Please enter valid response and resolution times.')
        return false
      }

      return {
        first_response_minutes: Number(firstResponse),
        resolution_minutes: Number(resolution),
        pause_on_waiting_customer: pause,
        use_business_hours: businessHours,
        is_active: isActive,
      }
    },
  })

  return formValues
}
