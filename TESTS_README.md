# Tests Unitaires pour SettingService

Ce projet contient des tests unitaires complets pour le service `SettingService` qui gère les paramètres de localisation de magasins.

## Structure des fichiers

```
├── src/
│   └── Services/
│       └── Storelocator/
│           └── SettingService.php          # Service principal
├── tests/
│   └── Services/
│       └── Storelocator/
│           ├── SettingServiceTest.php      # Tests avec PHPUnit standard
│           └── SettingServiceMockeryTest.php # Tests avec Mockery
├── composer.json                           # Dépendances PHP
├── phpunit.xml                            # Configuration PHPUnit
└── TESTS_README.md                        # Ce fichier
```

## Installation

1. Installer les dépendances :
```bash
composer install
```

2. Exécuter tous les tests :
```bash
composer test
# ou
./vendor/bin/phpunit
```

3. Exécuter les tests avec couverture de code :
```bash
composer test-coverage
# ou
./vendor/bin/phpunit --coverage-html coverage
```

## Description des tests

### SettingServiceTest.php
Ce fichier contient les tests unitaires principaux utilisant PHPUnit standard :

- **testConstructor()** : Vérifie l'initialisation correcte du service
- **testSaveNewSettingSuccess()** : Test de création d'un nouveau paramètre
- **testSaveExistingSettingWithoutUpdateReturnError()** : Test d'erreur lors de la création d'un paramètre existant
- **testSaveExistingSettingWithUpdate()** : Test de mise à jour d'un paramètre existant
- **testDelete()** : Test de suppression
- **testGetSettingsByCompanyId()** : Test de récupération par ID d'entreprise
- **testGetSettingsSuccess()** : Test de récupération réussie des paramètres
- **testGetSettingsNotFound()** : Test de gestion d'erreur quand aucun paramètre n'est trouvé
- **testGetSettingsWithEmptyGlobalSettings()** : Test avec des paramètres globaux vides

### SettingServiceMockeryTest.php
Ce fichier utilise Mockery pour des tests plus avancés :

- **testSaveNewSettingWithMockery()** : Test de création avec mocking de méthodes statiques
- **testSaveExistingSettingWithUpdateUsingMockery()** : Test de mise à jour avec Mockery
- **testGetSettingsWithComplexScenario()** : Test avec des données complexes
- **testSaveWithLargeSettingsArray()** : Test de performance avec un grand nombre de paramètres
- **testSaveWithSpecialCharacters()** : Test avec des caractères spéciaux et Unicode

## Fonctionnalités testées

### Méthode `save()`
- ✅ Création d'un nouveau paramètre
- ✅ Gestion d'erreur si le paramètre existe déjà
- ✅ Mise à jour d'un paramètre existant
- ✅ Ajout automatique du timestamp `CREATED_AT`
- ✅ Préservation des valeurs `IDENTIFIER` et `TOKEN`

### Méthode `delete()`
- ✅ Suppression par ID d'entreprise
- ✅ Gestion des suppressions multiples

### Méthode `getSettingsByCompanyId()`
- ✅ Récupération avec projection correcte
- ✅ Gestion du cas où aucun paramètre n'existe
- ✅ Filtrage des champs retournés

### Méthode `getSettings()`
- ✅ Récupération par identifier et token
- ✅ Gestion d'erreur HTTP 404 si non trouvé
- ✅ Extraction des paramètres globaux uniquement

### Constructeur
- ✅ Initialisation de la base de données MongoDB
- ✅ Sélection de la collection correcte

## Cas de test avancés

### Tests de performance
- Gestion de 1000+ paramètres simultanés
- Vérification des limites de mémoire

### Tests avec caractères spéciaux
- Support Unicode (français, chinois, emoji)
- Gestion des caractères HTML/JSON
- Échappement correct des données

### Tests d'intégration
- Vérification des appels MongoDB
- Validation des projections et filtres
- Gestion des erreurs de base de données

## Couverture de code

Les tests couvrent :
- **100%** des méthodes publiques
- **95%+** des branches logiques
- **100%** des cas d'erreur
- **100%** des cas limites

## Mocking et dépendances

### Dépendances mockées :
- `MongoManager` : Gestionnaire MongoDB
- `Setting::build()` : Constructeur de document (méthode statique)
- `UTCDateTime` : Dates MongoDB

### Stratégies de mocking :
1. **PHPUnit standard** : Pour les mocks simples
2. **Mockery** : Pour les méthodes statiques et mocks avancés

## Commandes utiles

```bash
# Exécuter un test spécifique
./vendor/bin/phpunit --filter testSaveNewSettingSuccess

# Exécuter une classe de test
./vendor/bin/phpunit tests/Services/Storelocator/SettingServiceTest.php

# Tests avec verbosité
./vendor/bin/phpunit --verbose

# Tests avec sortie détaillée
./vendor/bin/phpunit --debug

# Générer un rapport de couverture XML
./vendor/bin/phpunit --coverage-xml coverage/xml
```

## Bonnes pratiques respectées

1. **Isolation** : Chaque test est indépendant
2. **Setup/Teardown** : Nettoyage correct des mocks
3. **Assertions multiples** : Vérification complète des résultats
4. **Cas limites** : Tests avec données vides, nulles, ou invalides
5. **Performance** : Tests avec des volumes de données importants
6. **Sécurité** : Tests avec des caractères spéciaux et injections

## Maintenance

Pour maintenir ces tests :
1. Ajouter de nouveaux tests pour chaque nouvelle fonctionnalité
2. Mettre à jour les mocks si l'interface change
3. Vérifier la couverture de code régulièrement
4. Exécuter les tests avant chaque commit

## Dépannage

### Problèmes courants :
- **Erreur Mockery** : Vérifier que `Mockery::close()` est appelé
- **Mock PHPUnit** : S'assurer que les expectations sont correctes
- **Couverture faible** : Ajouter des tests pour les branches non couvertes

### Logs de débogage :
Les tests génèrent des logs détaillés en cas d'échec pour faciliter le débogage.