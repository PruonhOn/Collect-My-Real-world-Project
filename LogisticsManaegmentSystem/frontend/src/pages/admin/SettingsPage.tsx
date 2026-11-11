import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { SlidersHorizontal } from 'lucide-react'
import { get, patch } from '@/lib/api'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { ThemePicker } from '@/components/ui/ThemePicker'
import { EmptyState } from '@/components/ui/States'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface AdminSetting {
  key: string
  value: string | number | boolean | null
  type: string
  group: string
  description: string | null
  updated_at: string
  created_at: string
}

export function SettingsPage() {
  const [editingKey, setEditingKey] = useState<string | null>(null)
  const [editValue, setEditValue] = useState('')

  const { data, isPending } = useQuery<AdminSetting[]>({
    queryKey: ['admin', 'settings'],
    queryFn: () => get<AdminSetting[]>('/admin/settings'),
  })

  const handleSave = async (setting: AdminSetting) => {
    const value = editValue === '' ? null : editValue
    try {
      await patch(`/admin/settings/${setting.key}`, { value, type: setting.type })
      setEditingKey(null)
      setEditValue('')
      window.location.reload()
    } catch {
      // Error handled by API interceptor (400/422); show nothing extra.
    }
  }

  // Settings arrive ordered by group and key from the API. Grouping them here
  // keeps one card per group, so a setting about mail never sits under
  // "general" because the seed happened to order it that way.
  const groups = useMemo(() => {
    const grouped = new Map<string, AdminSetting[]>()

    for (const setting of data ?? []) {
      const bucket = grouped.get(setting.group)
      if (bucket) {
        bucket.push(setting)
      } else {
        grouped.set(setting.group, [setting])
      }
    }

    return [...grouped.entries()]
  }, [data])

  return (
    <RequirePermission permission="setting.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">System Settings</h1>
            <p className="text-xs text-ink-500">Keyed values an administrator can read and change.</p>
          </div>
        </div>

        {/* Appearance first: it is the setting every visitor will want, and it
            works even while the keyed table below is still empty. */}
        <Card>
          <CardHeader
            title="Appearance"
            description="Choose the theme template. Your choice is remembered in this browser."
          />
          <CardBody>
            <ThemePicker />
          </CardBody>
        </Card>

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <div className="animate-pulse rounded-full h-12 w-12 bg-ink-200" />
          </div>
        ) : (data ?? []).length === 0 ? (
          <EmptyState title="No settings" />
        ) : (
          groups.map(([group, settings]) => (
            <Card key={group}>
              <CardHeader
                title={GROUP_TITLES[group] ?? group}
                description={GROUP_BLURBS[group] ?? 'Change a value and press Save.'}
              />
              <CardBody className="p-0">
                <Table>
                  <THead>
                    <TR>
                      <TH>Key</TH>
                      <TH>Type</TH>
                      <TH align="right">Value</TH>
                      <TH>Description</TH>
                      <TH align="right" className="min-w-[90px]">Action</TH>
                    </TR>
                  </THead>
                  <TBody>
                    {settings.map((setting: AdminSetting) => (
                      <TR key={setting.key}>
                        <TD className="font-medium text-ink-900 truncate max-w-xs">
                          {setting.key}
                        </TD>
                        <TD className="tabular text-xs text-ink-500">
                          <Badge tone="neutral">{setting.type}</Badge>
                        </TD>
                        <TD align="right">
                          {editingKey === setting.key ? (
                            <Input
                              value={editValue}
                              onChange={(e) => setEditValue(e.target.value)}
                              className="w-40 text-sm"
                              autoFocus
                            />
                          ) : (
                            <span className="tabular text-sm text-ink-700 truncate max-w-xs">
                              {String(setting.value ?? '—')}
                            </span>
                          )}
                        </TD>
                        <TD className="text-sm text-ink-500 truncate max-w-xs">
                          {setting.description ?? '—'}
                        </TD>
                        <TD align="right">
                          {editingKey === setting.key ? (
                            <>
                              <Button variant="outline" size="sm" onClick={() => handleSave(setting)}>
                                Save
                              </Button>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => {
                                  setEditingKey(null)
                                  setEditValue('')
                                }}
                                className="ml-1 h-8 w-8 p-0"
                              >
                                <SlidersHorizontal className="size-4" aria-hidden />
                              </Button>
                            </>
                          ) : (
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => {
                                setEditingKey(setting.key)
                                setEditValue(String(setting.value ?? ''))
                              }}
                            >
                              Edit
                            </Button>
                          )}
                        </TD>
                      </TR>
                    ))}
                  </TBody>
                </Table>
              </CardBody>
            </Card>
          ))
        )}
      </div>
    </RequirePermission>
  )
}

/** Display names for seeded groups, so a card heading reads as English. */
const GROUP_TITLES: Record<string, string> = {
  general: 'General',
  appearance: 'Appearance',
  notifications: 'Notifications',
  mail: 'Mail',
}

const GROUP_BLURBS: Record<string, string> = {
  general: 'Identity and locale for the whole installation.',
  appearance: 'Theme template and interface density.',
  notifications: 'How outbound alerts are delivered.',
  mail: 'Address and queue settings used when sending email.',
}
