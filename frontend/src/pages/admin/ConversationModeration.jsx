import { useEffect, useState } from 'react'
import { Filter, Pencil, PlusCircle, ShieldAlert, Trash2 } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { ActiveBadge, Badge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Card from '../../components/ui/Card'
import Checkbox from '../../components/ui/Checkbox'
import Field from '../../components/ui/Field'
import Input from '../../components/ui/Input'
import PageContainer from '../../components/ui/PageContainer'
import PageHeader from '../../components/ui/PageHeader'
import Pagination from '../../components/ui/Pagination'
import SearchInput from '../../components/ui/SearchInput'
import Select from '../../components/ui/Select'
import Sheet from '../../components/ui/Sheet'
import { Table, TableContainer, Tbody, Td, Th, Thead, Tr } from '../../components/ui/Table'
import { confirmAction, showError, showSuccess } from '../../utils/alerts'

const blankWord = { word: '', severity: 'medium', action: 'block', is_active: true, notes: '' }
const blankVariant = { variant: '', variant_type: 'manual', is_active: true }
const defaultSettings = {
  conversation_moderation_enabled: true,
  conversation_moderation_default_action: 'block',
  conversation_moderation_mask_character: '*',
  conversation_moderation_store_original: false,
  conversation_moderation_log_events: true,
  conversation_moderation_notify_manager_high: false,
  conversation_moderation_notify_manager_critical: true,
  conversation_moderation_block_prohibited: true,
  conversation_moderation_unicode_normalization: true,
  conversation_moderation_accent_folding: true,
  conversation_moderation_leetspeak_detection: true,
  conversation_moderation_invisible_stripping: true,
  conversation_moderation_separator_detection: true,
  conversation_moderation_repeat_normalization: true,
  conversation_moderation_compressed_variants: true,
  conversation_moderation_fuzzy_matching: true,
}

const actionLabels = { mask: 'Masked', block: 'Blocked', flag: 'Flagged', mask_flag: 'Masked + Flagged' }
const severityTones = { low: 'slate', medium: 'blue', high: 'amber', critical: 'red' }
const actionTones = { mask: 'blue', block: 'red', flag: 'amber', mask_flag: 'violet' }
const variantTypes = ['canonical', 'common_misspelling', 'compressed', 'morphological', 'manual']

function toSettings(raw) {
  return Object.entries(defaultSettings).reduce((carry, [key, fallback]) => ({
    ...carry,
    [key]: typeof fallback === 'boolean' ? raw[key] === '1' : raw[key] ?? fallback,
  }), {})
}

export default function ConversationModeration() {
  const [settings, setSettings] = useState(defaultSettings)
  const [filters, setFilters] = useState({ search: '', severity: '', action: '', is_active: '', per_page: 20 })
  const [words, setWords] = useState([])
  const [pagination, setPagination] = useState(null)
  const [sheetOpen, setSheetOpen] = useState(false)
  const [editingWord, setEditingWord] = useState(null)
  const [wordForm, setWordForm] = useState(blankWord)
  const [variants, setVariants] = useState([])
  const [variantForm, setVariantForm] = useState(blankVariant)
  const [saving, setSaving] = useState(false)

  async function load(params = filters) {
    try {
      const [settingData, wordData] = await Promise.all([adminService.getModerationSettings(), adminService.getBlockedWords(params)])
      setSettings(toSettings(settingData))
      setWords(wordData.blocked_words)
      setPagination(wordData.pagination)
    } catch (error) {
      await showError(error.message || 'Unable to load moderation configuration.')
    }
  }

  useEffect(() => { load() }, [])

  function updateSetting(event) {
    const { name, checked, type, value } = event.target
    setSettings((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
  }

  async function saveSettings(event) {
    event.preventDefault()
    const result = await confirmAction('Save conversation moderation settings?')
    if (!result.isConfirmed) return
    try {
      await adminService.updateModerationSettings(settings)
      await showSuccess('Moderation settings saved.')
    } catch (error) {
      await showError(error.message || 'Unable to save moderation settings.')
    }
  }

  function openCreate() {
    setEditingWord(null)
    setWordForm(blankWord)
    setSheetOpen(true)
  }

  async function openEdit(word) {
    setEditingWord(word)
    setWordForm({ word: word.word, severity: word.severity, action: word.action, is_active: Boolean(word.is_active), notes: word.notes ?? '' })
    setVariantForm(blankVariant)
    try {
      setVariants(await adminService.getBlockedWordVariants(word.id))
    } catch (error) {
      await showError(error.message || 'Unable to load variants.')
    }
    setSheetOpen(true)
  }

  function updateWord(event) {
    const { name, checked, type, value } = event.target
    setWordForm((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
  }

  async function saveWord(event) {
    event.preventDefault()
    try {
      setSaving(true)
      await adminService.saveBlockedWord(wordForm, editingWord?.id)
      setSheetOpen(false)
      await showSuccess(editingWord ? 'Blocked term updated.' : 'Blocked term created.')
      await load()
    } catch (error) {
      await showError(error.payload?.message || error.message || 'Unable to save blocked term.')
    } finally {
      setSaving(false)
    }
  }

  function updateVariant(event) {
    const { name, checked, type, value } = event.target
    setVariantForm((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }))
  }

  async function saveVariant(event) {
    event.preventDefault()
    if (!editingWord) return
    try {
      await adminService.saveBlockedWordVariant(editingWord.id, variantForm)
      setVariantForm(blankVariant)
      setVariants(await adminService.getBlockedWordVariants(editingWord.id))
      await showSuccess('Variant saved.')
    } catch (error) {
      await showError(error.message || 'Unable to save variant.')
    }
  }

  async function deleteVariant(variant) {
    if (!editingWord) return
    const result = await confirmAction('Delete this variant?')
    if (!result.isConfirmed) return
    try {
      await adminService.deleteBlockedWordVariant(editingWord.id, variant.id)
      setVariants(await adminService.getBlockedWordVariants(editingWord.id))
      await showSuccess('Variant deleted.')
    } catch (error) {
      await showError(error.message || 'Unable to delete variant.')
    }
  }

  async function toggle(word) {
    const result = await confirmAction(`${word.is_active ? 'Disable' : 'Enable'} this blocked term?`)
    if (!result.isConfirmed) return
    try {
      if (word.is_active) await adminService.disableBlockedWord(word.id)
      else await adminService.enableBlockedWord(word.id)
      await showSuccess('Blocked term status updated.')
      await load()
    } catch (error) {
      await showError(error.message || 'Unable to update blocked term.')
    }
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

  return (
    <PageContainer width="full">
      <PageHeader
        actions={<Button onClick={openCreate}><PlusCircle className="h-4 w-4" />Add Term</Button>}
        breadcrumbs={[{ label: 'Administration', to: '/admin' }, { label: 'Conversation Moderation' }]}
        eyebrow="Administration"
        title="Conversation Moderation"
      />

      <Card className="mb-6">
        <form className="space-y-5" onSubmit={saveSettings}>
          <div className="flex items-center gap-3">
            <ShieldAlert className="h-5 w-5 text-indigo-600" />
            <h2 className="text-base font-semibold text-slate-950">Moderation Settings</h2>
          </div>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            <Field label="Default action">
              <Select name="conversation_moderation_default_action" onChange={updateSetting} value={settings.conversation_moderation_default_action}>
                {Object.entries(actionLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
              </Select>
            </Field>
            <Field label="Mask character">
              <Input maxLength={1} name="conversation_moderation_mask_character" onChange={updateSetting} value={settings.conversation_moderation_mask_character} />
            </Field>
          </div>
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <Checkbox checked={settings.conversation_moderation_enabled} label="Enable profanity filter" name="conversation_moderation_enabled" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_log_events} label="Log moderation events" name="conversation_moderation_log_events" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_store_original} label="Store encrypted original" name="conversation_moderation_store_original" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_notify_manager_high} label="Notify manager on high" name="conversation_moderation_notify_manager_high" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_notify_manager_critical} label="Notify manager on critical" name="conversation_moderation_notify_manager_critical" onChange={updateSetting} />
          </div>
          <div className="grid grid-cols-1 gap-3 border-t border-slate-200 pt-4 md:grid-cols-2 xl:grid-cols-3">
            <Checkbox checked={settings.conversation_moderation_block_prohibited} label="Block prohibited language" name="conversation_moderation_block_prohibited" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_unicode_normalization} label="Unicode normalization" name="conversation_moderation_unicode_normalization" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_accent_folding} label="Accent folding" name="conversation_moderation_accent_folding" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_leetspeak_detection} label="Leetspeak detection" name="conversation_moderation_leetspeak_detection" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_invisible_stripping} label="Invisible character stripping" name="conversation_moderation_invisible_stripping" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_separator_detection} label="Separator-obfuscation detection" name="conversation_moderation_separator_detection" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_repeat_normalization} label="Repeated-character normalization" name="conversation_moderation_repeat_normalization" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_compressed_variants} label="Compressed variant detection" name="conversation_moderation_compressed_variants" onChange={updateSetting} />
            <Checkbox checked={settings.conversation_moderation_fuzzy_matching} label="Fuzzy matching" name="conversation_moderation_fuzzy_matching" onChange={updateSetting} />
          </div>
          <Button type="submit">Save Settings</Button>
        </form>
      </Card>

      <form className="mb-6 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-(--shadow-xs) lg:flex-row lg:items-center" onSubmit={submitFilters}>
        <SearchInput onChange={(event) => setFilters((current) => ({ ...current, search: event.target.value }))} placeholder="Search blocked terms" value={filters.search} />
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-3 lg:flex lg:shrink-0">
          <Select className="lg:w-40" onChange={(event) => setFilters((current) => ({ ...current, severity: event.target.value }))} value={filters.severity}>
            <option value="">All severities</option>
            {['low', 'medium', 'high', 'critical'].map((value) => <option key={value} value={value}>{value}</option>)}
          </Select>
          <Select className="lg:w-44" onChange={(event) => setFilters((current) => ({ ...current, action: event.target.value }))} value={filters.action}>
            <option value="">All actions</option>
            {Object.entries(actionLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
          </Select>
          <Select className="lg:w-40" onChange={(event) => setFilters((current) => ({ ...current, is_active: event.target.value }))} value={filters.is_active}>
            <option value="">Any status</option>
            <option value="1">Active</option>
            <option value="0">Disabled</option>
          </Select>
          <Button type="submit" variant="secondary"><Filter className="h-4 w-4" />Filter</Button>
        </div>
      </form>

      <TableContainer>
        <Table>
          <Thead><tr><Th>Term</Th><Th>Severity</Th><Th>Action</Th><Th>Status</Th><Th>Updated</Th><Th>Actions</Th></tr></Thead>
          <Tbody>
            {words.map((word) => (
              <Tr key={word.id}>
                <Td primary>{word.word}</Td>
                <Td><Badge tone={severityTones[word.severity]}>{word.severity}</Badge></Td>
                <Td><Badge tone={actionTones[word.action]}>{actionLabels[word.action]}</Badge></Td>
                <Td><ActiveBadge active={word.is_active} /></Td>
                <Td>{word.updated_at ? new Date(word.updated_at).toLocaleString() : '-'}</Td>
                <Td>
                  <div className="flex flex-wrap gap-2">
                    <Button onClick={() => openEdit(word)} size="sm" variant="secondary"><Pencil className="h-4 w-4" />Edit</Button>
                    <Button onClick={() => toggle(word)} size="sm" variant="secondary">{word.is_active ? 'Disable' : 'Enable'}</Button>
                  </div>
                </Td>
              </Tr>
            ))}
          </Tbody>
        </Table>
      </TableContainer>
      <Pagination onPageChange={goToPage} pagination={pagination} />

      <Sheet onOpenChange={setSheetOpen} open={sheetOpen} title={editingWord ? 'Edit Blocked Term' : 'Add Blocked Term'}>
        <form className="space-y-4" onSubmit={saveWord}>
          <Field label="Term"><Input name="word" onChange={updateWord} value={wordForm.word} /></Field>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Severity">
              <Select name="severity" onChange={updateWord} value={wordForm.severity}>{['low', 'medium', 'high', 'critical'].map((value) => <option key={value} value={value}>{value}</option>)}</Select>
            </Field>
            <Field label="Action">
              <Select name="action" onChange={updateWord} value={wordForm.action}>{Object.entries(actionLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</Select>
            </Field>
          </div>
          <Field label="Notes"><Input name="notes" onChange={updateWord} value={wordForm.notes} /></Field>
          <Checkbox checked={wordForm.is_active} label="Active" name="is_active" onChange={updateWord} />
          {editingWord ? (
            <div className="space-y-3 border-t border-slate-200 pt-4">
              <h3 className="text-sm font-semibold text-slate-950">Variants</h3>
              <div className="space-y-2">
                {variants.length === 0 ? <p className="text-sm text-slate-500">No variants configured.</p> : null}
                {variants.map((variant) => (
                  <div className="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2" key={variant.id}>
                    <div>
                      <p className="text-sm font-semibold text-slate-900">{variant.variant}</p>
                      <p className="text-xs text-slate-500">{variant.variant_type}</p>
                    </div>
                    <Button onClick={() => deleteVariant(variant)} size="sm" type="button" variant="ghost"><Trash2 className="h-4 w-4" /></Button>
                  </div>
                ))}
              </div>
              <div className="space-y-3 rounded-lg border border-slate-200 p-3">
                <Field label="Variant"><Input name="variant" onChange={updateVariant} value={variantForm.variant} /></Field>
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                  <Field label="Variant type">
                    <Select name="variant_type" onChange={updateVariant} value={variantForm.variant_type}>{variantTypes.map((value) => <option key={value} value={value}>{value.replaceAll('_', ' ')}</option>)}</Select>
                  </Field>
                  <Checkbox checked={variantForm.is_active} label="Active" name="is_active" onChange={updateVariant} />
                </div>
                <Button onClick={saveVariant} type="button" variant="secondary"><PlusCircle className="h-4 w-4" />Add Variant</Button>
              </div>
            </div>
          ) : null}
          <div className="flex justify-end gap-2"><Button onClick={() => setSheetOpen(false)} type="button" variant="secondary">Cancel</Button><Button loading={saving} type="submit">Save</Button></div>
        </form>
      </Sheet>
    </PageContainer>
  )
}
