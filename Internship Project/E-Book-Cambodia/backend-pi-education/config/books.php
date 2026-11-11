<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Book file storage
    |--------------------------------------------------------------------------
    |
    | Every storage decision for book documents and covers goes through this
    | config so the platform can move from the local disks to cloud storage
    | (S3, R2, GCS, ...) by changing environment variables and the disks
    | defined in config/filesystems.php -- no business code changes.
    |
    */

    'file_disk' => env('BOOKS_FILE_DISK', 'local'),

    'cover_disk' => env('BOOKS_COVER_DISK', 'public'),

    /*
    | Directory prefixes used for stored objects. The document path also
    | includes the book language segment: books/{language}/{hash}.pdf
     */
    'file_directory' => 'books',

    'cover_directory' => 'covers',

    /*
    | Accepted upload types. Documents are PDF-only (EPUB support was
    | half-implemented and is intentionally not advertised). Covers accept
    | raster image formats only -- SVG is excluded because it can execute
    | scripts when served, and GIF is allowed for existing covers.
     */
    'file_mimes' => ['pdf'],

    'cover_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif', 'heic', 'tiff'],

    /*
    | Upload limits.
     */
    'max_file_kilobytes' => 51200,

    'min_file_bytes' => 256,

    'max_cover_kilobytes' => 5120,

    /*
    | Lifetime (seconds) of temporary signed URLs used to preview private
    | files (e.g. the admin review preview).
     */
    'signed_url_ttl_seconds' => 300,
];
