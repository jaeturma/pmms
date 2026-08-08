/**
 * A real, admin-approved sport photo (`Sport.photo_upload_id`) — renders
 * nothing at all when `null` rather than a stock/placeholder image, since
 * no photo upload path exists yet (out of this WP's scope, see
 * `SportController::photo()`). No empty-state card either: an empty photo
 * slot isn't news the way "no venues yet" is, it just doesn't apply.
 */
export function PortalSportPhoto({ photoUrl, sportName }: { photoUrl: string | null; sportName: string }) {
    if (photoUrl === null) {
        return null;
    }

    return (
        <div className="overflow-hidden rounded-[var(--portal-radius)] border border-[var(--portal-border)]">
            <img src={photoUrl} alt={sportName} className="h-64 w-full object-cover sm:h-80" />
        </div>
    );
}
