# PMMS File Upload Foundation

Generic, reusable file upload backbone (WP-01-10). Feature modules build on this rather than reimplementing storage.

## Storage

- Disk: `config('uploads.disk')` — default `local` (private, not publicly served). Override with `UPLOADS_DISK`.
- Directory: `uploads/` on the disk; stored names are framework-generated hashes (originals kept in metadata).
- Metadata: `file_uploads` table — `uploaded_by` (FK `users`, null on user deletion), `disk`, `path`, `original_name`, `mime_type`, `size`.

## Rules (`config/uploads.php`)

- Allowed extensions: jpg, jpeg, png, webp, pdf, doc, docx, xls, xlsx.
- Max size: 10 MB (`UPLOADS_MAX_KB` to override).

## API (web routes, `auth` + `verified`)

| Route | Action | Authorization |
|---|---|---|
| `POST /uploads` (`uploads.store`) | Upload a file (`file` field) | Any authenticated, verified user |
| `GET /uploads/{upload}` (`uploads.download`) | Download | Owner only (`FileUploadPolicy@view`) |
| `DELETE /uploads/{upload}` (`uploads.destroy`) | Delete file + record | Owner only (`FileUploadPolicy@delete`) |

## Building Blocks

- `App\Services\FileUploadService` — `store(UploadedFile, User): FileUpload`, `delete(FileUpload): void`. Use this from future modules (e.g., athlete documents) instead of calling `Storage` directly.
- `App\Http\Requests\FileUploadRequest` — validation.
- `App\Policies\FileUploadPolicy` — owner-only access; broaden with roles in a later phase.
- `FileUpload::factory()` for tests.

## Tests

`tests/Feature/FileUploadTest.php` — guest denial, allowed upload, type rejection, size rejection, owner download/delete, non-owner 403s (8 tests).
