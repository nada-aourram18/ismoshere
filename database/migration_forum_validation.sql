-- À exécuter une fois sur la base ISMOShare (phpMyAdmin ou mysql client)
ALTER TABLE sujet
  ADD COLUMN statut_validation VARCHAR(24) NOT NULL DEFAULT 'en_attente'
    COMMENT 'en_attente | accepte | refuse'
    AFTER date_creation;

UPDATE sujet SET statut_validation = 'accepte';
