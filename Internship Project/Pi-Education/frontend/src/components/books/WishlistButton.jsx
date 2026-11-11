import { useMutation, useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router-dom'
import { useState } from 'react'
import api from '../../api/client'
import { useAuth } from '../../context/AuthContext'
import { useI18n } from '../../i18n'

export default function WishlistButton({ bookId, active = false, size = 18 }) {
  const { t } = useI18n()
  const { isAuthenticated } = useAuth()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [isActive, setIsActive] = useState(Boolean(active))

  const toggle = useMutation({
    mutationFn: () =>
      isActive ? api.delete(`/wishlist/${bookId}`) : api.post('/wishlist', { book_id: bookId }),
    onSuccess: async () => {
      setIsActive((v) => !v)
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['books'] }),
        queryClient.invalidateQueries({ queryKey: ['book'] }),
        queryClient.invalidateQueries({ queryKey: ['wishlist'] }),
      ])
    },
  })

  return (
    <button
      type="button"
      aria-label={t('wishlist.title')}
      title={isActive ? t('wishlist.remove') : t('wishlist.add')}
      disabled={toggle.isPending}
      onClick={(e) => {
        e.preventDefault()
        e.stopPropagation()
        if (!isAuthenticated) {
          navigate('/login')
          return
        }
        toggle.mutate()
      }}
      className={`flex h-9 w-9 items-center justify-center rounded-full ring-1 transition ${
        isActive ? 'bg-rose-500 text-white ring-rose-500' : 'bg-white text-slate-500 ring-slate-200 hover:text-rose-500 hover:ring-rose-300'
      }`}
    >
      <svg width={size} height={size} viewBox="0 0 24 24" fill={isActive ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="2">
        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
      </svg>
    </button>
  )
}