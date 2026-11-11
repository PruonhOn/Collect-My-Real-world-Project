<?php

namespace Database\Seeders\Catalog;

use Database\Seeders\Catalog\Datasets\CompetitiveCatalog;
use Database\Seeders\Catalog\Datasets\HumanitiesCatalog;
use Database\Seeders\Catalog\Datasets\KhmerCatalog;
use Database\Seeders\Catalog\Datasets\LowerSecondaryCatalog;
use Database\Seeders\Catalog\Datasets\MathCatalog;
use Database\Seeders\Catalog\Datasets\PrimaryCatalog;
use Database\Seeders\Catalog\Datasets\ProfessionalCatalog;
use Database\Seeders\Catalog\Datasets\TeacherCatalog;
use Database\Seeders\Catalog\Datasets\UpperSecondaryCatalog;

/**
 * The whole demo catalogue in one list.
 *
 * The order of the shelves mirrors the way the catalogue is browsed: the
 * school grade shelves first, then the shelves that are organised by a
 * subject or by a reader rather than by a grade.
 */
final class DemoCatalog
{
    /**
     * Every demo record, in shelf order.
     *
     * @return list<array<string, mixed>>
     */
    public static function books(): array
    {
        return array_merge(
            MathCatalog::books(),
            KhmerCatalog::books(),
            PrimaryCatalog::books(),
            LowerSecondaryCatalog::books(),
            UpperSecondaryCatalog::books(),
            CompetitiveCatalog::books(),
            TeacherCatalog::books(),
            ProfessionalCatalog::books(),
            HumanitiesCatalog::books(),
        );
    }

    /**
     * The shelves of the demo catalogue.
     *
     * @return list<string>
     */
    public static function shelves(): array
    {
        return [
            'Math',
            'Khmer',
            'Primary',
            'Lower Secondary',
            'Upper Secondary School',
            'Competitive Exams',
            'Teacher Resources',
            'Professional',
            'Humanities',
        ];
    }
}
