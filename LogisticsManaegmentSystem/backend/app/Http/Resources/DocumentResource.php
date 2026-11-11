<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * One document, described.
     *
     * The file is summarised rather than embedded: `file` carries the name, type,
     * size and version number of the copy a download would serve, and the bytes
     * themselves are behind `download_url`. Nothing here is a public URL, because
     * nothing in this table is public - a bill of lading is commercially sensitive,
     * and a signed asset link in a list response is a link that leaks the moment it
     * is pasted into a ticket.
     *
     * `is_expired`, `expires_in_days` and `needs_attention` are all computed here
     * rather than in the browser. They are answers to three different questions -
     * has it lapsed, how long is left, is it on this month's worklist - and the
     * register's badge, the filter and a future notification have to agree about
     * all of them. `needs_attention` in particular is not derivable from the other
     * two without knowing the warning window, and that number is server policy.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $version = $this->currentVersion;

        return [
            'id' => $this->id,
            'title' => $this->title,

            // The third party's own number, where there is one. Nullable and
            // repeated across documents on purpose - see the migration.
            'reference' => $this->reference,

            'document_type' => $this->document_type?->value,
            'document_type_label' => $this->document_type?->label(),

            'shipment' => $this->whenLoaded('shipment', fn () => $this->shipment === null ? null : [
                'id' => $this->shipment->id,
                'reference' => $this->shipment->reference,
                'status' => $this->shipment->status->value,
                'status_label' => $this->shipment->status->label(),
                // The same four keys every other consignment reference in this API
                // emits, so the client can treat it as `ConsignmentRef` and one
                // component renders it wherever it appears.
                'is_closed' => $this->shipment->status->isClosed(),
            ]),

            /*
             * `Y-m-d` for the same reason it is a `date` column: a certificate
             * expires at the end of a day and a timestamp would move that by the
             * hour the request happened to arrive.
             */
            'issued_on' => $this->issued_on?->format('Y-m-d'),
            'expires_on' => $this->expires_on?->format('Y-m-d'),

            /*
             * The current copy, and nothing else about the history.
             *
             * `version_count` is here because a register that shows "version 4"
             * without saying there are three others is inviting somebody to assume
             * nothing came before it. The list is not sent: a paged register would
             * then carry every superseded copy's metadata on every row, and nothing
             * on this screen needs it.
             */
            'file' => $version === null ? null : [
                'name' => $version->original_name,
                'mime_type' => $version->mime_type,
                'size_bytes' => $version->size_bytes,
                'version' => $version->version_number,
                'uploaded_at' => $version->created_at?->toIso8601String(),
                'uploaded_by' => $version->relationLoaded('uploader') && $version->uploader !== null ? [
                    'id' => $version->uploader->id,
                    'name' => $version->uploader->name,
                ] : null,
            ],
            'version_count' => $this->versions_count,

            /*
             * Where to GET the bytes, as a path and never a bare one.
             *
             * Generated from the route itself (`$absolute = false`), so this is the
             * path that actually serves the file rather than a hand-assembled guess
             * at it: re-prefixing the route, moving it to another version or
             * renaming the prefix changes this string with no edit here, and a
             * mismatch would be a 404 from a link the API published itself.
             *
             * It is not an absolute URL. The client is frequently pointed at a
             * different host from the one serving the SPA, and a link built from
             * `APP_URL` sends the file across the network twice on a split-horizon
             * deployment.
             */
            'download_url' => route('api.v1.documents.download', $this->getRouteKey(), false),

            'is_expired' => $this->isExpired(),
            'expires_in_days' => $this->expiresInDays(),
            'needs_attention' => $this->needsAttention(),

            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
