import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useParams, useNavigate } from 'react-router-dom'
import { readBook, getBookFile as fetchBookFile, saveProgress, purchaseBook } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { useAuth } from '../../context/AuthContext'
import { useMembership } from '../../hooks/useMembership'
import CrownIcon from '../../components/membership/CrownIcon'
import BakongPaymentModal from '../../components/payment/BakongPaymentModal'
import { useCallback, useEffect, useState } from 'react'
import BookReader from '../../components/BookReader'
import ReflowBookReader from '../../components/reader/ReflowBookReader'
import { splitContentIntoPages } from '../../utils/pages'
import {
  getBookFile as cacheGetBookFile,
  saveBookFile as cacheSaveBookFile,
  getPage as cacheGetPage,
  savePage as cacheSavePage,
} from '../../utils/bookCache'

export default function ReaderPage() {
  const { id } = useParams()
  const { t, locale } = useLanguage()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAuthenticated, user } = useAuth()
  const { isVip } = useMembership()
  const [pdfPageCount, setPdfPageCount] = useState(0)
  const [tocNotice, setTocNotice] = useState('')
  const [purchaseResult, setPurchaseResult] = useState(null)
  const [purchaseError, setPurchaseError] = useState('')

  const handleLockedTocNavigate = () => {
    setTocNotice(t('toc.lockedNotice'))
    window.clearTimeout(handleLockedTocNavigate._timer)
    handleLockedTocNavigate._timer = window.setTimeout(() => setTocNotice(''), 3200)
  }

  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['read', id],
    queryFn: () => readBook(id),
    retry: 1,
  })

  const res = data?.data
  const book = res?.book
  const savedPage = res?.progress?.last_page || 1
  const version = res?.access?.version
  const toc = res?.toc || []
  const previewMode = res?.access?.mode === 'preview'
  const previewGate = res?.access?.preview_mode || 'preview'

  const purchaseMutation = useMutation({
    mutationFn: () => purchaseBook(book.id, { method: 'bakong' }),
    onSuccess: (result) => {
      setPurchaseError('')
      setPurchaseResult(result.data)
    },
    onError: (error) => {
      setPurchaseError(error.response?.data?.message || t('purchaseFailed'))
    },
  })

  // "Buy to continue" opens the KHQR sheet right here in the reader, so the
  // reader stays on the page they were reading instead of going back to the
  // catalogue to start a second purchase.
  const handleUnlock = () => {
    if (!book) return

    if (book.is_vip_only) {
      navigate(isAuthenticated ? '/vip' : '/login')
      return
    }

    if (!isAuthenticated) {
      navigate('/login')
      return
    }

    if (purchaseMutation.isPending) {
      return
    }

    setPurchaseError('')
    purchaseMutation.mutate()
  }

  const handleBakongPaid = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ['read', id] })
    setPurchaseResult((prev) => (prev ? { ...prev, activated: true, status: 'completed' } : prev))
  }, [queryClient, id])

  const paymentModal = purchaseResult ? (
    <BakongPaymentModal
      result={purchaseResult}
      onPaid={handleBakongPaid}
      onClosed={() => setPurchaseResult(null)}
      footer={
        <button
          type="button"
          onClick={() => setPurchaseResult(null)}
          className="flex-1 px-4 py-2.5 bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 font-medium"
        >
          {t('readNow')}
        </button>
      }
    />
  ) : null

  const purchaseErrorNotice = purchaseError && (
    <div className="fixed top-16 left-1/2 -translate-x-1/2 z-[60] max-w-md px-4 py-2 rounded-full bg-red-600 text-white text-sm shadow-lg">
      {purchaseError}
    </div>
  )

  const isPdf =
    typeof book?.file_type === 'string' &&
    (book.file_type.toLowerCase().includes('pdf') ||
      (typeof book?.file_path === 'string' && book.file_path.toLowerCase().endsWith('.pdf')))

  const fileQuery = useQuery({
    queryKey: ['reader-file', id, version || 'legacy'],
    queryFn: async () => {
      if (version) {
        const cached = await cacheGetBookFile(Number(id), version)
        if (cached?.blob) {
          return {
            bytes: new Uint8Array(await cached.blob.arrayBuffer()),
            blobUrl: URL.createObjectURL(cached.blob),
          }
        }
      }
      const resp = await fetchBookFile(id)
      const blob = resp.data
      const bytes = new Uint8Array(await blob.arrayBuffer())
      if (version) {
        cacheSaveBookFile(Number(id), version, blob)
      }
      return { bytes, blobUrl: URL.createObjectURL(blob) }
    },
    enabled: isPdf && !!book?.file_path,
    retry: 1,
  })

  const content = book?.content || ''
  const pages = splitContentIntoPages(content)

  const pageCache = version
    ? {
        enabled: true,
        bookId: Number(id),
        version,
        getPage: cacheGetPage,
        savePage: cacheSavePage,
      }
    : null

  const totalPages = pdfPageCount || book?.pages || pages.length || 100

  const progressMutation = useMutation({
    mutationFn: ({ lastPage, total }) =>
      saveProgress(book.id, {
        last_page: lastPage,
        total_pages: total,
      }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['read', id] }),
  })

  useEffect(() => {
    if (book?.id && isAuthenticated && savedPage > 0) {
      progressMutation.mutate({
        lastPage: savedPage,
        total: res?.progress?.total_pages || totalPages,
      })
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [book?.id, isAuthenticated])

  const handlePageChange = (pageNumber, totalOverride) => {
    if (book?.id && isAuthenticated) {
      progressMutation.mutate({
        lastPage: pageNumber,
        total: totalOverride || pdfPageCount || book?.pages || pages.length,
      })
    }
  }

  const BackButton = (
    <button
      onClick={() => navigate(-1)}
      className="absolute top-4 left-4 z-50 flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-black/60 hover:bg-black/80 rounded-full backdrop-blur"
      aria-label="Back"
    >
      <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
      </svg>
      {t('back')}
    </button>
  )

  // A single gold marker so VIP members feel the premium tier while reading —
  // no popups, no ads, nothing that interrupts the page.
  const VipChip = isVip && (
    <span className="absolute top-4 right-4 z-50 inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold text-white bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] rounded-full shadow-[0_0_20px_rgba(212,175,55,0.4)] border border-amber-400/60 backdrop-blur-md transition-all duration-200 hover:shadow-[0_0_25px_rgba(212,175,55,0.6)]">
      <CrownIcon className="w-3.5 h-3.5" />
      {t('vip')}
    </span>
  )

  const VipToolbarBadge = isVip ? (
    <span className="bkr-meta-reader bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-[0_0_10px_rgba(212,175,55,0.3)]">
      <CrownIcon className="w-3 h-3" />
      {t('vip')}
    </span>
  ) : null

  const ReflowVipBadge = isVip ? (
    <span className="rdr-badge">
      <CrownIcon className="w-3 h-3" />
      {t('vip')}
    </span>
  ) : null

  if (isLoading) {
    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="animate-spin w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full" />
        {BackButton}
        {VipChip}
      </div>
    )
  }

  if (isError || !book) {
    // The backend flags VIP-only denials so we can offer an upgrade instead of
    // a dead end.
    if (error?.response?.data?.vip_required) {
      return (
        <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
          <div className="text-center max-w-md p-8 bg-white rounded-2xl shadow-sm">
            <div className="inline-block px-3 py-1 rounded-lg bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white text-xs font-bold shadow-[0_0_10px_rgba(212,175,55,0.3)]">
              VIP
            </div>
            <h1 className="text-xl font-bold text-gray-900 mt-3 mb-2">{t('vipOnlyNotice')}</h1>
            <div className="flex flex-col sm:flex-row gap-3 justify-center">
                <button
                  onClick={() => navigate(user ? '/vip' : '/login')}
                  className="px-4 py-2 bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white rounded-lg font-semibold shadow-[0_0_10px_rgba(212,175,55,0.3)] hover:shadow-[0_0_15px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:to-[#c49a30] transition-all duration-200"
                >
                  {t('becomeVip')}
                </button>
              <button
                onClick={() => navigate('/books')}
                className="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium"
              >
                {t('books')}
              </button>
            </div>
          </div>
          {BackButton}
        </div>
      )
    }

    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="text-center max-w-md p-8 bg-white rounded-2xl shadow-sm">
          <h1 className="text-xl font-bold text-gray-900 mb-2">{t('error')}</h1>
          <p className="text-gray-500 mb-4">{t('bookAccessDenied')}</p>
          <button
            onClick={() => navigate('/books')}
            className="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium"
          >
            {t('books')}
          </button>
        </div>
        {BackButton}
      </div>
    )
  }

  if (isPdf && fileQuery.isLoading) {
    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="animate-spin w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full" />
        {BackButton}
        {VipChip}
      </div>
    )
  }

  if (isPdf && fileQuery.isError) {
    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="text-center max-w-md p-8 bg-white rounded-2xl shadow-sm">
          <h1 className="text-xl font-bold text-gray-900 mb-2">{t('error')}</h1>
          <p className="text-gray-500 mb-4">{t('bookFileLoadFailed')}</p>
          <button
            onClick={() => navigate('/books')}
            className="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium"
          >
            {t('books')}
          </button>
        </div>
        {BackButton}
      </div>
    )
  }

  if (isPdf && fileQuery.isSuccess && fileQuery.data) {
    return (
      <>
        {tocNotice && (
          <div className="fixed top-16 left-1/2 -translate-x-1/2 z-[60] px-4 py-2 rounded-full bg-black/80 text-white text-sm shadow-lg">
            {tocNotice}
          </div>
        )}
        {purchaseErrorNotice}
        <BookReader
          pdfData={fileQuery.data.bytes}
          title={book.title}
          viewCount={book.view_count}
          viewCountLabel={t('views')}
          readerName={user?.name}
          initialPage={savedPage}
          onLoad={setPdfPageCount}
          onPageChange={handlePageChange}
          onClose={() => navigate(-1)}
          pageCache={pageCache}
          bookId={book.id}
          toolbarBadge={VipToolbarBadge}
          toc={toc}
          locale={locale}
          onLockedNavigate={handleLockedTocNavigate}
          previewMode={previewMode}
          previewGate={previewGate}
          vipRequired={!!book.is_vip_only}
          onUnlock={handleUnlock}
        />
        {paymentModal}
      </>
    )
  }

  if (!isPdf && content) {
    return (
      <>
        {purchaseErrorNotice}
        <ReflowBookReader
          content={content}
          title={book.title}
          author={book.author}
          viewCount={book.view_count}
          viewCountLabel={t('views')}
          readerName={user?.name}
          initialPage={savedPage}
          initialTotal={res?.progress?.total_pages || 0}
          sourcePageCount={book.pages || pages.length}
          previewPageCount={res?.access?.preview_pages}
          onPageChange={handlePageChange}
          onClose={() => navigate(-1)}
          bookId={book.id}
          badge={ReflowVipBadge}
          toc={toc}
          locale={locale}
          onLockedNavigate={handleLockedTocNavigate}
          previewMode={previewMode}
          previewGate={previewGate}
          vipRequired={!!book.is_vip_only}
          onUnlock={handleUnlock}
        />
        {paymentModal}
      </>
    )
  }

  return (
    <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
      <p className="text-gray-400">{t('noReadableContent')}</p>
      {BackButton}
    </div>
  )
}