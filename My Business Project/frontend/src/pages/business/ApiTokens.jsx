import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { KeyRound, Plus, Trash2 } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage } from '../../api/client'
import { formatDateTime } from '../../utils/format'
import { API_TOKEN_ABILITIES } from '../../utils/constants'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock } from '../../components/ui/Feedback'
import { Badge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Input } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'
import { FeatureGate } from '../../components/FeatureGate'

const schema = z.object({
  name: z.string().min(1, 'Give the token a name.').max(100),
  expires_in_days: z.coerce.number().int().min(1).max(365).optional(),
})

export default function ApiTokens() {
  const [creating, setCreating] = useState(false)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [issued, setIssued] = useState(null)
  const [revoking, setRevoking] = useState(null)
  const [abilities, setAbilities] = useState([API_TOKEN_ABILITIES[0]])

  const loader = useCallback(() => businessService.apiTokens(), [])
  const { data, loading, error, reload } = useApi(loader, [loader])

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { name: '', expires_in_days: 90 },
  })

  const openCreate = () => {
    setFormError(null)
    setAbilities([API_TOKEN_ABILITIES[0]])
    reset({ name: '', expires_in_days: 90 })
    setCreating(true)
  }

  const toggleAbility = (ability) => {
    setAbilities((current) =>
      current.includes(ability) ? current.filter((item) => item !== ability) : [...current, ability],
    )
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)

    try {
      const result = await businessService.createApiToken({
        name: values.name,
        abilities,
        expires_in_days: values.expires_in_days || undefined,
      })

      setIssued(result)
      setCreating(false)
      reload()
    } catch (exception) {
      setFormError(apiErrorMessage(exception, 'Unable to create the token.'))
    } finally {
      setSaving(false)
    }
  }

  const confirmRevoke = async () => {
    if (!revoking) {
      return
    }

    try {
      await businessService.revokeApiToken(revoking.id)
      toast.success('Token revoked.')
      setRevoking(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to revoke the token.'))
    }
  }

  const tokens = data?.data ?? []

  return (
    <div>
      <PageHeader
        title="API tokens"
        description="Machine credentials for the external API. Tokens are scoped to this business only."
        actions={
          <Button onClick={openCreate}>
            <Plus className="size-4" />
            Issue token
          </Button>
        }
      />

      <FeatureGate feature="api_access">
        <Card>
          {loading && <LoadingBlock />}

          {error && !loading && (
            <div className="p-5">
              <Alert tone="danger">{apiErrorMessage(error)}</Alert>
            </div>
          )}

          {!loading && !error && tokens.length === 0 && (
            <EmptyState
              icon={KeyRound}
              title="No API tokens yet"
              description="Issue a token to connect an external system to this business."
              action={<Button onClick={openCreate}>Issue token</Button>}
            />
          )}

          {!loading && !error && tokens.length > 0 && (
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Name</Th>
                  <Th>Abilities</Th>
                  <Th>Status</Th>
                  <Th>Last used</Th>
                  <Th>Created</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {tokens.map((token) => (
                  <Tr key={token.id}>
                    <Td className="font-medium text-slate-900">{token.name}</Td>
                    <Td>
                      <div className="flex flex-wrap gap-1">
                        {(token.abilities ?? []).map((ability) => (
                          <Badge key={ability} tone="neutral">
                            {ability}
                          </Badge>
                        ))}
                      </div>
                    </Td>
                    <Td>
                      <Badge tone={token.is_revoked ? 'danger' : 'success'}>
                        {token.is_revoked ? 'revoked' : 'active'}
                      </Badge>
                    </Td>
                    <Td>{token.last_used_at ? formatDateTime(token.last_used_at) : 'Never'}</Td>
                    <Td>{formatDateTime(token.created_at)}</Td>
                    <Td className="text-right">
                      {!token.is_revoked && (
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                          onClick={() => setRevoking(token)}
                          aria-label="Revoke token"
                        >
                          <Trash2 className="size-4" />
                        </button>
                      )}
                    </Td>
                  </Tr>
                ))}
              </tbody>
            </Table>
          )}
        </Card>
      </FeatureGate>

      <Modal
        open={creating}
        onClose={() => setCreating(false)}
        title="Issue an API token"
        description="Tokens inherit your access to this business and can never widen it."
        footer={
          <>
            <Button variant="secondary" onClick={() => setCreating(false)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              Issue token
            </Button>
          </>
        }
      >
        {formError && (
          <div className="mb-4">
            <Alert tone="danger">{formError}</Alert>
          </div>
        )}

        <form className="space-y-4" onSubmit={handleSubmit(onSubmit)} noValidate>
          <FormRow label="Token name" htmlFor="token-name" error={errors.name?.message}>
            <Input id="token-name" placeholder="Accounting integration" invalid={Boolean(errors.name)} {...register('name')} />
          </FormRow>

          <FormRow label="Expires in (days)" htmlFor="token-expiry" error={errors.expires_in_days?.message}>
            <Input id="token-expiry" type="number" min="1" max="365" {...register('expires_in_days')} />
          </FormRow>

          <div>
            <p className="mb-2 text-sm font-medium text-slate-700">Abilities</p>
            <div className="space-y-2">
              {(data?.available_abilities ?? API_TOKEN_ABILITIES).map((ability) => (
                <label key={ability} className="flex items-center gap-2 text-sm text-slate-700">
                  <input
                    type="checkbox"
                    className="size-4 rounded border-slate-300"
                    checked={abilities.includes(ability)}
                    onChange={() => toggleAbility(ability)}
                  />
                  <span className="font-mono text-xs">{ability}</span>
                </label>
              ))}
            </div>
          </div>
        </form>
      </Modal>

      <Modal
        open={Boolean(issued)}
        onClose={() => setIssued(null)}
        title="Token issued"
        description="Copy the token now. It is shown only once."
        footer={
          <Button onClick={() => setIssued(null)}>
            Done
          </Button>
        }
      >
        <div className="space-y-3">
          <p className="text-sm text-slate-600">{issued?.message}</p>
          <code className="block break-all rounded-lg bg-slate-900 p-3 font-mono text-xs text-emerald-300">
            {issued?.token}
          </code>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => {
              navigator.clipboard?.writeText(issued?.token ?? '')
              toast.success('Token copied.')
            }}
          >
            Copy token
          </Button>
        </div>
      </Modal>

      <ConfirmDialog
        open={Boolean(revoking)}
        onClose={() => setRevoking(null)}
        onConfirm={confirmRevoke}
        title="Revoke token"
        description="External clients using this token stop working immediately."
        confirmLabel="Revoke"
      />
    </div>
  )
}
