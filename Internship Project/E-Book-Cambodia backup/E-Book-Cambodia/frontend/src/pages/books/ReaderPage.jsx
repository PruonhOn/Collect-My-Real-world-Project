import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useParams, useNavigate } from 'react-router-dom'
import { readBook, getBookFile, saveProgress } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { useAuth } from '../../context/AuthContext'
import { useEffect, useState } from 'react'
import BookReader from '../../components/BookReader'
import { splitContentIntoPages } from '../../utils/pages'

export default function ReaderPage() {
  const { id } = useParams()
  const { t } = useLanguage()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { isAuthenticated, user } = useAuth()
  const [pdfPageCount, setPdfPageCount] = useState(0)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['read', id],
    queryFn: () => readBook(id),
    retry: 1,
  })

  const res = data?.data
  const book = res?.book
  const savedPage = res?.progress?.last_page || 1

  const isPdf =
    typeof book?.file_type === 'string' &&
    (book.file_type.toLowerCase().includes('pdf') ||
      (typeof book?.file_path === 'string' && book.file_path.toLowerCase().endsWith('.pdf')))

  const fileQuery = useQuery({
    queryKey: ['reader-file', id],
    queryFn: async () => {
      const resp = await getBookFile(id)
      const blob = resp.data
      const bytes = new Uint8Array(await blob.arrayBuffer())
      return { bytes, blobUrl: URL.createObjectURL(blob) }
    },
    enabled: isPdf && !!book?.file_path,
    retry: 1,
  })

  const content = book?.content || ''
  const pages = splitContentIntoPages(content)

  const totalPages = pdfPageCount || book?.pages || pages.length || 100

  const progressMutation = useMutation({
    mutationFn: ({ lastPage, total }) =>
      saveProgress(book.id, {
        last_page: lastPage,
        total_pages: total,
      }),
    onSuccess: () => queryClient.invalidateQueries(['read', id]),
  })

  useEffect(() => {
    if (book?.id && isAuthenticated && (pages.length > 0 || pdfPageCount > 0)) {
      progressMutation.mutate({ lastPage: 1, total: totalPages })
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [book?.id, isAuthenticated, pages.length, pdfPageCount])

  const handlePageChange = (pageNumber) => {
    if (book?.id && isAuthenticated) {
      progressMutation.mutate({
        lastPage: pageNumber,
        total: pdfPageCount || book?.pages || pages.length,
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

  if (isLoading) {
    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="animate-spin w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full" />
        {BackButton}
      </div>
    )
  }

  if (isError || !book) {
    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="text-center max-w-md p-8 bg-white rounded-2xl shadow-sm">
          <h1 className="text-xl font-bold text-gray-900 mb-2">{t('error')}</h1>
          <p className="text-gray-500 mb-4">You don't have access to this book or it doesn't exist.</p>
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
      </div>
    )
  }

  if (isPdf && fileQuery.isError) {
    return (
      <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
        <div className="text-center max-w-md p-8 bg-white rounded-2xl shadow-sm">
          <h1 className="text-xl font-bold text-gray-900 mb-2">{t('error')}</h1>
          <p className="text-gray-500 mb-4">Failed to load the book file.</p>
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
      <BookReader
        pdfData={fileQuery.data.bytes}
        title={book.title}
        readerName={user?.name}
        initialPage={savedPage}
        onLoad={setPdfPageCount}
        onPageChange={handlePageChange}
        onClose={() => navigate(-1)}
      />
    )
  }

  if (!isPdf && pages.length > 0) {
    return (
      <BookReader
        pages={pages}
        title={book.title}
        readerName={user?.name}
        initialPage={savedPage}
        onPageChange={handlePageChange}
        onClose={() => navigate(-1)}
      />
    )
  }

  if (!isPdf && content) {
    return (
      <div className="min-h-screen w-full bg-gray-950 relative">
        {BackButton}
        <div className="max-w-3xl mx-auto px-6 py-14">
          <h1 className="text-3xl font-serif font-bold text-gray-100 mb-4 text-center">
            {book.title}
          </h1>
          <div className="text-center text-gray-400 mb-8 text-sm">{book.author || ''}</div>
          <div className="text-gray-300 leading-relaxed whitespace-pre-wrap">{content}</div>
        </div>
      </div>
    )
  }

  return (
    <div className="h-screen w-screen bg-gray-950 flex items-center justify-center relative">
      <p className="text-gray-400">No readable content.</p>
      {BackButton}
    </div>
  )
}