<?php

namespace App\Enums;

/**
 * Per-cinema modules (Pennant). Check with Team::hasFeature(), which also honours requires().
 */
enum TeamFeature: string
{
    case Rozpis = 'rozpis';
    case RozpisAi = 'rozpis-ai';

    public function label(): string
    {
        return match ($this) {
            self::Rozpis => 'Rozpis zmien',
            self::RozpisAi => 'AI návrh rozpisu',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Rozpis => 'Pozície, sloty, zverejnenie rozpisu, notifikácie a export.',
            self::RozpisAi => 'Návrh rozpisu cez Gemini. Funguje len so zapnutým rozpisom a nastaveným GEMINI_API_KEY.',
        };
    }

    public function requires(): ?self
    {
        return match ($this) {
            self::Rozpis => null,
            self::RozpisAi => self::Rozpis,
        };
    }
}
