<?php

declare(strict_types=1);

namespace Xterr\Espd\Codelist;

/**
 * ESPD Regulation Parts as defined in Commission Implementing Regulation (EU) 2016/7, Annex 2.
 *
 * @see https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=CELEX:32016R0007
 */
enum EspdPart: string
{
    case I = 'I';
    case II = 'II';
    case III = 'III';
    case IV = 'IV';
    case V = 'V';
    case VI = 'VI';

    /**
     * Returns the official title of this Part from EU Regulation 2016/7.
     */
    public function label(): string
    {
        return match ($this) {
            self::I => 'Information concerning the procurement procedure and the contracting authority or contracting entity',
            self::II => 'Information concerning the economic operator',
            self::III => 'Exclusion grounds',
            self::IV => 'Selection criteria',
            self::V => 'Reduction of the number of qualified candidates',
            self::VI => 'Concluding statements',
        };
    }
}
