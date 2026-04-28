-- ISMOShare - Base vide + structure (utf8mb4)
-- Import : phpMyAdmin > Importer ce fichier
-- Ou CLI : mysql -u root -p < database/schema.sql
-- Les identifiants MySQL doivent permettre DROP/CREATE DATABASE.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS ismoshere;
CREATE DATABASE ismoshere CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ismoshere;

CREATE TABLE filiere (
  id_filier INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom_filiere VARCHAR(191) NOT NULL,
  PRIMARY KEY (id_filier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilisateur (
  id_utilisateur INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL,
  telephon VARCHAR(20) NOT NULL,
  matricule_CEF VARCHAR(20) NOT NULL,
  `role` ENUM('admin','formateur','stagiaire') NOT NULL DEFAULT 'stagiaire',
  id_filier INT UNSIGNED NULL,
  date_inscription DATE NULL,
  photo_profil VARCHAR(255) NOT NULL DEFAULT 'default-user.png',
  mot_de_passe VARCHAR(255) NOT NULL,
  statut VARCHAR(32) NOT NULL DEFAULT 'en attente',
  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_matricule (matricule_CEF),
  UNIQUE KEY uq_telephon (telephon),
  KEY idx_filier (id_filier),
  CONSTRAINT fk_utilisateur_filiere FOREIGN KEY (id_filier) REFERENCES filiere (id_filier) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE groupe (
  id_groupe INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user1 INT UNSIGNED NOT NULL,
  user2 INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_groupe),
  KEY fk_groupe_u1 (user1),
  KEY fk_groupe_u2 (user2),
  CONSTRAINT fk_groupe_user1 FOREIGN KEY (user1) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE,
  CONSTRAINT fk_groupe_user2 FOREIGN KEY (user2) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sujet (
  id_sujet INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titre VARCHAR(255) NOT NULL,
  contenu MEDIUMTEXT NOT NULL,
  categorie VARCHAR(120) NOT NULL DEFAULT 'general',
  id_utilisateur INT UNSIGNED NOT NULL,
  date_creation DATE NOT NULL,
  statut_validation VARCHAR(24) NOT NULL DEFAULT 'en_attente'
    COMMENT 'en_attente | accepte | refuse',
  PRIMARY KEY (id_sujet),
  KEY idx_sujet_user (id_utilisateur),
  CONSTRAINT fk_sujet_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reponse (
  id_reponse INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_sujet INT UNSIGNED NOT NULL,
  id_utilisateur INT UNSIGNED NOT NULL,
  contenu MEDIUMTEXT NOT NULL,
  date_reponse DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_reponse),
  KEY idx_rep_sujet (id_sujet),
  CONSTRAINT fk_reponse_sujet FOREIGN KEY (id_sujet) REFERENCES sujet (id_sujet) ON DELETE CASCADE,
  CONSTRAINT fk_reponse_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE like_sujet (
  id_utilisateur INT UNSIGNED NOT NULL,
  id_sujet INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_utilisateur, id_sujet),
  CONSTRAINT fk_like_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE,
  CONSTRAINT fk_like_sujet FOREIGN KEY (id_sujet) REFERENCES sujet (id_sujet) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ressource (
  id_ressource INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titre VARCHAR(255) NOT NULL,
  description MEDIUMTEXT NULL,
  fichier VARCHAR(255) NOT NULL,
  type VARCHAR(80) NOT NULL DEFAULT 'document',
  filiere VARCHAR(191) NOT NULL DEFAULT '',
  module VARCHAR(191) NOT NULL DEFAULT '',
  date_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut VARCHAR(32) NOT NULL DEFAULT 'en_attente',
  id_utilisateur INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_ressource),
  KEY idx_ress_user (id_utilisateur),
  KEY idx_ress_statut (statut),
  CONSTRAINT fk_ressource_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commentaire (
  id_com INT UNSIGNED NOT NULL AUTO_INCREMENT,
  contune MEDIUMTEXT NOT NULL,
  id_ressource INT UNSIGNED NOT NULL,
  id_utilisateur INT UNSIGNED NULL,
  PRIMARY KEY (id_com),
  KEY idx_com_ress (id_ressource),
  CONSTRAINT fk_commentaire_ressource FOREIGN KEY (id_ressource) REFERENCES ressource (id_ressource) ON DELETE CASCADE,
  CONSTRAINT fk_commentaire_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE annonce (
  id_annonce INT UNSIGNED NOT NULL AUTO_INCREMENT,
  titre VARCHAR(255) NOT NULL,
  contenu MEDIUMTEXT NOT NULL,
  statut VARCHAR(50) NOT NULL DEFAULT 'publie',
  date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  image VARCHAR(255) NULL,
  id_utilisateur INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_annonce),
  KEY idx_ann_user (id_utilisateur),
  CONSTRAINT fk_annonce_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification (
  id_notification INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  message MEDIUMTEXT NOT NULL,
  date_notification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut VARCHAR(20) NOT NULL DEFAULT 'unread',
  visible TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_notification),
  KEY idx_notif_user (id_utilisateur),
  CONSTRAINT fk_notification_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact (
  id_contact INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(191) NOT NULL,
  sujet VARCHAR(255) NOT NULL,
  message MEDIUMTEXT NOT NULL,
  date_envoi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_contact)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messager (
  id_msg INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_groupe INT UNSIGNED NOT NULL,
  id_utilisateur INT UNSIGNED NOT NULL,
  contenu MEDIUMTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_msg),
  KEY idx_msg_groupe (id_groupe),
  CONSTRAINT fk_messager_groupe FOREIGN KEY (id_groupe) REFERENCES groupe (id_groupe) ON DELETE CASCADE,
  CONSTRAINT fk_messager_user FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO filiere (nom_filiere) VALUES
  ('Développement Digital'),
  ('Gestion des entreprises'),
  ('Infrastructure réseaux et systèmes'),
  ('Multimédia et webdesign');
