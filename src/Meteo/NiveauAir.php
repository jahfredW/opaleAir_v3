<?php

namespace App\Meteo;

/**
 * Le niveau de qualité de l'air, d'après l'indice européen (European AQI)
 * renvoyé par Open-Meteo (european_aqi).
 *
 * Les seuils sont ceux de la documentation d'Open-Meteo :
 * 0–20 bon, 20–40 correct, 40–60 moyen, 60–80 médiocre,
 * 80–100 très médiocre, au-delà extrêmement médiocre.
 * Un indice pile sur un seuil (20, 40…) tombe dans la classe du dessus.
 */
final class NiveauAir
{
    /**
     * @return array{libelle: string, classe: string}
     *         « classe » complète la classe CSS badge-… de style.css
     */
    public static function depuisIndice(int $indice): array
    {
        // match (true) : la première branche dont la condition est vraie
        // l'emporte — l'ordre des lignes compte, comme dans un if/elseif.
        return match (true) {
            $indice < 20 => ['libelle' => 'Bon', 'classe' => 'bon'],
            $indice < 40 => ['libelle' => 'Correct', 'classe' => 'correct'],
            $indice < 60 => ['libelle' => 'Moyen', 'classe' => 'moyen'],
            $indice < 80 => ['libelle' => 'Médiocre', 'classe' => 'mediocre'],
            $indice <= 100 => ['libelle' => 'Très médiocre', 'classe' => 'tres-mediocre'],
            default => ['libelle' => 'Extrêmement médiocre', 'classe' => 'extremement-mediocre'],
        };
    }
}
