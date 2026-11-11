// IndexedDB cache for book content.
//
// This is a PERFORMANCE cache only - it never decides who may read a book.
// Access is always granted by the Laravel backend on each request; this module
// only remembers what an authorized user has already downloaded/rendered so
// re-reading the same book is instant.
//
// Layout:
//   DB            : pi-education-book-cache
//   store bookFiles : { key: `${bookId}:${version}`, bookId, version, blob, size, cachedAt }
//   store bookPages : { key: `${bookId}:${version}:${bucket}:${page}`, bookId, version, bucket, page, blob, cachedAt }
//
// The `version` comes from the backend (book content version) so that when a
// book file is replaced by an admin, the old pages/files are invalidated.

const DB_NAME = 'pi-education-book-cache'
const DB_VERSION = 1
const FILE_STORE = 'bookFiles'
const PAGE_STORE = 'bookPages'
const MAX_PAGES_PER_VERSION = 250

let dbPromise = null

function openDb() {
  if (!dbPromise) {
    dbPromise = new Promise((resolve, reject) => {
      if (typeof indexedDB === 'undefined') {
        reject(new Error('IndexedDB is not available in this browser.'))
        return
      }
      let request
      try {
        request = indexedDB.open(DB_NAME, DB_VERSION)
      } catch (err) {
        reject(err)
        return
      }
      request.onupgradeneeded = () => {
        const db = request.result
        if (!db.objectStoreNames.contains(FILE_STORE)) {
          db.createObjectStore(FILE_STORE, { keyPath: 'key' })
        }
        if (!db.objectStoreNames.contains(PAGE_STORE)) {
          db.createObjectStore(PAGE_STORE, { keyPath: 'key' })
        }
      }
      request.onsuccess = () => resolve(request.result)
      request.onerror = () => reject(request.error || new Error('Failed to open IndexedDB.'))
    })
  }
  return dbPromise
}

function transaction(storeName, mode, buildRequest) {
  return openDb().then(
    (db) =>
      new Promise((resolve, reject) => {
        let tx
        try {
          tx = db.transaction(storeName, mode)
        } catch (err) {
          reject(err)
          return
        }
        let result
        tx.oncomplete = () => resolve(result)
        tx.onerror = () => reject(tx.error || new Error('IndexedDB transaction failed.'))
        tx.onabort = () => reject(tx.error || new Error('IndexedDB transaction aborted.'))
        try {
          const req = buildRequest(tx.objectStore(storeName))
          if (req && typeof req.onsuccess === 'undefined') {
            // buildRequest returned a plain value (e.g. on readonly single ops)
            result = req
          } else if (req) {
            req.onsuccess = () => {
              result = req.result
            }
          }
        } catch (err) {
          reject(err)
        }
      })
  )
}

const fileKey = (bookId, version) => `${bookId}:${version}`
const pageKey = (bookId, version, bucket, page) => `${bookId}:${version}:${bucket}:${page}`
const matchBook = (record, bookId) => record && record.bookId === bookId

/**
 * Returns { blob, size, cachedAt } for a cached whole-book file, or null.
 */
export function getBookFile(bookId, version) {
  if (!bookId || !version) return Promise.resolve(null)
  return transaction(FILE_STORE, 'readonly', (store) => store.get(fileKey(bookId, version)))
    .then((record) => (record && record.blob ? record : null))
    .catch(() => null)
}

/**
 * Stores the whole-book blob, first invalidating any older version for the
 * same book (the version hash is the cache-invalidation key).
 */
export function saveBookFile(bookId, version, blob) {
  if (!bookId || !version || !blob) return Promise.resolve()
  return invalidateOtherVersions(bookId, version)
    .then(() =>
      transaction(FILE_STORE, 'readwrite', (store) => {
        store.put({
          key: fileKey(bookId, version),
          bookId,
          version,
          blob,
          size: blob.size,
          cachedAt: Date.now(),
        })
        return store.get(fileKey(bookId, version))
      })
    )
    .catch(() => {})
}

/**
 * Returns the cached rendered image for a single page (Blob), or null.
 * `bucket` is the render quality bucket so a cached low-resolution image is
 * never served when the reader currently needs a higher-resolution one.
 */
export function getPage(bookId, version, page, bucket) {
  if (!bookId || !version || !page) return Promise.resolve(null)
  return transaction(PAGE_STORE, 'readonly', (store) =>
    store.get(pageKey(bookId, version, bucket, page))
  )
    .then((record) => (record && record.blob ? record : null))
    .catch(() => null)
}

/**
 * Caches one rendered page image (Blob). Pages are capped per book version so
 * we do not cache everything unnecessarily - the oldest pages are evicted.
 */
export function savePage(bookId, version, page, bucket, blob) {
  if (!bookId || !version || !page || !blob) return Promise.resolve()
  const record = {
    key: pageKey(bookId, version, bucket, page),
    bookId,
    version,
    bucket,
    page,
    blob,
    cachedAt: Date.now(),
  }
  return transaction(PAGE_STORE, 'readwrite', (store) => {
    store.put(record)
    return store.get(record.key)
  })
    .then(() => enforcePageCap(bookId, version))
    .catch(() => {})
}

async function enforcePageCap(bookId, version) {
  const all = await transaction(PAGE_STORE, 'readonly', (store) => store.getAll()).catch(() => [])
  const entries = (Array.isArray(all) ? all : []).filter((r) => matchBook(r, bookId) && r.version === version)
  if (entries.length <= MAX_PAGES_PER_VERSION) return
  const overflow = entries
    .sort((a, b) => (a.cachedAt || 0) - (b.cachedAt || 0))
    .slice(0, entries.length - MAX_PAGES_PER_VERSION)
    .map((r) => r.key)
  if (overflow.length) {
    await transaction(PAGE_STORE, 'readwrite', (store) => {
      overflow.forEach((key) => store.delete(key))
      return store.count()
    }).catch(() => {})
  }
}

/**
 * Removes every cached file/page for a book whose version differs from the
 * current one. Called whenever a new version is first saved.
 */
export function invalidateOtherVersions(bookId, keepVersion) {
  if (!bookId) return Promise.resolve()
  const staleKeys = (records) =>
    (Array.isArray(records) ? records : [])
      .filter((r) => matchBook(r, bookId) && r.version !== keepVersion)
      .map((r) => r.key)
  return Promise.all([
    transaction(FILE_STORE, 'readonly', (store) => store.getAll()).then((rows) =>
      deleteKeys(FILE_STORE, staleKeys(rows))
    ),
    transaction(PAGE_STORE, 'readonly', (store) => store.getAll()).then((rows) =>
      deleteKeys(PAGE_STORE, staleKeys(rows))
    ),
  ]).catch(() => {})
}

function deleteKeys(storeName, keys) {
  if (!keys.length) return Promise.resolve()
  return transaction(storeName, 'readwrite', (store) => {
    keys.forEach((key) => store.delete(key))
    return store.count()
  }).then(() => {})
}

export function isCacheAvailable() {
  return typeof indexedDB !== 'undefined' && typeof window !== 'undefined'
}

export default {
  getBookFile,
  saveBookFile,
  getPage,
  savePage,
  invalidateOtherVersions,
  isCacheAvailable,
}