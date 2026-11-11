import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { toast } from 'sonner'
import { Copy, Trash2, UserPlus, UsersRound } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage, apiFieldErrors, isUpgradeRequired } from '../../api/client'
import { formatDateTime } from '../../utils/format'
import { MEMBER_ROLES, STATUS_TONES } from '../../utils/constants'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock } from '../../components/ui/Feedback'
import { StatusBadge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { FormRow, Input, Select } from '../../components/ui/Form'
import { ConfirmDialog, Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const schema = z.object({
  email: z.string().min(1, 'Email is required.').email('Enter a valid email address.'),
  role: z.enum(['admin', 'staff']),
})

export default function Team() {
  const [inviting, setInviting] = useState(false)
  const [saving, setSaving] = useState(false)
  const [formError, setFormError] = useState(null)
  const [upgradeRequired, setUpgradeRequired] = useState(false)
  const [revoking, setRevoking] = useState(null)

  const loader = useCallback(() => businessService.team(), [])
  const { data, loading, error, reload } = useApi(loader, [loader])

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm({
    resolver: zodResolver(schema),
    defaultValues: { email: '', role: 'staff' },
  })

  const openInvite = () => {
    setFormError(null)
    reset({ email: '', role: 'staff' })
    setInviting(true)
  }

  const onSubmit = async (values) => {
    setSaving(true)
    setFormError(null)
    setUpgradeRequired(false)

    try {
      await businessService.inviteMember(values)
      toast.success('Invitation sent.')
      setInviting(false)
      reload()
    } catch (exception) {
      const fieldErrors = apiFieldErrors(exception)

      Object.entries(fieldErrors).forEach(([field, messages]) => {
        setError(field, { message: messages[0] })
      })

      if (isUpgradeRequired(exception)) {
        setUpgradeRequired(true)
      }

      setFormError(apiErrorMessage(exception, 'Unable to send the invitation.'))
    } finally {
      setSaving(false)
    }
  }

  const changeRole = async (member, role) => {
    try {
      await businessService.updateMember(member.id, { role })
      toast.success('Role updated.')
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to update the member.'))
    }
  }

  const confirmRevoke = async () => {
    if (!revoking) {
      return
    }

    try {
      await businessService.removeMember(revoking.id)
      toast.success('Access revoked.')
      setRevoking(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to revoke the member.'))
    }
  }

  const members = data?.data ?? []
  const limits = data?.limits

  return (
    <div>
      <PageHeader
        title="Team"
        description="Invite colleagues, assign roles and revoke access."
        actions={
          <Button onClick={openInvite}>
            <UserPlus className="size-4" />
            Invite member
          </Button>
        }
      />

      {upgradeRequired && (
        <div className="mb-4">
          <Alert tone="warning" title="Member limit reached">
            This plan allows {limits?.max_members ?? 'a limited number of'} members. Upgrade to invite more people.
          </Alert>
        </div>
      )}

      {error && (
        <div className="mb-4">
          <Alert tone="danger">{apiErrorMessage(error)}</Alert>
        </div>
      )}

      <Card>
        <CardHeader
          title="Members"
          description={
            limits?.max_members
              ? `${limits.used} of ${limits.max_members} seats used`
              : `${limits?.used ?? members.length} members`
          }
        />

        {loading && <LoadingBlock />}

        {!loading && members.length === 0 && (
          <EmptyState icon={UsersRound} title="No members yet" description="Invite your first team member." />
        )}

        {!loading && members.length > 0 && (
          <Table>
            <thead className="bg-slate-50">
              <tr>
                <Th>Member</Th>
                <Th>Role</Th>
                <Th>Status</Th>
                <Th>Joined</Th>
                <Th className="text-right">Actions</Th>
              </tr>
            </thead>
            <tbody>
              {members.map((member) => (
                <Tr key={member.id}>
                  <Td>
                    <p className="font-medium text-slate-900">{member.user?.name ?? member.email}</p>
                    <p className="text-xs text-slate-500">{member.user?.email ?? member.email}</p>
                  </Td>
                  <Td>
                    {member.role === 'owner' ? (
                      <span className="text-sm font-medium text-slate-700">Owner</span>
                    ) : (
                      <Select
                        className="w-28"
                        value={member.role}
                        onChange={(event) => changeRole(member, event.target.value)}
                      >
                        {MEMBER_ROLES.map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </Select>
                    )}
                  </Td>
                  <Td>
                    <StatusBadge status={member.status} tone={STATUS_TONES[member.status]} />
                  </Td>
                  <Td>{member.accepted_at ? formatDateTime(member.accepted_at) : '—'}</Td>
                  <Td className="text-right">
                    <div className="inline-flex items-center gap-1">
                      {member.invite_token && (
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                          onClick={() => {
                            navigator.clipboard?.writeText(
                              `${import.meta.env.VITE_FRONTEND_URL ?? window.location.origin}/invitations/${member.invite_token}`,
                            )
                            toast.success('Invitation link copied.')
                          }}
                          aria-label="Copy invitation link"
                        >
                          <Copy className="size-4" />
                        </button>
                      )}
                      {member.role !== 'owner' && (
                        <button
                          type="button"
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                          onClick={() => setRevoking(member)}
                          aria-label="Revoke access"
                        >
                          <Trash2 className="size-4" />
                        </button>
                      )}
                    </div>
                  </Td>
                </Tr>
              ))}
            </tbody>
          </Table>
        )}
      </Card>

      <Modal
        open={inviting}
        onClose={() => setInviting(false)}
        title="Invite a team member"
        description="The invitation link is emailed to the address you provide."
        footer={
          <>
            <Button variant="secondary" onClick={() => setInviting(false)} disabled={saving}>
              Cancel
            </Button>
            <Button onClick={handleSubmit(onSubmit)} loading={saving}>
              Send invitation
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
          <FormRow label="Email address" htmlFor="invite-email" error={errors.email?.message}>
            <Input id="invite-email" type="email" invalid={Boolean(errors.email)} {...register('email')} />
          </FormRow>

          <FormRow label="Role" htmlFor="invite-role" error={errors.role?.message}>
            <Select id="invite-role" {...register('role')}>
              {MEMBER_ROLES.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </Select>
          </FormRow>

          <CardBody className="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Admins manage the workspace and the team. Staff can work with the operational data they are given.
          </CardBody>
        </form>
      </Modal>

      <ConfirmDialog
        open={Boolean(revoking)}
        onClose={() => setRevoking(null)}
        onConfirm={confirmRevoke}
        title="Revoke access"
        description="The member loses access to this business immediately."
        confirmLabel="Revoke"
      />
    </div>
  )
}
