<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * FOURNI — une réponse de l'API Open-Meteo, gardée en base.
 *
 * C'est le 2e étage de SourceMeteo (cache → base → API) : une ligne par
 * « question » posée à l'API (la météo de Calais, l'air de Calais…), avec la
 * réponse et l'heure à laquelle on l'a obtenue. La ligne est mise à jour à
 * chaque nouvel appel : la table ne grossit pas.
 */
#[ORM\Entity]
class ReponseApi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // La question posée, par exemple « meteo-50.9513-1.8587 ». Unique : une
    // seule réponse gardée par question. C'est aussi la clé dans le cache.
    #[ORM\Column(length: 100, unique: true)]
    private ?string $cle = null;

    // La réponse, telle que la rend ClientMeteo : un tableau PHP, rangé en JSON.
    #[ORM\Column(type: Types::JSON)]
    private array $donnees = [];

    // Quand l'API a répondu : c'est ce qui dit si la réponse est encore fraîche.
    #[ORM\Column]
    private ?\DateTimeImmutable $recupereLe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCle(): ?string
    {
        return $this->cle;
    }

    public function setCle(string $cle): static
    {
        $this->cle = $cle;

        return $this;
    }

    public function getDonnees(): array
    {
        return $this->donnees;
    }

    public function setDonnees(array $donnees): static
    {
        $this->donnees = $donnees;

        return $this;
    }

    public function getRecupereLe(): ?\DateTimeImmutable
    {
        return $this->recupereLe;
    }

    public function setRecupereLe(\DateTimeImmutable $recupereLe): static
    {
        $this->recupereLe = $recupereLe;

        return $this;
    }
}
