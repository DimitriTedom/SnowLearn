# SnowLearn — Plateforme LMS E-Learning Premium (Dark Mode)

**SnowLearn** est un système de gestion de l'apprentissage (LMS) moderne et épuré, conçu avec une esthétique **Premium Dark Mode** (Indigo/Violet/Glassmorphism). La plateforme supporte trois rôles d'utilisateurs distincts avec des fonctionnalités dédiées, un suivi de progression automatique en AJAX et un système interactif de quiz et de certification.

---

## 🚀 Fonctionnalités Clés

### 👤 Rôles Utilisateurs
*   **Promoteur (Administrateur)** :
    *   Tableau de bord complet avec indicateurs clés (KPIs) : nombre d'étudiants, d'enseignants, de cours, de leçons et de certificats délivrés.
    *   Gestion complète (CRUD) des **Modules**.
    *   Gestion et consultation des listes d'étudiants et d'enseignants.
    *   Attribution et révocation manuelle ou automatique de certificats de validation de module.
*   **Enseignant** :
    *   Tableau de bord de suivi des performances et derniers résultats de quiz.
    *   Création et édition de cours.
    *   Ajout de leçons avec upload de fichiers **PDF** ou **Vidéos (MP4/MOV/etc.)**.
    *   Création de quiz d'évaluation interactifs pour chaque leçon et édition des questions.
*   **Étudiant** :
    *   Catalogue de cours et de modules interactif avec barres de progression dynamiques.
    *   Lecteur de leçons avancé (lecteur vidéo HTML5 ou iframe PDF intégrée).
    *   Système de quiz interactif (question par question) avec revue détaillée des réponses après soumission.
    *   Historique des résultats et impression de certificats de validation de module au format PDF.

### ⏱️ Systèmes de Suivi de Progression (Tracking)
*   **Vidéo** : La leçon est automatiquement marquée comme terminée lorsque l'étudiant a visionné au moins **90%** de la vidéo.
*   **PDF** : Le bouton de complétion de lecture de la leçon apparaît après un délai de lecture minimal de **30 secondes**.
*   **AJAX & API** : Toutes les actions de complétion et de mise à jour de progression (%) s'exécutent en arrière-plan sans rechargement de page.

---

## 🛠️ Stack Technique

*   **Frontend** : HTML5, Vanilla CSS3 (Design System centralisé, Responsive), Vanilla JavaScript (ES6), AJAX (Fetch API).
*   **Backend** : PHP (Programmation procédurale propre, requêtes préparées avec PDO).
*   **Base de Données** : MySQL / MariaDB (Contraintes relationnelles `ON DELETE CASCADE` pour assurer la cohérence des données).

---

## 📦 Installation et Configuration Locale (XAMPP)

1.  **Cloner le dépôt** ou copier les fichiers dans votre répertoire de serveur local (par exemple : `C:\xampp\htdocs\SnowLearn`).
2.  **Démarrer Apache et MySQL** depuis le panneau de configuration XAMPP.
3.  **Importer la base de données** :
    *   Ouvrez phpMyAdmin (`http://localhost/phpmyadmin/`).
    *   Créez une base de données nommée `snowlearn_db`.
    *   Importez le fichier `database.sql` présent à la racine du projet.
4.  **Accéder à la plateforme** : Ouvrez votre navigateur et accédez à [http://localhost/SnowLearn/](http://localhost/SnowLearn/).

---

## 🔑 Comptes de Démonstration

Des comptes avec des rôles et des données de test pré-configurés sont disponibles pour l'évaluation :

| Rôle | Adresse E-mail | Mot de Passe |
| :--- | :--- | :--- |
| **Promoteur (Admin)** | `admin@snowlearn.com` | `Admin123!` |
| **Enseignant** | `prof@snowlearn.com` | `Teacher123!` |
| **Étudiant** | `etudiant@snowlearn.com` | `Student123!` |

*(Note : Un raccourci de remplissage automatique est présent directement sur la page de connexion pour faciliter vos tests).*
