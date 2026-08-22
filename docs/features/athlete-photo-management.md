# Athlete photo management

PMMS stores two logically and physically separate athlete images through the existing `FileUpload` filesystem architecture:

- **Passport / Profile Photo** (`photo_upload_id`) is private identification media used in athlete administration, accreditation, and authorized evaluation.
- **Sports Photo** (`sports_photo_upload_id`) is presentation media for authorized player and sports views. It never falls back to the passport photo.

Neither image is an eligibility document. School ID, birth certificate, report card, medical, and consent attachments retain their existing workflow.

## Processing policy

The browser provides a 4:5 crop, zoom, reposition, rotation, preview, and JPEG compression before upload. JPG, PNG, and WebP originals up to 20 MB are accepted. The server independently decodes the real image content with GD, normalizes JPEG EXIF orientation, center-crops and resizes to 800×1000, removes source metadata through re-encoding, adaptively compresses, and rejects any permanent image above 500 KB.

The original camera file is not retained. Generated JPEG derivatives are:

- `thumb`: 200×250 for compact lists/cards
- `card`: 480×600 for player cards
- main: 800×1000 for profiles and reasonable printing

All paths use randomized names on the configured private upload disk. Photo serving remains policy-authorized. Public consumers must use Sports Photo only and show a silhouette when it is missing.

## Replacement and authorization

Athlete policy rules remain authoritative. A coach or delegation officer may replace photos only while the athlete/delegation is editable; administrators and organizers retain their existing management authority. Replacements remove the previous main image and derivatives. Passport and Sports Photo upload/replacement actions receive distinct audit events.

Sports Photo completeness is displayed separately and does not become a DSAC eligibility requirement.
