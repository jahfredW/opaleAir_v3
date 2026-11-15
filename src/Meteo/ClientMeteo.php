<?php

namespace App\Meteo;

use App\Entity\Ville;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Le seul endroit d'AirOpale qui parle à l'API Open-Meteo.
 *
 * C'est un service : Symfony le construit, et lui passe ce que demande
 * son constructeur — le client HTTP (autowiring), et les deux adresses de
 * l'API, lues dans les variables d'environnement (.env, .env.local).
 * Le contrôleur, lui, ne connaît ni les URL, ni le format JSON.
 */
final class ClientMeteo
{
    public function __construct(
        private HttpClientInterface $client,
        #[Autowire(env: 'METEO_URL')] private string $urlMeteo,
        #[Autowire(env: 'AIR_URL')] private string $urlAir,
    ) {
    }

    /**
     * @return array{heure: string, temperature: float, vent: float, code: int, libelle: string}
     */
    public function meteoActuelle(Ville $ville): array
    {
        // 'query' : HttpClient construit lui-même ?latitude=…&longitude=…,
        // encodage compris. Pas de concaténation à la main.
        $reponse = $this->client->request('GET', $this->urlMeteo . '/v1/forecast', [
            'query' => [
                'latitude' => $ville->getLatitude(),
                'longitude' => $ville->getLongitude(),
                'current' => 'temperature_2m,wind_speed_10m,weather_code',
                'timezone' => 'Europe/Paris',
            ],
        ]);

        // toArray() : décode le JSON en tableau PHP. Si l'API répond une
        // erreur (400, 500…), toArray() lève une exception au lieu de rendre
        // un tableau vide : on ne travaille jamais sur des données fausses.
        $actuel = $reponse->toArray()['current'];

        return [
            'heure' => $actuel['time'],
            'temperature' => $actuel['temperature_2m'],
            'vent' => $actuel['wind_speed_10m'],
            'code' => $actuel['weather_code'],
            'libelle' => CodeMeteo::libelle($actuel['weather_code']),
        ];
    }

    /**
     * @return array{heure: string, indice: int, pm25: float, pm10: float, no2: float}
     */
    public function qualiteAir(Ville $ville): array
    {
        // Même chose, sur l'AUTRE adresse d'Open-Meteo : la qualité de l'air
        // a son propre serveur.
        $reponse = $this->client->request('GET', $this->urlAir . '/v1/air-quality', [
            'query' => [
                'latitude' => $ville->getLatitude(),
                'longitude' => $ville->getLongitude(),
                'current' => 'european_aqi,pm2_5,pm10,nitrogen_dioxide',
                'timezone' => 'Europe/Paris',
            ],
        ]);

        $actuel = $reponse->toArray()['current'];

        return [
            'heure' => $actuel['time'],
            'indice' => $actuel['european_aqi'],
            'pm25' => $actuel['pm2_5'],
            'pm10' => $actuel['pm10'],
            'no2' => $actuel['nitrogen_dioxide'],
        ];
    }
}
