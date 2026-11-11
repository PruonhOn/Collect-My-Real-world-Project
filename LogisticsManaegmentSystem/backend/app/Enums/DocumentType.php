<?php

namespace App\Enums;

/**
 * What kind of paperwork a document is.
 *
 * A closed vocabulary rather than free text, for the reason every other enum in
 * this schema is one: a document register that can only be grouped by a
 * misspelling is not a register, and "which certificates are we actually holding"
 * is the question the module exists to answer.
 *
 * The distinction that catches people out is `commercial_invoice` versus the
 * `invoices` table. They are different documents that happen to share a word. A
 * commercial invoice is the *shipper's* paperwork for the goods - what the
 * consignee's bank and the importing customs authority read, and it carries the
 * shipper's own value and Incoterm. An invoice in this system is *our* bill to a
 * customer, raised against charges, and is money. Keeping the two apart in the
 * vocabulary, in the label and in the table they live in is the point; a
 * document register that mixed them would be quoting a receivable as though it
 * were evidence.
 *
 * Which types are worth an expiry date is deliberately not encoded here. Almost
 * all of them can carry one - a certificate of origin has a validity window, a
 * delivery order a short one - and a rule per type would be a guess about the
 * jurisdictions this business trades in rather than a fact about the document.
 */
enum DocumentType: string
{
    /** The carrier's contract to carry the goods. Negotiated, signed, and amended. */
    case BillOfLading = 'bill_of_lading';

    /**
     * The shipper's invoice for the goods.
     *
     * Not an invoice raised by this business - see the note on the enum itself.
     */
    case CommercialInvoice = 'commercial_invoice';

    /** What is in the container or the pallet, and in what quantity. */
    case PackingList = 'packing_list';

    /** Proves where the goods were made, which is what a duty preference rests on. */
    case CertificateOfOrigin = 'certificate_of_origin';

    /** The cover note. The one type here with an expiry that genuinely matters. */
    case InsuranceCertificate = 'insurance_certificate';

    /** What was declared to the authority, filed by us or by a broker. */
    case CustomsDeclaration = 'customs_declaration';

    /** Releases the goods to the nominated party. Time-limited, per consignment. */
    case DeliveryOrder = 'delivery_order';

    /** Anything the types above do not cover, which is a real and growing pile. */
    case Other = 'other';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::BillOfLading => 'Bill of lading',
            self::CommercialInvoice => 'Commercial invoice',
            self::PackingList => 'Packing list',
            self::CertificateOfOrigin => 'Certificate of origin',
            self::InsuranceCertificate => 'Insurance certificate',
            self::CustomsDeclaration => 'Customs declaration',
            self::DeliveryOrder => 'Delivery order',
            self::Other => 'Other',
        };
    }

    /**
     * Whether this document is normally read by somebody outside the business.
     *
     * Advisory only - advice for a register's default ordering and for the hint on
     * the form - and never a restriction. What is *commercially sensitive* about
     * a document is enforced by a permission instead (`document.download` is held
     * separately from `document.view`), because sensitivity is a fact about the
     * business and not about the paper.
     */
    public function isExternalPaperwork(): bool
    {
        return match ($this) {
            self::BillOfLading,
            self::CommercialInvoice,
            self::CertificateOfOrigin,
            self::DeliveryOrder => true,
            self::PackingList,
            self::InsuranceCertificate,
            self::CustomsDeclaration,
            self::Other => false,
        };
    }
}
