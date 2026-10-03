<?php

namespace App\Presenters;

use App\Models\ContractImport;
use App\Models\House;
use App\Models\RollCall;

/** The page copy for the contract's enum values (plan AC 41, 42, 52) and the house names. */
final class Labels
{
    public const BALLOTS = [
        'nominal' => 'Votação nominal',
        'secret' => 'Votação secreta',
        'symbolic' => 'Votação simbólica',
    ];

    public const KINDS = [
        'final' => 'Decisão sobre a proposta',
        'amendment' => 'Emenda, destaque ou parte do texto',
        'procedural' => 'Procedimento',
        'unclassified' => 'Sem regra correspondente',
    ];

    public const ORIENTATIONS = [
        'yes' => 'Sim',
        'no' => 'Não',
        'abstention' => 'Abstenção',
        'obstruction' => 'Obstrução',
        'free' => 'Liberado',
    ];

    public static function house(House $house): string
    {
        return $house === House::Senado ? 'Senado Federal' : 'Câmara dos Deputados';
    }

    /** "na Câmara dos Deputados", "no Senado Federal". */
    public static function inHouse(House $house): string
    {
        return $house === House::Senado ? 'no Senado Federal' : 'na Câmara dos Deputados';
    }

    /** "da Câmara dos Deputados", "do Senado Federal". */
    public static function ofHouse(House $house): string
    {
        return $house === House::Senado ? 'do Senado Federal' : 'da Câmara dos Deputados';
    }

    /** `{type} {number}/{year}` of the proposition decided, or `{ballot} de DD/MM/AAAA` without one (AC 41). */
    public static function heading(RollCall $rollCall): string
    {
        $p = $rollCall->proposition;
        if ($p !== null && $p->number !== null && $p->year !== null) {
            return "{$p->type} {$p->number}/{$p->year}";
        }

        return self::BALLOTS[$rollCall->ballot].' de '.Dates::br($rollCall->date);
    }

    /** `/metodologia/#regra-camara-09` for rule `camara.09`. */
    public static function ruleAnchor(string $ruleId): string
    {
        return 'regra-'.str_replace('.', '-', $ruleId);
    }

    /**
     * What the footer says was collected, and when: one line per house shown (AC 32).
     *
     * @param  list<House>  $houses
     * @return list<array{source: string, collectedAt: string}>
     */
    public static function sources(array $houses): array
    {
        $lines = [];
        foreach ($houses as $house) {
            $import = ContractImport::latestOf($house);
            if ($import !== null) {
                $lines[] = [
                    'source' => 'Dados abertos '.self::ofHouse($house),
                    'collectedAt' => $import->generated_at->utc()->format('Y-m-d\\TH:i:s\\Z'),
                ];
            }
        }

        return $lines;
    }

    /**
     * The footer line of a page that spans both houses (app-home AC 42): each house with an import, with
     * the Brasília day of its latest one, in one sentence; null when no house has been imported.
     */
    public static function sourcesLine(): ?string
    {
        $parts = [];
        foreach (House::cases() as $house) {
            $import = ContractImport::latestOf($house);
            if ($import !== null) {
                $parts[] = self::ofHouse($house).', coletados em '.Dates::br(HouseActivity::brasiliaDay($import));
            }
        }

        return $parts === [] ? null : 'Dados abertos '.implode(', e ', $parts).'.';
    }
}
