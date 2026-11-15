# AirOpale — jalon de fin de lundi (après B3)

La météo et la qualité de l'air sur la Côte d'Opale. Projet fil rouge de la R3.01
(BUT2 apprentis), à partir de la séance 7.

Le projet tel qu'il doit être à la fin du lundi : l'entité `Ville` et sa migration, les fixtures (cinq
villes), la liste `/villes` et la fiche `/villes/{id}` ; sur la fiche, la météo (B2, service `ClientMeteo`)
et la qualité de l'air avec son badge (B3, `NiveauAir`). Le formulaire d'ajout, les relevés et l'historique
(B4 à B7) se font jeudi, à partir d'ici.

## Reprendre à partir de ce jalon

Jeudi, si votre projet de lundi ne va pas jusqu'au bout de la B3 (ou ne démarre plus) :

1. Arrêtez votre projet **et videz sa base** : `docker compose down -v` dans votre ancien dossier. Les deux
   dossiers s'appellent `airopale` : sans `-v`, ils partagent la même base, et les migrations de ce jalon
   échoueraient sur des tables qui existent déjà.
2. Extrayez cette archive (PowerShell : `tar -xf airopale-lundi.zip`), ouvrez le dossier `airopale/`.
3. Démarrez, puis créez les tables et chargez les villes :

```bash
docker compose up -d --build
docker compose exec web php bin/console doctrine:migrations:migrate
docker compose exec web php bin/console doctrine:fixtures:load
```

À la seconde question, tapez `yes` : `Entrée` seule annule.

## Démarrer

Depuis ce dossier (celui qui contient `docker-compose.yml`) :

```bash
docker compose up -d --build
```

Puis ouvrez <http://localhost:8080>. La toute première page prend **deux à trois minutes** :
Symfony construit son cache. Ne rechargez pas, attendez. Les suivantes sont rapides.

| Adresse | Quoi |
|---|---|
| <http://localhost:8080> | AirOpale |
| <http://localhost:8081> | Adminer — serveur `db`, utilisateur `airopale`, mot de passe `airopale`, base `airopale` |

> **Le port 8080 est déjà pris ?** C'est sans doute SkillSwap qui tourne encore. Dans le dossier de
> SkillSwap : `docker compose down`, puis relancez AirOpale.

## Les commandes

Toutes les commandes PHP se lancent **dans le conteneur** :

```bash
docker compose exec web php bin/console           # la liste des commandes de Symfony
docker compose exec web php bin/console debug:router
docker compose logs -f web                         # les erreurs d'Apache et de PHP
docker compose down -v                             # tout arrêter ET vider la base
```

## Sans réseau

La fiche interroge l'API Open-Meteo à chaque affichage : sans réseau, elle s'arrête sur une erreur. Créez
alors un fichier `.env.local` à côté de `.env`, avec ces deux lignes :

```
METEO_URL=http://localhost/fausse-api
AIR_URL=http://localhost/fausse-api
```

Votre code n'a pas à changer : seule l'adresse de l'API change.
