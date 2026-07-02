# Restructuration Clean Architecture du Projet

Ce plan décrit la réorganisation complète du projet `parking_mobile` pour suivre les principes de la Clean Architecture. Nous allons déplacer les fonctionnalités existantes (`agent` et `caissier`) de `roles/` vers `features/`, réorganiser `auth/`, ajouter les couches manquantes (data, domain, presentation) pour chaque feature, et structurer `core/` et `shared/`.

## User Review Required

> [!WARNING]
> Cette restructuration déplacera et renommera de nombreux fichiers et répertoires. Cela entraînera des modifications massives d'importations à travers tous les fichiers Dart du projet. Toutes les fonctionnalités actuelles (scan, historique, profils, versements) seront conservées sans modification de leur logique interne.

> [!IMPORTANT]
> 1. Le rôle **Agent** sera renommé en **attendant** au niveau des dossiers (ex: `lib/features/attendant/`), mais pour des raisons de stabilité et pour éviter d'introduire des régressions, nous conserverons les noms de classes existants (comme `AgentHomeScreen`, `AgentDashboardScreen`, etc.).
> 2. Les fichiers d'onboarding (`splash_screen.dart` et `onboarding_screen.dart`), qui ne figuraient pas dans votre diagramme cible, seront placés sous `lib/features/auth/presentation/pages/` (sous les noms `splash_page.dart` et `onboarding_page.dart`). C'est le meilleur endroit pour les regrouper de manière cohérente avant la connexion.

## Proposed Changes

---

### Core & Shared Components

#### [NEW] [dio_service.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/services/dio_service.dart)
- Centralisation de la configuration HTTP de `Dio` (baseUrl, timeouts, en-têtes communs) et gestion automatique du jeton d'authentification via `TokenService`.

#### [NEW] [storage_service.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/services/storage_service.dart)
- Service générique encapsulant `SharedPreferences` pour le stockage persistant clé-valeur.

#### [NEW] [validators.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/utils/validators.dart)
- Fonctions utilitaires de validation (e.g. emails, mots de passe, plaques d'immatriculation).

#### [NEW] [formatter.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/utils/formatter.dart)
- Formatage des dates, heures et montants financiers (FCFA).

#### [NEW] [api_exception.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/errors/api_exception.dart)
- Représentation des exceptions levées lors des requêtes HTTP (codes d'erreurs, messages personnalisés du backend).

#### [NEW] [failure.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/errors/failure.dart)
- Représentation typée des échecs métier de la couche domaine (NetworkFailure, ServerFailure, CacheFailure).

#### [NEW] [app_constants.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/core/constants/app_constants.dart)
- Constantes applicatives globales (timeouts, clés SharedPreferences).

#### [NEW] [custom_button.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/shared/widgets/custom_button.dart)
- Composant réutilisable pour les boutons stylisés de l'application.

#### [NEW] [custom_textfield.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/shared/widgets/custom_textfield.dart)
- Champ de saisie réutilisable avec gestion du design dark mode et focus.

#### [NEW] [loading_widget.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/shared/widgets/loading_widget.dart)
- Indicateur de chargement stylisé personnalisé.

#### [NEW] [string_extension.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/shared/extensions/string_extension.dart)
- Utilitaires d'extensions sur les chaînes de caractères.

---

### Routing & Main

#### [NEW] [route_names.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/routes/route_names.dart)
- Renommage de `app_routes.dart` pour stocker les noms/chemins des routes.

#### [DELETE] [app_routes.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/routes/app_routes.dart)
- Supprimé et remplacé par `route_names.dart`.

#### [MODIFY] [app_router.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/routes/app_router.dart)
- Mise à jour de toutes les importations d'écrans déplacés et adaptation au renommage de `app_routes.dart` en `route_names.dart`.

#### [MODIFY] [main.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/main.dart)
- Mise à jour des importations liées à `app_router.dart`.

---

### Features / Auth

#### [NEW] [auth_remote_datasource.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/auth/data/datasources/auth_remote_datasource.dart)
- Source de données distante effectuant les appels réseau liés à l'authentification en utilisant le nouveau `DioService`.

#### [NEW] [user.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/auth/domain/entities/user.dart)
- Entité métier représentant un utilisateur. `UserModel` étendra cette entité.

#### [NEW] [register_page.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/auth/presentation/pages/register_page.dart)
- Écran placeholder pour l'inscription.

#### [NEW] [login_form.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/auth/presentation/widgets/login_form.dart)
- Extraction du formulaire de connexion pour nettoyer `login_page.dart`.

#### [NEW] [login_page.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/auth/presentation/pages/login_page.dart)
- Renommage et déplacement de `login_screen.dart`.

#### [DELETE] [login_screen.dart](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/auth/presentation/pages/login_screen.dart)
- Supprimé suite au renommage en `login_page.dart`.

---

### Features / Attendant (anciennement roles/agent)

#### [NEW] [attendant feature](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/attendant)
- Création de la structure Clean Architecture pour l'attendant :
  - `data/repositories/`
  - `domain/repositories/`
  - `presentation/pages/`
  - `presentation/providers/`
  - `presentation/widgets/`

- Déplacement et réorganisation de tous les fichiers de `roles/agent/` vers ces sous-dossiers.

#### [DELETE] [roles/agent/](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/roles/agent)
- Supprimé après déplacement complet de tous les fichiers.

---

### Features / Caissier (anciennement roles/caissier)

#### [NEW] [caissier feature](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/caissier)
- Création de la structure Clean Architecture pour le caissier :
  - `data/repositories/`
  - `domain/repositories/`
  - `presentation/pages/`
  - `presentation/providers/`
  - `presentation/widgets/`

- Déplacement et réorganisation de tous les fichiers de `roles/caissier/` vers ces sous-dossiers.

#### [DELETE] [roles/caissier/](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/roles/caissier)
- Supprimé après déplacement complet de tous les fichiers.

---

### Features / Admin

#### [NEW] [admin folders](file:///c:/Users/DEV-KKS-TECHNOLOGIES/KKS-PROJET/parking_mobile/lib/features/admin)
- Création de dossiers vides structurés (avec fichier `.gitkeep`) pour accueillir la future logique d'administration :
  - `data/`
  - `domain/`
  - `presentation/`

---

## Verification Plan

### Automated Tests
- Exécution de `flutter pub get` pour s'assurer que les packages sont corrects.
- Exécution de `flutter analyze` pour vérifier les erreurs d'analyse statique ou les imports incorrects.
- Lancement du build de l'application : `flutter build apk --debug` pour valider que le code compile à 100%.

### Manual Verification
- Lancement de l'application sur émulateur ou appareil pour tester :
  - L'écran Splash et Onboarding.
  - La connexion (Auth).
  - La navigation vers les tableaux de bord de l'agent et du caissier.
  - Les interactions clés (scans, formulaires, affichage des historiques).
