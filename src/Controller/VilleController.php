<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Meteo\NiveauAir;
use App\Meteo\SourceMeteo;
use App\Repository\VilleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Le préfixe : toutes les routes de ce contrôleur commencent par /villes.
#[Route('/villes')]
class VilleController extends AbstractController
{
    // A4 — la liste. Le repository est injecté dans la méthode : c'est un
    // service, comme le repository PDO de la séance 6.
    #[Route('', name: 'ville_liste', methods: ['GET'])]
    public function liste(VilleRepository $villeRepository): Response
    {
        return $this->render('ville/liste.html.twig', [
            // findBy(critères, tri) : un SELECT … ORDER BY nom, sans SQL.
            'villes' => $villeRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    // A5, B2, B3 — la fiche.
    // « Ville $ville » : Symfony lit {id} dans l'adresse, fait le find()
    // lui-même, et répond 404 si la ville n'existe pas. \d+ refuse /villes/abc.
    // « SourceMeteo $sourceMeteo » : le service, construit et injecté par
    // Symfony (autowiring). Le contrôleur ne connaît ni l'URL ni le JSON.
    // SourceMeteo (fourni, ajouté au jalon pour jeudi) a les mêmes méthodes
    // que ClientMeteo, mais cherche d'abord dans le cache, puis en base, et
    // n'appelle ClientMeteo — donc l'API — qu'en dernier recours.
    #[Route('/{id}', name: 'ville_fiche', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function fiche(Ville $ville, SourceMeteo $sourceMeteo): Response
    {
        $meteo = $sourceMeteo->meteoActuelle($ville);
        $air = $sourceMeteo->qualiteAir($ville);

        return $this->render('ville/fiche.html.twig', [
            'ville' => $ville,
            'meteo' => $meteo,
            'air' => $air,
            'niveau' => NiveauAir::depuisIndice($air['indice']),
        ]);
    }
}
