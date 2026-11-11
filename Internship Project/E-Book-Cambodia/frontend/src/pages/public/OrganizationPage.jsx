import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { getOrganization, getOrganizationMember } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const memberPhoto = (member) => member?.photo_url || null

function Avatar({ member, size = 'w-20 h-20', textSize = 'text-2xl' }) {
  const photo = memberPhoto(member)
  if (photo) {
    return <img src={photo} alt={member?.name || ''} className={`${size} rounded-full object-cover ring-2 ring-white shadow`} />
  }
  return (
    <span className={`${size} rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center ${textSize} font-bold ring-2 ring-white shadow`}>
      {(member?.name || '?').charAt(0).toUpperCase()}
    </span>
  )
}

export default function OrganizationPage() {
  const { t } = useLanguage()
  const [selectedId, setSelectedId] = useState(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['organization'],
    refetchInterval: 60000,
    queryFn: getOrganization,
  })

  const departments = data?.data?.departments || []
  const hasMembers = departments.some((d) => d.members?.length > 0)

  return (
    <div className="bg-gray-50">
      {/* Hero */}
      <section className="bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
          <p className="text-indigo-200 text-sm font-semibold uppercase tracking-widest mb-3">Pi Education Cambodia</p>
          <h1 className="text-3xl sm:text-4xl font-bold mb-4">{t('orgStructure')}</h1>
          <p className="text-indigo-100 max-w-2xl mx-auto">{t('orgStructureSub')}</p>
        </div>
      </section>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {isLoading ? (
          <div className="p-12 text-center text-gray-500">{t('loading')}</div>
        ) : isError ? (
          <div className="bg-red-50 text-red-600 rounded-2xl p-6 text-center">{t('error') || 'Error'}</div>
        ) : !hasMembers ? (
          <div className="bg-white rounded-2xl shadow-sm p-12 text-center text-gray-500">{t('orgNotSet')}</div>
        ) : (
          <div className="space-y-10">
            {departments.map((department) =>
              department.members?.length > 0 ? (
                <section key={department.id}>
                  <div className="flex items-center gap-3 mb-4">
                    <span className="w-1.5 h-8 rounded-full bg-indigo-600" aria-hidden="true" />
                    <div>
                      <h2 className="text-xl font-bold text-gray-900">{department.name}</h2>
                      {department.description && <p className="text-sm text-gray-500">{department.description}</p>}
                    </div>
                  </div>
                  <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {department.members.map((member) => (
                      <button
                        key={member.id}
                        type="button"
                        onClick={() => setSelectedId(member.id)}
                        className="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center transition-all duration-200 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      >
                        <div className="flex flex-col items-center">
                          <Avatar member={member} />
                          <h3 className="mt-3 font-semibold text-gray-900 group-hover:text-indigo-700 transition-colors">{member.name}</h3>
                          <p className="text-xs text-indigo-600 font-medium mt-1 leading-snug">{member.position?.title}</p>
                          {member.is_department_head && (
                            <span className="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-semibold uppercase tracking-wide">
                              ★ {t('orgDepartmentHead')}
                            </span>
                          )}
                          {member.responsibilities?.length > 0 && (
                            <ul className="mt-3 text-[11px] text-gray-500 text-left list-disc list-inside space-y-0.5 w-full">
                              {member.responsibilities.slice(0, 2).map((r) => (
                                <li key={r.id} className="truncate">{r.responsibility}</li>
                              ))}
                            </ul>
                          )}
                        </div>
                      </button>
                    ))}
                  </div>
                </section>
              ) : null,
            )}
          </div>
        )}
      </div>

      {selectedId && <MemberModal memberId={selectedId} onClose={() => setSelectedId(null)} />}
    </div>
  )
}

function MemberModal({ memberId, onClose }) {
  const { t } = useLanguage()
  const { data, isLoading, isError } = useQuery({
    queryKey: ['organization-member', memberId],
    refetchInterval: 60000,
    queryFn: () => getOrganizationMember(memberId),
  })
  const member = data?.data?.member

  useEffect(() => {
    const onKey = (e) => { if (e.key === 'Escape') onClose() }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose} role="dialog" aria-modal="true">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between gap-3 mb-4">
          <h2 className="text-lg font-bold text-gray-900">{t('orgMemberDetails')}</h2>
          <button
            type="button"
            onClick={onClose}
            className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
            aria-label={t('close')}
          >
            ✕
          </button>
        </div>

        {isLoading ? (
          <div className="py-10 text-center text-gray-500">{t('loading')}</div>
        ) : isError || !member ? (
          <div className="py-10 text-center text-red-500">{t('error') || 'Error'}</div>
        ) : (
          <div>
            <div className="flex items-center gap-4">
              <Avatar member={member} size="w-20 h-20" textSize="text-2xl" />
              <div className="min-w-0">
                <h3 className="text-xl font-bold text-gray-900">{member.name}</h3>
                <p className="text-sm text-indigo-600 font-medium">{member.position?.title || t('orgNotSet')}</p>
                <p className="text-xs text-gray-500 mt-0.5">{member.department?.name}</p>
                {member.is_department_head && (
                  <span className="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-semibold uppercase tracking-wide">
                    ★ {t('orgDepartmentHead')}
                  </span>
                )}
              </div>
            </div>

            {member.bio && <p className="mt-4 text-sm text-gray-600 whitespace-pre-line">{member.bio}</p>}

            <dl className="mt-4 grid sm:grid-cols-2 gap-3 text-sm">
              {member.reports_to && (
                <div className="bg-gray-50 rounded-xl p-3">
                  <dt className="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">{t('orgReportsTo')}</dt>
                  <dd className="text-gray-800 font-medium mt-0.5">{member.reports_to.name}</dd>
                </div>
              )}
              {member.email && (
                <div className="bg-gray-50 rounded-xl p-3">
                  <dt className="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">{t('email')}</dt>
                  <dd className="mt-0.5"><a href={`mailto:${member.email}`} className="text-indigo-600 hover:underline break-all">{member.email}</a></dd>
                </div>
              )}
              {member.phone && (
                <div className="bg-gray-50 rounded-xl p-3">
                  <dt className="text-[11px] uppercase tracking-wide text-gray-400 font-semibold">{t('phone')}</dt>
                  <dd className="mt-0.5"><a href={`tel:${member.phone}`} className="text-indigo-600 hover:underline">{member.phone}</a></dd>
                </div>
              )}
            </dl>

            {member.responsibilities?.length > 0 && (
              <div className="mt-4">
                <h4 className="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{t('orgResponsibilities')}</h4>
                <ul className="space-y-1.5 text-sm text-gray-700 list-disc list-inside">
                  {member.responsibilities.map((r) => (
                    <li key={r.id}>{r.responsibility}</li>
                  ))}
                </ul>
              </div>
            )}

            {member.partner?.length > 0 && (
              <div className="mt-4">
                <h4 className="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">{t('orgPartners')}</h4>
                <ul className="space-y-2">
                  {member.partner.map((p) => (
                    <li key={p.id} className="bg-indigo-50/60 rounded-xl p-3">
                      <p className="text-sm font-semibold text-gray-900">{p.organization_name}</p>
                      {p.role && <p className="text-xs text-indigo-700">{p.role}</p>}
                      {p.description && <p className="text-xs text-gray-600 mt-1">{p.description}</p>}
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
