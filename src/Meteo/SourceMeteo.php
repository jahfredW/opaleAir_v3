<?php

namespace App\Meteo;

use App\Entity\ReponseApi;
use App\Entity\Ville;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * FOURNI — la météo et l'air d'une ville, cherchés sur trois étages :
 *
 *   1. le CACHE   (fichiers dans var/cache/) : le plus rapide, gardé 10 minutes ;
 *   2. la BASE    (table reponse_api)        : gardée tant qu'elle a moins d'1 heure ;
 *   3. l'API      (ClientMeteo, sur le réseau) : la source de vérité, la plus lente.
 *
 * On descend d'un étage seulement si l'étage du dessus n'a rien de frais.
 * En remontant, on remplit les étages traversés : une réponse de l'API est
 * écrite en base ET dans le cache ; une réponse trouvée en base, dans le cache.
 *
 * Le contrôleur appelle SourceMeteo au lieu de ClientMeteo : mêmes méthodes,
 * même tableau en retour, avec deux clés en plus — 'origine' (cache, base ou
 * api) et 'recupereLe' (l'heure de l'appel à l'API).
 *
 * Si les trois étages échouent (rien de frais, API injoignable), l'exception
 * de HttpClient remonte telle quelle, comme avec ClientMeteo : une erreur 500,
 * ou le message « indisponible » si le contrôleur a le try/catch du facultatif C2.
 */
final class SourceMeteo
{
    private const DUREE_CACHE = 600;             // en secondes : 10 minutes
    private const FRAICHEUR_BASE = '-1 hour';    // au-delà, la base est périmée

    public function __construct(
        private ClientMeteo $clientMeteo,
        private CacheInterface $cache,           // le pool « cache.app » de Symfony
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function meteoActuelle(Ville $ville): array
    {
        return $this->chercher('meteo', $ville, fn (): array => $this->clientMeteo->meteoActuelle($ville));
    }

    public function qualiteAir(Ville $ville): array
    {
        return $this->chercher('air', $ville, fn (): array => $this->clientMeteo->qualiteAir($ville));
    }

    /**
     * @param callable(): array $appelerApi l'appel à l'API, fait seulement si besoin
     */
    private function chercher(string $type, Ville $ville, callable $appelerApi): array
    {
        // La même clé pour le cache et pour la base : la question posée.
        $cle = $type . '-' . $ville->getLatitude() . '-' . $ville->getLongitude();
        $origine = 'cache';

        // 1. Le cache. get() rend la valeur si elle y est ; sinon il appelle
        //    la fonction, range ce qu'elle rend dans le cache, et le rend.
        //    La fonction n'est donc exécutée QUE si le cache est vide.
        $trouve = $this->cache->get($cle, function (ItemInterface $element) use ($cle, $appelerApi, &$origine): array {
            $element->expiresAfter(self::DUREE_CACHE);

            // 2. La base : une réponse de moins d'une heure ?
            $reponse = $this->entityManager->getRepository(ReponseApi::class)->findOneBy(['cle' => $cle]);
            if ($reponse !== null && $reponse->getRecupereLe() > new \DateTimeImmutable(self::FRAICHEUR_BASE)) {
                $origine = 'base';

                return ['donnees' => $reponse->getDonnees(), 'recupereLe' => $reponse->getRecupereLe()];
            }

            // 3. L'API. Si elle échoue, l'exception traverse get() : rien n'est
            //    rangé, ni dans le cache ni en base.
            $origine = 'api';
            $donnees = $appelerApi();

            // On garde la réponse en base : une ligne par clé, mise à jour.
            $reponse ??= (new ReponseApi())->setCle($cle);
            $reponse->setDonnees($donnees)->setRecupereLe(new \DateTimeImmutable());
            $this->entityManager->persist($reponse);
            $this->entityManager->flush();

            return ['donnees' => $donnees, 'recupereLe' => $reponse->getRecupereLe()];
        });

        return $trouve['donnees'] + ['origine' => $origine, 'recupereLe' => $trouve['recupereLe']];
    }
}
