🎓 Application de Passage d'Examen - Projet de Synthèse
📖 Présentation

Ce projet consiste en le développement d'une application web de gestion et de passage d'examens en ligne, avec une interface destinée aux étudiants, enseignants, et administrateurs.
Le projet a été développé dans le cadre d'un projet de synthèse en développement digital Full Stack.
🛠️ Technologies et outils utilisés

    Back-end : PHP

    Front-end : HTML, CSS, JavaScript

    Base de données : MySQL

    Gestion de projet : Jira

    Gestion du code source : GitHub

📎 Liens du projet

    🔗 Tableau Jira - Suivi des tâches

    🔗 Dépôt GitHub - Code source

🧩 Fonctionnalités principales

    👤 Gestion des utilisateurs : Administrateurs, Enseignants, Étudiants

    📝 Création et gestion des examens (questions ouvertes, QCM, réponses courtes)

    🏆 Passage des examens en ligne avec minuterie

    📊 Système de notation automatique et manuelle

    📑 Affichage des résultats détaillés

    🏫 Gestion des groupes et matières

    🔒 Authentification sécurisée et gestion des rôles

    🗂️ Tableau de bord pour l'administration

🗄️ Base de données

Le projet utilise une base de données MySQL appelée exam_system, structurée comme suit :
Table	Description
users	Gestion des utilisateurs (admin, prof, étudiant)
exams	Informations sur les examens
questions	Questions associées aux examens
choices	Choix pour les questions de type QCM
student_answers	Réponses fournies par les étudiants
exam_students	Suivi des participations aux examens
question_points	Points attribués à chaque question
question_scores	Scores obtenus par question
groups	Groupes d'étudiants
subjects	Matières enseignées
user_groups	Association utilisateurs → groupes
teacher_subjects	Association enseignants → matières

    Remarque : Un utilisateur peut être administrateur (admin), enseignant (teacher) ou étudiant (student).

⚙️ Instructions d'installation

    Cloner le dépôt GitHub :

git clone https://github.com/GhAmk/projet-synthese.git

Configurer l'environnement local :

    Installer XAMPP, WAMP, MAMP ou tout autre serveur PHP/MySQL.

    Copier le projet dans le dossier htdocs (ou équivalent).

Importer la base de données :

    Ouvrir phpMyAdmin.

    Créer une base de données nommée exam_system.

    Importer le fichier exam_system.sql contenant la structure et les données initiales.

Lancer le serveur Apache et MySQL :

    Vérifier que les services sont actifs.

Accéder à l'application via navigateur :

    http://localhost/projet-synthese/

🔐 Accès par défaut
Rôle	Email	Mot de passe
Administrateur	admin@example.com	admin123
Enseignant	teacher1@example.com	teacher123
Étudiant	student1@example.com	student123

    Important : Pensez à sécuriser les mots de passe en production (utiliser password_hash en PHP).

✅ Bonnes pratiques appliquées

    Programmation orientée objet (POO) en PHP

    Utilisation des conventions HTML5 / CSS3 / JavaScript (ES6)

    Respect de la structure MVC (Model-View-Controller) dans le projet

    Suivi de projet Agile avec Jira

    Gestion du code source et des branches avec Git/GitHub

📅 Évolution future

    Implémentation de notifications en temps réel (via WebSockets ou Pusher)

    Module de statistiques avancées pour les enseignants

    Mode "examen surveillé" avec capture webcam

👥 Contributeurs

    Ghizlane Amk - Développeuse Full Stack

    Équipe pédagogique ISTA - Supervision et encadrement

📫 Contact

Pour toute question, suggestion ou collaboration, veuillez nous contacter via :
    GitHub : @elabbady maroua
    GitHub : @GhAmk

    Jira : Communication via le projet

Merci pour votre intérêt pour notre projet ! 🚀
