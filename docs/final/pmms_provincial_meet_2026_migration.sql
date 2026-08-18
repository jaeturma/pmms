-- PMMS Provincial Meet 2026 - Final Data & Assignment Migration
-- Source: TWG TM and TO.xlsx
-- Target: MySQL 8 / Laravel 13 compatible relational structure
-- IMPORTANT: Account provisioning uses a staging queue; no passwords or emails are invented.
-- Coaches are NOT seeded as users. Coaches self-register, select sport assignments, and enroll athletes.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

CREATE TABLE IF NOT EXISTS pmms_meets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  year SMALLINT UNSIGNED NOT NULL,
  status ENUM('draft','active','completed','archived') NOT NULL DEFAULT 'active',
  starts_at DATE NULL,
  ends_at DATE NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_municipalities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  congressional_district_code VARCHAR(30) NULL COMMENT 'Backfill from existing official geographic master data',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_school_districts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  municipality_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pmms_sd_muni FOREIGN KEY (municipality_id) REFERENCES pmms_municipalities(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_people (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  person_key VARCHAR(190) NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  source_flags VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_sports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(180) NOT NULL,
  classification ENUM('regular','paragames') NOT NULL DEFAULT 'regular',
  source_label VARCHAR(255) NULL,
  configuration_status ENUM('confirmed','needs_confirmation') NOT NULL DEFAULT 'confirmed',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_meet_sports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meet_id BIGINT UNSIGNED NOT NULL,
  sport_id BIGINT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_meet_sport (meet_id, sport_id),
  CONSTRAINT fk_pmms_ms_meet FOREIGN KEY (meet_id) REFERENCES pmms_meets(id),
  CONSTRAINT fk_pmms_ms_sport FOREIGN KEY (sport_id) REFERENCES pmms_sports(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_twg_units (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meet_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(120) NOT NULL,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_twg_unit (meet_id, code),
  CONSTRAINT fk_pmms_twg_meet FOREIGN KEY (meet_id) REFERENCES pmms_meets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_twg_memberships (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  twg_unit_id BIGINT UNSIGNED NOT NULL,
  person_id BIGINT UNSIGNED NOT NULL,
  role_title VARCHAR(150) NOT NULL,
  source_sequence INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_twg_member (twg_unit_id, person_id, role_title),
  CONSTRAINT fk_pmms_twgmem_unit FOREIGN KEY (twg_unit_id) REFERENCES pmms_twg_units(id),
  CONSTRAINT fk_pmms_twgmem_person FOREIGN KEY (person_id) REFERENCES pmms_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_dsc_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meet_id BIGINT UNSIGNED NOT NULL,
  municipality_id BIGINT UNSIGNED NOT NULL,
  school_district_id BIGINT UNSIGNED NOT NULL,
  person_id BIGINT UNSIGNED NOT NULL,
  is_lead TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_dsc (meet_id, school_district_id, person_id),
  CONSTRAINT fk_pmms_dsc_meet FOREIGN KEY (meet_id) REFERENCES pmms_meets(id),
  CONSTRAINT fk_pmms_dsc_muni FOREIGN KEY (municipality_id) REFERENCES pmms_municipalities(id),
  CONSTRAINT fk_pmms_dsc_sd FOREIGN KEY (school_district_id) REFERENCES pmms_school_districts(id),
  CONSTRAINT fk_pmms_dsc_person FOREIGN KEY (person_id) REFERENCES pmms_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_sport_personnel_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meet_sport_id BIGINT UNSIGNED NOT NULL,
  person_id BIGINT UNSIGNED NOT NULL,
  municipality_id BIGINT UNSIGNED NULL,
  school_district_id BIGINT UNSIGNED NULL,
  role_code VARCHAR(100) NOT NULL,
  role_label VARCHAR(180) NOT NULL,
  assignment_scope VARCHAR(120) NULL,
  source_sequence INT NULL,
  source_district_text VARCHAR(180) NULL,
  requires_system_user TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_sport_person (meet_sport_id, person_id, role_code, source_sequence),
  CONSTRAINT fk_pmms_spa_ms FOREIGN KEY (meet_sport_id) REFERENCES pmms_meet_sports(id),
  CONSTRAINT fk_pmms_spa_person FOREIGN KEY (person_id) REFERENCES pmms_people(id),
  CONSTRAINT fk_pmms_spa_muni FOREIGN KEY (municipality_id) REFERENCES pmms_municipalities(id),
  CONSTRAINT fk_pmms_spa_sd FOREIGN KEY (school_district_id) REFERENCES pmms_school_districts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_user_provisioning (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  person_id BIGINT UNSIGNED NOT NULL,
  suggested_username VARCHAR(100) NOT NULL UNIQUE,
  email VARCHAR(255) NULL,
  target_role VARCHAR(100) NOT NULL DEFAULT 'sport_personnel',
  provisioning_reason VARCHAR(100) NOT NULL,
  provisioning_status ENUM('pending','provisioned','skipped','failed') NOT NULL DEFAULT 'pending',
  linked_user_id BIGINT UNSIGNED NULL COMMENT 'Map to existing Laravel users.id after provisioning',
  must_set_password TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_provision_person (person_id),
  CONSTRAINT fk_pmms_prov_person FOREIGN KEY (person_id) REFERENCES pmms_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_sport_participation_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meet_id BIGINT UNSIGNED NOT NULL,
  sport_code VARCHAR(100) NOT NULL,
  classification ENUM('regular','paragames') NOT NULL DEFAULT 'regular',
  level ENUM('elementary','secondary') NOT NULL,
  participation_rule VARCHAR(255) NULL,
  source_row INT NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pmms_part_rule (meet_id, sport_code, level, source_row),
  CONSTRAINT fk_pmms_pr_meet FOREIGN KEY (meet_id) REFERENCES pmms_meets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pmms_coach_registration_settings (
  meet_id BIGINT UNSIGNED PRIMARY KEY,
  self_registration_enabled TINYINT(1) NOT NULL DEFAULT 1,
  coach_selects_sports TINYINT(1) NOT NULL DEFAULT 1,
  coach_enrolls_athletes TINYINT(1) NOT NULL DEFAULT 1,
  requires_assignment_approval TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pmms_crs_meet FOREIGN KEY (meet_id) REFERENCES pmms_meets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO pmms_meets (code,name,year,status)
VALUES ('DDOPAA-2026','DdOPAA Provincial Meet 2026',2026,'active')
ON DUPLICATE KEY UPDATE name=VALUES(name), year=VALUES(year), status=VALUES(status);

INSERT INTO pmms_municipalities (code,name) VALUES ('COMPOSTELA', 'Compostela')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('LAAK', 'Laak')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('MACO', 'Maco')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('MABINI', 'Mabini')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('MAWAB', 'Mawab')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('MONTEVISTA', 'Montevista')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('MARAGUSAN', 'Maragusan')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('MONKAYO', 'Monkayo')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('NABUNTURAN', 'Nabunturan')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('NEW BATAAN', 'New Bataan')
ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO pmms_municipalities (code,name) VALUES ('PANTUKAN', 'Pantukan')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'COMPOSTELA_EAST','Compostela East'
FROM pmms_municipalities m WHERE m.code='COMPOSTELA'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'COMPOSTELA_WEST','Compostela West'
FROM pmms_municipalities m WHERE m.code='COMPOSTELA'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'LAAK_NORTH','Laak North'
FROM pmms_municipalities m WHERE m.code='LAAK'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'LAAK_SOUTH','Laak South'
FROM pmms_municipalities m WHERE m.code='LAAK'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MACO_NORTH','Maco North'
FROM pmms_municipalities m WHERE m.code='MACO'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MACO_SOUTH','Maco South'
FROM pmms_municipalities m WHERE m.code='MACO'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MABINI','Mabini'
FROM pmms_municipalities m WHERE m.code='MABINI'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MAWAB','Mawab'
FROM pmms_municipalities m WHERE m.code='MAWAB'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MONTEVISTA','Montevista'
FROM pmms_municipalities m WHERE m.code='MONTEVISTA'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MARAGUSAN_EAST','Maragusan East'
FROM pmms_municipalities m WHERE m.code='MARAGUSAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MARAGUSAN_WEST','Maragusan West'
FROM pmms_municipalities m WHERE m.code='MARAGUSAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MONKAYO_EAST','Monkayo East'
FROM pmms_municipalities m WHERE m.code='MONKAYO'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'MONKAYO_WEST','Monkayo West'
FROM pmms_municipalities m WHERE m.code='MONKAYO'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'NABUNTURAN_EAST','Nabunturan East'
FROM pmms_municipalities m WHERE m.code='NABUNTURAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'NABUNTURAN_WEST','Nabunturan West'
FROM pmms_municipalities m WHERE m.code='NABUNTURAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'NEW_BATAAN','New Bataan'
FROM pmms_municipalities m WHERE m.code='NEW BATAAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'PANTUKAN_NORTH','Pantukan North'
FROM pmms_municipalities m WHERE m.code='PANTUKAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);
INSERT INTO pmms_school_districts (municipality_id,code,name)
SELECT m.id,'PANTUKAN_SOUTH','Pantukan South'
FROM pmms_municipalities m WHERE m.code='PANTUKAN'
ON DUPLICATE KEY UPDATE name=VALUES(name), municipality_id=VALUES(municipality_id);

INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ABDUL_LAGUNGAN','ABDUL LAGUNGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ACE_DAVE_D_CANE','ACE DAVE D. CANE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ACE_MARLO_A_CELADA','ACE MARLO A. CELADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('AILYN_B_PUNO','AILYN B. PUNO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('AIMEE_A_TOMAS','AIMEE A. TOMAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('AIRES_INSAO','AIRES INSAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('AIRES_P_TIA','AIRES P. TIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALBERT_S_VENTURA','ALBERT S. VENTURA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALBIN_LACABA','ALBIN LACABA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALDIN_M_NAQUILA','ALDIN M. NAQUILA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALDREN_DAYDAY','ALDREN DAYDAY','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALEX_G_BARREDO','ALEX G. BARREDO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALEXANDER_M_LAGARE','ALEXANDER M. LAGARE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALJEAN_MANSANADES','ALJEAN MANSANADES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALJUN_A_ABAPO','ALJUN A. ABAPO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALL_HOST_SCHOOL_HEAD','ALL HOST SCHOOL HEAD','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALLAN_R_GUERTA','ALLAN R. GUERTA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALMA_S_LOREQUE','ALMA S. LOREQUE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALMARIE_P_NAVARRE','ALMARIE P. NAVARRE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALME_A_TALANDRON','ALME A. TALANDRON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALOHA_FE_V_BATAR','ALOHA FE V. BATAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALRICH_RYAN_TAGALOG','ALRICH RYAN TAGALOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALRIN_TANUDRA','ALRIN TANUDRA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALVIN_ESCOBAR','ALVIN ESCOBAR','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALVIN_J_ABARECIO','ALVIN J. ABARECIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ALYSSA_NERI_TUMANDA','ALYSSA NERI TUMANDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANA_LYN_H_MICUTUAN','ANA LYN H. MICUTUAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANABEL_C_BAYLON','ANABEL C. BAYLON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANABEL_P_GUIPITACIO','ANABEL P. GUIPITACIO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANABELLE_E_ALCOS','ANABELLE E. ALCOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANACEL_A_NACARIO','ANACEL A. NACARIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALISA_P_GLORIA','ANALISA P. GLORIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALIZA_D_LIMIKID','ANALIZA D. LIMIKID','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALIZA_R_GLINOGO','ANALIZA R. GLINOGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALOU_L_SIDAYON','ANALOU L. SIDAYON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALYN_M_LORETO','ANALYN M. LORETO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALYN_M_SANGUINZA','ANALYN M. SANGUINZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALYN_P_DINGAL','ANALYN P. DINGAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANALYN_P_PANERIO','ANALYN P. PANERIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANDY_P_CABODOC','ANDY P. CABODOC','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANGELBERT_DOYOG','ANGELBERT DOYOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANGELITO_D_CARREON','ANGELITO D. CARREON','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANGELO_C_GUTIERREZ_JR','ANGELO C. GUTIERREZ JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANGELOU_E_DAYANAN','ANGELOU E. DAYANAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANNA_MARIE_MANIQUEZ','ANNA MARIE MANIQUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANNABILLE_B_BASTASA','ANNABILLE B. BASTASA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANNALYN_ADVINCULA','ANNALYN ADVINCULA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ANNIE_F_VALDERAMA','ANNIE F. VALDERAMA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('AQUILINO_CAMUS','AQUILINO CAMUS','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARA_A_QUILLO','ARA A. QUILLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARCELI_A_HUMOL','ARCELI A. HUMOL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARCELIE_SALINAS','ARCELIE SALINAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARCHIE_TANDING','ARCHIE TANDING','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARCHIMEDED_M_TAYONE','ARCHIMEDED M. TAYONE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARDIANO_DOYDOY','ARDIANO DOYDOY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARGIE_BILANDRES','ARGIE BILANDRES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARGIE_D_QUIRANTE','ARGIE D. QUIRANTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARIEL_ADOLFO','ARIEL ADOLFO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARJUN_C_MOJERES','ARJUN C. MOJERES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARLYN_B_LIM','ARLYN B. LIM','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARMIL_GRAFANI','ARMIL GRAFANI','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARNEL_A_SALOMON','ARNEL A. SALOMON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARNIEL_C_MARVAS','ARNIEL C. MARVAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARNIEL_CASTILLO','ARNIEL CASTILLO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARNOLD_S_TABAQUIN','ARNOLD S. TABAQUIN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARTCHELL_M_REYES','ARTCHELL M. REYES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ARZIEL_PAULINE_JACKIE_T_JAMORA','ARZIEL PAULINE JACKIE T. JAMORA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ATTY_ARMANDO_DALISAY_JR','ATTY ARMANDO DALISAY JR','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BALDWIN_R_BINGIL','BALDWIN R. BINGIL','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BEBERLY_AMPARO','BEBERLY AMPARO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BELLA_JOSIE_T_LIMBAGA','BELLA JOSIE T. LIMBAGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BENJAMIN_O_MADRIAGA','BENJAMIN O. MADRIAGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BERNIE_M_NAQUILA','BERNIE M. NAQUILA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BEVERLY_ANN_C_PENARANDA','BEVERLY ANN C. PEÑARANDA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BEVERLY_C_REYES','BEVERLY C. REYES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BEVERLY_T_TAGUPA','BEVERLY T. TAGUPA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BLAIRE_IAN_V_ADARO','BLAIRE IAN V. ADARO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BOB_DYLAN_MILABAT','BOB DYLAN MILABAT','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BON_JOIE_LIMBAGA','BON JOIE LIMBAGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BONICA_CORSIGA','BONICA CORSIGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BOYETH_G_RULIDA','BOYETH G. RULIDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BRADLEY_VON_SEVILLA','BRADLEY VON SEVILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BRYAN_G_PANINDIM','BRYAN G. PANINDIM','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('BUENAFE_A_TEMAN','BUENAFE A. TEMAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CAREN_P_MANIQUEZ','CAREN P MANIQUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CARLO_ANTHONY_L_AMAC','CARLO ANTHONY L. AMAC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CARLO_TUTOR','CARLO TUTOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CECELIO_H_ENTECOSA','CECELIO H. ENTECOSA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CECILE_SEVILLANO','CECILE SEVILLANO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CECILY_LOURDS_M_BARUIZ','CECILY LOURDS M. BARUIZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CELERINA_V_MINOZA','CELERINA V. MIÑOZA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CELINDO_T_RAUT_RAUT','CELINDO T. RAUT-RAUT','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CERIAL_DALAOTA','CERIAL DALAOTA','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHARISSE_MAY_Z_SABAY','CHARISSE MAY Z. SABAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHARIZA_I_SAREN','CHARIZA I SAREN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHARLITO_REYES_JR','CHARLITO REYES JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHARMINE_A_CUNADO','CHARMINE A. CUNADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHAZAIRA_MAE_M_TAPAY','CHAZAIRA MAE M. TAPAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHELSEA_FAYE_Y_ESTRERA','CHELSEA FAYE Y. ESTRERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHERRY_ANN_B_RIVERA','CHERRY ANN B. RIVERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHERRY_ANN_Y_PANIO','CHERRY ANN Y. PANIO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHERRY_LAURENCE_SUAZO','CHERRY LAURENCE SUAZO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHERYL_MARIE_N_MACAS','CHERYL MARIE N. MACAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRIST_IAN_TIMBAL','CHRIST IAN TIMBAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTIAN_JAY_PATENIO','CHRISTIAN JAY PATENIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTIAN_L_APAO','CHRISTIAN L. APAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTIAN_LI_MIRANDA','CHRISTIAN LI MIRANDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTINE_JAY_M_URBINA','CHRISTINE JAY M. URBINA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTLE_MARIE_A_CASADOR','CHRISTLE MARIE A. CASADOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTOPHER_A_LASARTE','CHRISTOPHER A. LASARTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTOPHER_A_NISTAL','CHRISTOPHER A. NISTAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTOPHER_JANOHAN','CHRISTOPHER JANOHAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTOPHER_L_CRUDA','CHRISTOPHER L. CRUDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRISTY_MALIG_ON','CHRISTY MALIG ON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CHRIZELL_L_BALILI','CHRIZELL L. BALILI','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CILITO_D_DIAYON','CILITO D. DIAYON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CLAIRE_E_QUIJANO','CLAIRE E. QUIJANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CLARIBEL_S_ELING','CLARIBEL S. ELING','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CLAVINOVA_B_BOLONIA','CLAVINOVA B. BOLONIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CLEFFER_S_GARBAN','CLEFFER S. GARBAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CORRINE_GAIL_R_BULAC','CORRINE GAIL R. BULAC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CORY_CRISTY_P_EYAS','CORY CRISTY P. EYAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CRISPIN_SAMOYA','CRISPIN SAMOYA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CRISSELY_B_LAMORIN','CRISSELY B. LAMORIN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CRISTITO_S_SUAN_JR','CRISTITO S. SUAN JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CYRIL_B_UNGGOY','CYRIL B. UNGGOY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CYRIL_G_ESTRADA','CYRIL G. ESTRADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('CZARINE_MAE_F_INCLONAR','CZARINE MAE F. INCLONAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DAFRHIL_ROSE_QUIJADA','DAFRHIL ROSE QUIJADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DAISY_M_BESAS','DAISY M. BESAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DAISY_MAE_DIAN_P_ABANIZA','DAISY MAE DIAN P. ABANIZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DANELO_A_CASE','DANELO A. CASE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DANNY_BOY_C_ORILLA','DANNY BOY C. ORILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DAVE_GALES','DAVE GALES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DEBBIE_RANALAN','DEBBIE RANALAN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DELSON_B_MUSCA','DELSON B. MUSCA','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DEMETRIO_OPENA','DEMETRIO OPENA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DENNIS_B_YUMANG','DENNIS B. YUMANG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DENNIS_D_MAURICIO','DENNIS D. MAURICIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DENNIS_L_PELIGRO','DENNIS L. PELIGRO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DEXTER_C_HAO','DEXTER C. HAO','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DEXTER_CEQUINA','DEXTER CEQUINA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DEXTER_MATT_LEVI_BELLO','DEXTER MATT LEVI BELLO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DIANA_ROSE_GARBAN','DIANA ROSE GARBAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DINA_MARIECOR_P_MOJADO','DINA MARIECOR P. MOJADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DINDO_RABAGO','DINDO RABAGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DIOSANNE_M_BERANA','DIOSANNE M. BERANA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DIOSDADO_P_VELENCIO','DIOSDADO P. VELENCIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DIOVEROSE_E_CAMPANER','DIOVEROSE E. CAMPANER','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DOMINGO_B_MURILLO','DOMINGO B. MURILLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DOMINIC_B_YUMANG','DOMINIC B. YUMANG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DONALD_P_PIZARAS','DONALD P. PIZARAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DONNA_DEE_T_BOLOFER','DONNA DEE T. BOLOFER','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DONNA_KENNETH_NISTAL','DONNA KENNETH NISTAL','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DONNABEL_BETONIO','DONNABEL BETONIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DONNALYN_JOYCE_BRANDINO','DONNALYN JOYCE BRANDINO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DOREEN_JOY_D_GLINOGO','DOREEN JOY D. GLINOGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DOWEE_EVE_TAGUD','DOWEE EVE TAGUD','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DR_GRINGO_JOHN_F_PELAEZ','DR. GRINGO JOHN F. PELAEZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DR_NIKKA_CARLA_R_ALFARO','DR. NIKKA CARLA R. ALFARO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('DREAMY_B_BABANTO','DREAMY B. BABANTO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDEN_BERG_P_BONGCAC','EDEN BERG P. BONGCAC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDEN_S_ESPARTERO','EDEN S ESPARTERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDGAR_B_CORTIDOR','EDGAR B. CORTIDOR','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDISON_D_BAUTISTA','EDISON D. BAUTISTA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDUARD_C_CUASITO','EDUARD C. CUASITO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDWARD_S_YOUNG','EDWARD S. YOUNG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDWIN_D_REMORERAS','EDWIN D. REMORERAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EDWIN_N_ESTANOL','EDWIN N. ESTAÑOL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELBEN_DOCENA','ELBEN DOCENA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELENA_M_ESTRADA','ELENA M. ESTRADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELEOMAR_CARDENIO','ELEOMAR CARDENIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELEONOR_A_MUSCA','ELEONOR A. MUSCA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELIEMAR_JHONE_J_BAMBAO','ELIEMAR JHONE J. BAMBAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELIZABETH_JOY_F_POLINAR','ELIZABETH JOY F. POLINAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELJE_MAY_A_PENIDO','ELJE MAY A. PENIDO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELJUN_M_SUMAYANG','ELJUN M. SUMAYANG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELPEDIO_JR_D_PERALTA','ELPEDIO JR. D PERALTA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELVIE_B_JUNIO','ELVIE B. JUNIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELVIE_JOY_T_SERIDA','ELVIE JOY T. SERIDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELY_P_SAROMINES','ELY P. SAROMINES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ELYN_MARIE_D_SARAEL','ELYN MARIE D. SARAEL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EMELY_B_RAMOS','EMELY B. RAMOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EMMA_RITA_S_MENDOZA','EMMA RITA S MENDOZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EMMANUAL_CLARION','EMMANUAL CLARION','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ENGR_RANALYN_BALONG','ENGR RANALYN BALONG','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ERLEN_BANDIBAS','ERLEN BANDIBAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ERNESTO_GOMEZ','ERNESTO GOMEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ERNIE_LUNGAN','ERNIE LUNGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ERWIN_C_ALQUIZA','ERWIN C. ALQUIZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ERWIN_J_SALTA','ERWIN J. SALTA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ESHUEL_C_GIGARE','ESHUEL C. GIGARE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EUNISA_ALLIONES','EUNISA ALLIONES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EVELYN_E_SAAD','EVELYN E. SAAD','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EVELYN_N_YU','EVELYN N. YU','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('EVELYN_T_PADERANGA','EVELYN T. PADERANGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FAITH_A_SALINAS','FAITH A. SALINAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FAITH_GRACE_MANZANARES','FAITH GRACE MANZANARES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FATIMA_C_JALANE','FATIMA C. JALANE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FE_B_FORMENTERA','FE B. FORMENTERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FE_DELOSTRICO','FE DELOSTRICO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FE_G_CONTIGA','FE G. CONTIGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FEBLOUT_TAGAWA','FEBLOUT TAGAWA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FELIPE_III_AUTOR','FELIPE III AUTOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FERDINAND_O_VELUZ','FERDINAND O. VELUZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FERLY_FERNANDO','FERLY FERNANDO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FERNANDO_JR_CORTEZ','FERNANDO JR CORTEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FILTRICIO_A_SALINAS','FILTRICIO A. SALINAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FRANCIS_LAGUNA','FRANCIS LAGUNA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FREDDIE_BOLONOS','FREDDIE BOLONOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FREEDETEZ_P_ENICUELA','FREEDETEZ P. ENICUELA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FREITZY_AMOT_L_COJEN','FREITZY AMOT L. COJEN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FRELIPACAR_B_GUCOR','FRELIPACAR B. GUCOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FRINE_G_REYES','FRINE G. REYES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FROIL_BEN_I_PELIGRINO','FROIL BEN I. PELIGRINO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('FRYZIEL_RHEENAMAE_PACPAKIN','FRYZIEL RHEENAMAE PACPAKIN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GALDYS_LAUSA_VIRTUDAZO','GALDYS LAUSA VIRTUDAZO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GARBAN_CLEFFER','GARBAN CLEFFER','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GEMMA_I_PAL','GEMMA I. PAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GEOFREY_E_HADLOCON','GEOFREY E. HADLOCON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GERALDINE_CALESA','GERALDINE CALESA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GERARO_L_MONTANA','GERARO L. MONTANA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GERONIMO_B_GALANZA_JR','GERONIMO B. GALANZA JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GERRYPHER_VIERNES','GERRYPHER VIERNES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GERWIN_RUGAY','GERWIN RUGAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GESTY_BUGHAO','GESTY BUGHAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GEZER_A_CAGAPE','GEZER A. CAGAPE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GIMMA_FERNANDEZ','GIMMA FERNANDEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GINA_CASERA','GINA CASERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GIRLIE_JEAN_E_JAVIERTO','GIRLIE JEAN E. JAVIERTO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GLADYS_V_ESPINOSA','GLADYS V. ESPINOSA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GLECEL_BEJOC','GLECEL BEJOC','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GLEE_BLANCO','GLEE BLANCO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GLENN_A_LIMOSNERO','GLENN A. LIMOSNERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GLESSEL_BANGIAN','GLESSEL BANGIAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GLORYFEL_B_MAMUSOG','GLORYFEL B. MAMUSOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GODFREE_ESPERO','GODFREE ESPERO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GRACE_D_PONTILLAS','GRACE D. PONTILLAS','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GRACE_MAE_T_TORREON','GRACE MAE T. TORREON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GRACE_P_ALURA','GRACE P. ALURA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GREZEL_C_CASTRO','GREZEL C. CASTRO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('GUILLERMO_AYALA_JR','GUILLERMO AYALA JR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HAIDE_G_LAURON','HAIDE G. LAURON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HAIDE_LAURON','HAIDE LAURON','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HAIDEE_C_AMISTOSO','HAIDEE C. AMISTOSO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HANNA_GRACE_MORDEN','HANNA GRACE MORDEN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HARLY_G_GINGO','HARLY G. GINGO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HARVEY_C_PIOQUINTO','HARVEY C. PIOQUINTO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HARVEY_NICKY_ESMEDIA','HARVEY NICKY ESMEDIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HELEN_B_TABUSO','HELEN B. TABUSO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HENRY_JAN_C_DALAGAN','HENRY JAN C. DALAGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HILDA_OPENA','HILDA OPENA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HONEY_BETH_C_OPPUS','HONEY BETH C. OPPUS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HONEYLYN_C_JAINAR','HONEYLYN C. JAINAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HYRO_JAMES_P_COMODA','HYRO JAMES P. COMODA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('HYUAN_C_MALUPAY','HYUAN C. MALUPAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IAN_JUNTILLA','IAN JUNTILLA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IAN_P_TAPANAN','IAN P. TAPANAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IRESH_JOY_L_ABON','IRESH JOY L. ABON','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IRIS_B_LABADAN','IRIS B. LABADAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IRISH_GENE_A_SALINAS','IRISH GENE A. SALINAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ISIDRO_D_SADONGDONG_JR','ISIDRO D. SADONGDONG, JR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ISIDRO_REFAMONTE_JR','ISIDRO REFAMONTE JR','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IVY_CLAIRE_SALINAS','IVY CLAIRE SALINAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IVY_D_PARAJELE','IVY D. PARAJELE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('IVY_MARIE_A_DECATORIA','IVY MARIE A. DECATORIA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JACKY_LYN_LACRE','JACKY LYN LACRE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JACKY_R_MAMACOS','JACKY R. MAMACOS','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAMARISON_L_BALBERO','JAMARISON L. BALBERO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAMES_M_MERANO','JAMES M. MERANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAMES_V_ROSALINDA','JAMES V. ROSALINDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAN_JOFFRED_B_COMAINGKING','JAN JOFFRED B. COMAINGKING','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAN_MARK_D_MANGUILIMUTAN','JAN MARK D. MANGUILIMUTAN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JANICE_D_TAASAN','JANICE D. TAASAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JANICE_ESCARPIO','JANICE ESCARPIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JANICE_S_CAPUYAN','JANICE S. CAPUYAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JANINE_J_OLIVA','JANINE J. OLIVA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JANNEL_P_CALOGMOC','JANNEL P. CALOGMOC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAPETH_SEBUALA','JAPETH SEBUALA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAQUELYN_V_PASTOR','JAQUELYN V. PASTOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JASIEL_M_MARIANO','JASIEL M. MARIANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JASMIN_P_PASCAN','JASMIN P. PASCAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JASSEN_MAE_L_GOMEZ','JASSEN MAE L. GOMEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAY_A_CASAS','JAY A. CASAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAY_NINO_ROA_MAQUILAN','JAY NINO ROA MAQUILAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAY_R_C_ANG','JAY R C. ANG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAY_ZON_B_LUMBA','JAY ZON B. LUMBA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAYMES_BALALA','JAYMES BALALA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAYSON_B_SALILI','JAYSON B. SALILI','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAYSON_COSO','JAYSON COSO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAYSON_MARUNDAN_MANGAPAS','JAYSON MARUNDAN MANGAPAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JAYVEE_T_CRUDA','JAYVEE T. CRUDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEA_AN_B_ENAGA','JEA-AN B. ENAGA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEAN_J_BASSIG','JEAN J. BASSIG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEAN_MAY_A_SABARITA','JEAN MAY A. SABARITA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEANETH_E_RAGA','JEANETH E. RAGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEANETTE_M_PACALDO','JEANETTE M. PACALDO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JED_LUCKY_S_RACHO','JED LUCKY S. RACHO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEEFHIL_C_OBUGA','JEEFHIL C. OBUGA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEHPIE_B_MABIDA','JEHPIE B. MABIDA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEIRMANE_P_DAHAY','JEIRMANE P. DAHAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JELYN_M_DIFUNTORUM','JELYN M. DIFUNTORUM','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEMAR_C_CATOC','JEMAR C. CATOC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENELYN_H_MARBEBE','JENELYN H. MARBEBE','TM_TO,TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNEYBELLE_J_JACINTO','JENNEYBELLE J. JACINTO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNIE_D_CALAPE','JENNIE D. CALAPE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNIFER_D_ESTRIVO','JENNIFER D. ESTRIVO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNIFER_LAZO','JENNIFER LAZO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNIFER_N_JAMAROLIN','JENNIFER N. JAMAROLIN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNIFER_T_TEMPLONUEVO','JENNIFER T. TEMPLONUEVO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNILYN_T_SEROTE','JENNILYN T. SEROTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JENNY_MAE_A_SABLON','JENNY MAE A. SABLON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEREME_SEVILLA','JEREME SEVILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEREMIAH_G_RECREO','JEREMIAH G. RECREO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JEREMIAS_M_GALLO','JEREMIAS M. GALLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JERIC_B_HERBITO','JERIC B. HERBITO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JERRY_A_ESPINA','JERRY A. ESPINA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JERSON_O_RINGCONADA','JERSON O. RINGCONADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JERSON_YBANES','JERSON YBAÑES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESLIE_O_TIANZON','JESLIE O. TIANZON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESLY_A_MAGNO','JESLY A. MAGNO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESSA_L_COLARES','JESSA L. COLARES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESSA_MAE_S_BRIGOLE','JESSA MAE S. BRIGOLE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESSIE_A_CENTILLAS','JESSIE A. CENTILLAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESSIE_L_REFAMONTE','JESSIE L. REFAMONTE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESSON_S_PANERIO','JESSON S. PANERIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESTER_PESCADERO','JESTER PESCADERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESTONI_A_SAMPUTON','JESTONI A. SAMPUTON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JESUS_SICAT_M_CATUGAL','JESUS SICAT M. CATUGAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JHEA_MAE_P_DAGAME','JHEA MAE P. DAGAME','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JHONREY_B_PALUA','JHONREY B. PALUA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JIMMY_VARQUEZ','JIMMY VARQUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JIONE_V_CABACTULAN','JIONE V. CABACTULAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JO_MARIE_M_VILLA','JO-MARIE M. VILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOAN_A_BULAN','JOAN A. BULAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOAN_A_ETURMA','JOAN A. ETURMA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOANN_S_BALIONG','JOANN S. BALIONG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOANNE_Y_SALVADOR','JOANNE Y. SALVADOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOANNIE_PALABIA','JOANNIE PALABIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOCEL_LABADAN','JOCEL LABADAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOCEL_P_LOPEZ','JOCEL P. LOPEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOCELYN_D_MOLERO','JOCELYN D. MOLERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOCELYN_P_QUIMBA','JOCELYN P. QUIMBA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOCELYN_Q_BESTIL','JOCELYN Q. BESTIL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JODEL_ABENOJA','JODEL ABENOJA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOE_ART_G_CAPATAN','JOE ART G. CAPATAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOECIP_ANINON','JOECIP ANIÑON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEL_E_CALISO','JOEL E. CALISO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEL_S_JUMALON','JOEL S. JUMALON','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEMAR_G_COMAWAS','JOEMAR G. COMAWAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEMON_SIBAYAN','JOEMON SIBAYAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEY_B_AMADA','JOEY B. AMADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEY_B_PAMPLONA','JOEY B. PAMPLONA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOEY_CASILA','JOEY CASILA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHANESS_N_ESCOVILLA','JOHANESS N. ESCOVILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHANNE_PRINCESS_A_WAGAS','JOHANNE PRINCESS A. WAGAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_DAVE_E_RUFANO','JOHN DAVE E. RUFANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_EARL_B_TUBIANOSA','JOHN EARL B. TUBIANOSA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_MAPELLE_LABITIGAN','JOHN MAPELLE LABITIGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_MARK_MANGARON','JOHN MARK MANGARON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_PAUL_J_QUIBAL','JOHN PAUL J. QUIBAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_PETER_O_OCLEDA','JOHN PETER O. OCLEDA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHN_RUSSEL_B_CADUNGOG','JOHN RUSSEL B. CADUNGOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHNBERT_F_INCLONAR','JOHNBERT F. INCLONAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHNIE_REY_T_ALCOS','JOHNIE REY T. ALCOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHNWARD_AMARANTO','JOHNWARD AMARANTO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOHNY_A_SARAEL','JOHNY A. SARAEL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOJI_MARIE_C_BENITO','JOJI MARIE C. BENITO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOKO_FRITS_B_DIAZ','JOKO FRITS B. DIAZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOMARISON_L_BALBERO','JOMARISON L. BALBERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONAH_JOFFA_FAITH_P_FUENTES','JONAH JOFFA FAITH P. FUENTES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONALEE_J_TORLAO','JONALEE J. TORLAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONALYN_M_PAVIA','JONALYN M. PAVIA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONARDO_ROBLE','JONARDO ROBLE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONAS_T_BATONGHINOG','JONAS T. BATONGHINOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONATHAN_A_URGEL','JONATHAN A. URGEL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONATHAN_B_LIMOSNERO','JONATHAN B. LIMOSNERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONATHAN_ECAT','JONATHAN ECAT','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JONNIFER_T_CHUA','JONNIFER T. CHUA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JORDAN_C_GOCHOCO','JORDAN C. GOCHOCO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSE_R_VILLANUEVA_JR','JOSE R. VILLANUEVA JR','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSEPH_BILLIONES','JOSEPH BILLIONES','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSEPH_CORPUZ','JOSEPH CORPUZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSEPH_M_MARTOS','JOSEPH M. MARTOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSEPHINE_C_QUILATON','JOSEPHINE C. QUILATON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSEPHINE_M_VILLAFUERTE','JOSEPHINE M. VILLAFUERTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSEPHINE_S_PERSEGAS','JOSEPHINE S. PERSEGAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSETTE_ASILO','JOSETTE ASILO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSHUA_MEPICO','JOSHUA MEPICO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSHUA_YBANEZ','JOSHUA YBANEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOSIE_J_SABERON','JOSIE J. SABERON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOVANNI_A_LUSICA','JOVANNI A. LUSICA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOVAR_T_SAGING','JOVAR T. SAGING','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOVER_L_BASTASA','JOVER L. BASTASA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOVIL_T_VILLARREIZ','JOVIL T. VILLARREIZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOY_I_LUMOCSO','JOY I. LUMOCSO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOYCE_FE_R_BAGATNAN','JOYCE FE R. BAGATNAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOYCEE_MAE_CALDITO','JOYCEE MAE CALDITO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JOYLEN_B_ELLAN','JOYLEN B. ELLAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUADJIE_PARBA','JUADJIE PARBA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUDY_ANN_R_ORTEGA','JUDY ANN R. ORTEGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUDYLAND_YU','JUDYLAND YU','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULANNE_F_GANZA','JULANNE F. GANZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULIBETH_P_BOBO','JULIBETH P. BOBO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULIET_B_GONIDA','JULIET B. GONIDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULITO_JR_D_CASES_JR','JULITO JR D. CASES JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULIUS_ERNEST_R_MIRANDA','JULIUS ERNEST R. MIRANDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULIUS_RIGOR_DUQUE','JULIUS RIGOR DUQUE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULIUS_TAPOT','JULIUS TAPOT','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JULUIS_BALANSAG','JULUIS BALANSAG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUMAR_BASALO','JUMAR BASALO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUMAR_IAN_L_TEVES','JUMAR IAN L. TEVES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUN_REY_AGOY','JUN REY AGOY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUNALITA_M_LOPEZ','JUNALITA M. LOPEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUNARD_ALIDRO','JUNARD ALIDRO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUNERIC_A_RICALDE','JUNERIC A. RICALDE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JUNMAR_A_GUISANG','JUNMAR A. GUISANG','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('JURLIE_M_MURING','JURLIE M. MURING','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KAIRA_GUIRIGAY','KAIRA GUIRIGAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KARL_BRYAN_Q_SABERON','KARL BRYAN Q. SABERON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KAROLE_E_MOJECA','KAROLE E. MOJECA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KATLEEN_JOY_S_MARTINEZ','KATLEEN JOY S. MARTINEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KEN_CLARK_PUGO','KEN CLARK PUGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KENNETH_E_CABALAN','KENNETH E. CABALAN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KENNETH_SALAMANCA','KENNETH SALAMANCA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KEVIN_BUL_AN','KEVIN BUL-AN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KEYMARK_FELIPAS','KEYMARK FELIPAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KIM_B_BANOGBANOG','KIM B. BANOGBANOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KIM_CHRISTOPHER_R_NIALA','KIM CHRISTOPHER R. NIALA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KJAY_D_ARO','KJAY D. ARO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KRIS_D_BARNIDO','KRIS D. BARNIDO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KRISTENE_NOVIE_M_PEREZ','KRISTENE NOVIE M. PEREZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KRISTIA_REYNA_B_TABANYAG','KRISTIA REYNA B. TABANYAG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KRISTINE_MAE_OCHEA','KRISTINE MAE OCHEA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('KRITINE_ROBBIE_L_SANCHEZ','KRITINE ROBBIE L. SANCHEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LANI_M_GOGO','LANI M. GOGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LAVILLA_L_WATE','LAVILLA L. WATE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEAHNA_GEM_R_JUBANE','LEAHNA GEM R. JUBANE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEO_L_LARGO','LEO L. LARGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEO_MARLO_P_RAMOS','LEO MARLO P. RAMOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEO_REY_CAGAS','LEO REY CAGAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEO_SANTO_O_ESCARPE','LEO SANTO O. ESCARPE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEONARDO_M_CALOLO','LEONARDO M. CALOLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEONILO_S_TORREJAS','LEONILO S.TORREJAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEONILYN_KHRIS_L_ABARECIO','LEONILYN KHRIS L. ABARECIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LEONOR_E_MELLIVO','LEONOR E. MELLIVO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LESTER_C_ENRIQUEZ','LESTER C. ENRIQUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LHESTER_JAY_U_SALAO','LHESTER JAY U. SALAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LIBENLY_E_CASTILLO','LIBENLY E. CASTILLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LINDSAY_CANEZO','LINDSAY CANEZO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LINDY_LOU_DUG_OY','LINDY LOU DUG-OY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LIOBA_C_LINDONG_II','LIOBA C. LINDONG, II','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LIRA_P_EYAS','LIRA P. EYAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LLANE_M_ALBINA','LLANE M. ALBINA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LLOYD_D_NABASCA','LLOYD D. NABASCA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LLYOD_BON_M_LEGONES','LLYOD BON M. LEGONES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LORELIE_P_BARUIZ','LORELIE P. BARUIZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOREMIE_J_PELANTES','LOREMIE J. PELANTES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOUIE_GOMEZ','LOUIE GOMEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOURENCE_U_FEBRIA','LOURENCE U. FEBRIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOUVIEN_DEE_L_IBARRA','LOUVIEN DEE L. IBARRA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOUWELL_ZAL_C_ALMILLA','LOUWELL ZAL C. ALMILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOVELY_FAITH_L_ESTRADA','LOVELY FAITH L. ESTRADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOVELY_KRIS_Q_BATINGAL','LOVELY KRIS Q. BATINGAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOVELY_M_ENANORIA','LOVELY M. ENANORIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LOWEI_JANUYAN','LOWEI JANUYAN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LUDWIG_VON_C_BRAGA','LUDWIG VON C. BRAGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LYDEL_BRIAN_CANETE','LYDEL BRIAN CANETE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LYKA_PATULOT','LYKA PATULOT','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LYZLE_C_ABREGANA','LYZLE C. ABREGANA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('LYZZA_P_BODIONGAN','LYZZA P. BODIONGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAE_ROSELLER_CHANCO','MAE ROSELLER CHANCO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAEZY_ALBOROTO','MAEZY ALBOROTO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAFER_C_POLISTICO','MAFER C. POLISTICO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAGDALINA_GALIMBA','MAGDALINA GALIMBA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAIZA_D_OPISAN','MAIZA D. OPISAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAR_ULYSSES_SOMBREO','MAR ULYSSES SOMBREO','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARGARITA_DEIPARINE','MARGARITA DEIPARINE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARGIE_B_CELADA','MARGIE B. CELADA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARGIELYN_M_AWAS','MARGIELYN M. AWAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARGIRIE_ASUQUE','MARGIRIE ASUQUE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIA_BELLA_ALVAREZ','MARIA BELLA ALVAREZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIA_DIONESSA_A_COYOCA','MARIA DIONESSA A. COYOCA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIA_LORENA_M_JIMENEZ','MARIA LORENA M. JIMENEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIA_TERESA_A_APOG','MARIA TERESA A. APOG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIA_THESALONIKA_PRINCESS_ONA','MARIA THESALONIKA PRINCESS ONA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARICEL_G_MANALO','MARICEL G. MANALO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARICEL_MERCADO','MARICEL MERCADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARICHU_P_GARANO','MARICHU P. GARANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIFE_D_SHAGOL','MARIFE D. SHAGOL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARILAG_O_BACALSO','MARILAG O. BACALSO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIO_ALUAD_II','MARIO ALUAD II','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARION_ESPINOSA','MARION ESPINOSA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARISSA_C_CASTORICO','MARISSA C. CASTORICO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARISSA_I_LIWAYA','MARISSA I. LIWAYA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARISSA_M_ORBINO','MARISSA M. ORBINO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARITES_A_MALDA','MARITES A. MALDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIVIC_R_TULO','MARIVIC R. TULO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARIZ_PORRAS','MARIZ PORRAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARJO_L_LABADLABAD','MARJO L. LABADLABAD','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARJON_G_ABONERO','MARJON G. ABONERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARJORIE_L_GANADEN','MARJORIE L. GANADEN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARJORIE_ORAIS','MARJORIE ORAIS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARK_AUBREY_P_POSADAS','MARK AUBREY P. POSADAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARLON_G_PLAZA','MARLON G. PLAZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARLON_P_PIZARRAS','MARLON P. PIZARRAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARLON_SUAZO','MARLON SUAZO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARLOU_LUMAKANG','MARLOU LUMAKANG','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARNELLE_TERO','MARNELLE TERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARNELY_JANE_BERNAL','MARNELY JANE BERNAL','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAROJA_R_BELISARIO','MAROJA R. BELISARIO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARRY_ANN_PAULINES','MARRY ANN PAULINES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARVI_S_ESPENA','MARVI S. ESPENA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARWIN_GIL_GOLEZ','MARWIN GIL GOLEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_ANN_C_AROCHA','MARY ANN C. AROCHA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_ANN_LAGUITAO','MARY ANN LAGUITAO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_JANE_J_ORDENIZA','MARY JANE J. ORDEÑIZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_JOAN_C_SENO','MARY JOAN C. SENO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_JOY_C_MISSION','MARY JOY C. MISSION','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_JOY_NAQUILA','MARY JOY NAQUILA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_PAZ_V_PASTERA','MARY PAZ V. PASTERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_ROSE_A_DERANO','MARY ROSE A. DERANO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_ROSE_AMORA_QUEBEC','MARY ROSE AMORA-QUEBEC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARY_ROSE_CANO','MARY ROSE CAŇO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MARYJANE_M_CABEROS','MARYJANE M. CABEROS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAXWELL_ZOILON','MAXWELL ZOILON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MAYBEL_H_STA_IGLESIA','MAYBEL H. STA. IGLESIA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MC_VEE_MANALILI','MC VEE MANALILI','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MECHEL_F_MONTIBON','MECHEL F. MONTIBON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MEDARD_T_APIT','MEDARD T. APIT','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MEDELYN_TIMARIO','MEDELYN TIMARIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELCHI_FIGUEROA','MELCHI FIGUEROA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELGIE_F_NAQUILA','MELGIE F. NAQUILA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELISSA_SHEAREENE_M_MAYORMITA','MELISSA SHEAREENE M. MAYORMITA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELISSA_T_SELEGENCIA','MELISSA T. SELEGENCIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELTHER_FE_O_PADILLA','MELTHER FE O. PADILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELVA_P_AMPILAN','MELVA P. AMPILAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MELVIN_JADE_V_HUBAHIB','MELVIN JADE V. HUBAHIB','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERAFLOR_ONGAYO','MERAFLOR ONGAYO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERCY_GIFT_M_SALINAS','MERCY GIFT M. SALINAS','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERIAM_R_ANDRADE','MERIAM R. ANDRADE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERLIE_MARTIR','MERLIE MARTIR','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERLIE_P_MARTIR','MERLIE P. MARTIR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERLYN_B_BAQUERO','MERLYN B. BAQUERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERRY_CRIS_D_BATUCAN','MERRY CRIS D. BATUCAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MERRY_JEAN_C_MEDRINA','MERRY JEAN C. MEDRINA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MEVYLIN_A_EMBAY','MEVYLIN A. EMBAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHAEL_ESTEBAN','MICHAEL ESTEBAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHAEL_FRANCES_PINSOY','MICHAEL FRANCES PINSOY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHAEL_JAY_R_L_ESPADILLA','MICHAEL JAY-R L. ESPADILLA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHAEL_KEMS_Z_COLEGADO','MICHAEL KEMS Z. COLEGADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHAEL_P_PACHECO','MICHAEL P. PACHECO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHAEL_V_DELA_PENA','MICHAEL V. DELA PENA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHEAL_JOHN_SOLANO','MICHEAL JOHN SOLANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHELLE_M_BALDONADO','MICHELLE M. BALDONADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MICHELLE_Y_GAMIL','MICHELLE Y. GAMIL','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MILDRED_COMODA','MILDRED COMODA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MILTON_L_BENILAN','MILTON L. BENILAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MINERVA_C_BUENO','MINERVA C. BUENO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MIRA_N_ARRANGUEZ','MIRA N. ARRANGUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MIRASOL_PANOY','MIRASOL PANOY','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MONETTE_A_GAMUTAN','MONETTE A. GAMUTAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MORAIDA_P_SALAPANTAN','MORAIDA P. SALAPANTAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('MYLYN_D_GARCIA','MYLYN D. GARCIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NANCY_CARILLO','NANCY CARILLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NASRODDIN_S_CABUGATAN','NASRODDIN S. CABUGATAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NEACEL_D_JUNTILLA','NEACEL D. JUNTILLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NECKLE_J_ARAIS','NECKLE J. ARAIS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NEILMHAR_E_MAGALLANES','NEILMHAR E. MAGALLANES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NELIA_R_SANCHEZ','NELIA R. SANCHEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NELSON_C_CANO','NELSON C. CAÑO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NELSON_CATIPAY','NELSON CATIPAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NELSON_P_BAUDAN','NELSON P. BAUDAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NENETH_T_SILVARES','NENETH T. SILVARES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NENITA_H_VALENCIA','NENITA H. VALENCIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NERISSA_G_DAROLLO','NERISSA G. DAROLLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NERISSA_G_HERNANDEZ','NERISSA G. HERNANDEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NETHEL_RUBY_REPONTE','NETHEL RUBY REPONTE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NIEL_MAR_L_CORPUZ','NIEL MAR L. CORPUZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NILDA_N_SENO','NILDA N. SENO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NILIETA_MACALANGA','NILIETA MACALANGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOEL_BALMORIA','NOEL BALMORIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOEMI_P_CANALES','NOEMI P. CANALES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOHARA_O_PINUTE','NOHARA O. PINUTE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOIME_C_CASTIL','NOIME C. CASTIL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOLE_VILLASON','NOLE VILLASON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NONA_BAE_MANUDSOD','NONA BAE MANUDSOD','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NONITO_MATA','NONITO MATA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NORBERT_VINCENT_S_MIACO','NORBERT VINCENT S. MIACO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NORBERTO_S_MANLANGIT','NORBERTO S. MANLANGIT','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOREEN_AGOSTO','NOREEN AGOSTO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NORLITO_LASAY','NORLITO LASAY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NORLYN_JANE_M_ESPINOSA','NORLYN JANE M. ESPINOSA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('NOUMI_T_ANISCAL','NOUMI T. ANISCAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('OEJOFIEL_JOHN_P_SANCHEZ','OEJOFIEL JOHN P. SANCHEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ORLANDO_JR_M_ESCOLLAR','ORLANDO JR M. ESCOLLAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PABLITO_PARADIRO','PABLITO PARADIRO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PACITA_D_MANCAO','PACITA D. MANCAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PAMEL_ROSE_DARATO','PAMEL ROSE DARATO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PEDRO_CABAHUG_JR','PEDRO CABAHUG JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PELBERT_JAMES_DURADO','PELBERT JAMES DURADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PEPITO_T_VILLARREIZ','PEPITO T. VILLARREIZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PEVY_V_DELA_CRUZ','PEVY V. DELA CRUZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PHILIP_C_SERIDA','PHILIP C. SERIDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PHOEBE_GAY_L_REFAMONTE','PHOEBE GAY L. REFAMONTE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PRETS_A_DONGALO','PRETS A. DONGALO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PRINCESS_B_DAGONDON','PRINCESS B. DAGONDON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('PYKE_KUDERA','PYKE KUDERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RACHEL_B_ALBET','RACHEL B. ALBET','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RACHELLE_D_INTIG','RACHELLE D. INTIG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAFAEL_NALANGAN','RAFAEL NALANGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAINBOW_A_NAVASCA','RAINBOW A. NAVASCA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAMON_M_GENABE','RAMON M. GENABE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RANDY_CANEDA','RANDY CANEDA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RANDY_H_MAESTRE','RANDY H. MAESTRE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RANDY_M_RALLOS','RANDY M. RALLOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RANDY_N_SIPSIP','RANDY N. SIPSIP','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RANSOM_PINSOY','RANSOM PINSOY','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAQUEL_H_SOTELO','RAQUEL H. SOTELO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAQUEL_M_MANUEL','RAQUEL M. MANUEL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAQUEL_T_LAZO','RAQUEL T. LAZO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAYMART_CELESTE','RAYMART CELESTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RAYMOND_S_TURBELLA','RAYMOND S. TURBELLA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REANE_F_LABANO','REANE F. LABANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REGGIE_M_ABSIN','REGGIE M. ABSIN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENANTE_C_ALIA','RENANTE C. ALIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENANTE_E_LIQUIT','RENANTE E. LIQUIT','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENANTE_GACMATAN','RENANTE GACMATAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENANTE_LIQUIT','RENANTE LIQUIT','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENANTE_R_JESURO','RENANTE R. JESURO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENATO_PACPAKIN','RENATO PACPAKIN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENE_JOSEPH_ABARQUEZ','RENE JOSEPH ABARQUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENE_L_LEGARA','RENE L. LEGARA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RENEGILDO_M_GOGO','RENEGILDO M. GOGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RESHELLE_MAY_AYE_H_TUBAL','RESHELLE MAY AYE H. TUBAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REVICK_P_BERMOY','REVICK P. BERMOY','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REWYN_C_MANUEL','REWYN C. MANUEL','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REY_CRIS_JUANILLO','REY CRIS JUANILLO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REY_SAROMINES','REY SAROMINES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REYMART_C_MALIG_ON','REYMART C. MALIG-ON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REYNALDO_CASTILLO_JR','REYNALDO CASTILLO JR.','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REYNALDO_M_CATIENZA','REYNALDO M. CATIENZA','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('REYNAN_LIBRETA','REYNAN LIBRETA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RHAPY_A_VIDAL','RHAPY A. VIDAL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RHEEX_G_CASTOR','RHEEX G. CASTOR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RHOBELYN_P_SEMACIO','RHOBELYN P. SEMACIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RHODORA_MAY_S_GARCIA','RHODORA MAY S. GARCIA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RHONNIE_JAY_C_ARADILLA','RHONNIE JAY C. ARADILLA','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RHUJELEN_C_TORMIS','RHUJELEN C. TORMIS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RICARDO_ALBERIO','RICARDO ALBERIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RICHARD_FERNANDEZ','RICHARD FERNANDEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RITCHIE_C_BASTE','RITCHIE C. BASTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RIZA_B_RUIZ','RIZA B. RUIZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RIZA_M_DIAZ','RIZA M. DIAZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RIZA_M_POLINAR','RIZA M. POLINAR','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RIZAL_JR_H_CAPANGPANGAN','RIZAL JR. H. CAPANGPANGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROBERT_BRIONES','ROBERT BRIONES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROBERT_JAME_P_ARENO','ROBERT JAME P. ARENO','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROBERT_REY_ALABA','ROBERT REY ALABA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROBERTO_AGUIPO','ROBERTO AGUIPO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROCEL_C_HOMEO','ROCEL C. HOMEO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODEL_YBANEZ','RODEL YBANEZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODELIA_G_MIPARANUM','RODELIA G. MIPARANUM','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODILO_RAMOS','RODILO RAMOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODJIT_S_PLETE','RODJIT S. PLETE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODNEY_ZEUS_LESCANO','RODNEY ZEUS LESCANO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODOLFO_A_PAMEN','RODOLFO A. PAMEN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODRIGO_L_CAWAGAS_II','RODRIGO L. CAWAGAS ,II','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RODSAL_BARIGA','RODSAL BARIGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROED_SIBANTA','ROED SIBANTA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROGELIO_ORBANEJA_JR','ROGELIO ORBANEJA JR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROGELYN_T_ESPINOSA','ROGELYN T. ESPINOSA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROJANE_L_DAITE','ROJANE L. DAITE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROLAND_KYLLE_H_VILLACRUSIS','ROLAND KYLLE H. VILLACRUSIS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROLANDO_ALFAREO','ROLANDO ALFAREO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROLANDO_P_ENGBINO','ROLANDO P. ENGBINO','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROLLY_AMANTE','ROLLY AMANTE','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROMAR_JANE_LUPOGAN','ROMAR JANE LUPOGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROMEO_ENERO_CATAYAS','ROMEO ENERO CATAYAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROMEO_GONZAGA_JR','ROMEO GONZAGA JR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROMMEL_P_LUMASAG','ROMMEL P. LUMASAG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROMMEL_T_GARCIA','ROMMEL T. GARCIA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROMULO_D_RACHO','ROMULO D. RACHO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONALD_CALAGO','RONALD CALAGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONALD_JAMES_B_BUSIG','RONALD JAMES B. BUSIG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONALD_M_PADERANGA','RONALD M. PADERANGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONALD_T_RICO','RONALD T. RICO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONALD_T_TABANAO','RONALD T. TABANAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONARD_T_MA_AGHOP','RONARD T. MA-AGHOP','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONEL_H_NACARIO','RONEL H. NACARIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONEL_LARGO','RONEL LARGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONNEL_NORTIGA','RONNEL NORTIGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONNEL_P_JAGONOS','RONNEL P. JAGONOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RONNIE_O_NAPULAG','RONNIE O. NAPULAG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSALIE_G_MAGHINAY','ROSALIE G. MAGHINAY','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSALINA_G_TAPAYAN','ROSALINA G. TAPAYAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSAMATER_ORIBADO','ROSAMATER ORIBADO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSE_ANN_P_JIMENA','ROSE ANN P. JIMENA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSELA_L_BUCIO','ROSELA L. BUCIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSELA_SUAREZ','ROSELA SUAREZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSEMARIE_D_MAG_ASO','ROSEMARIE D. MAG-ASO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSEMARIE_L_CAFE','ROSEMARIE L. CAFE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROSETIN_MARICAR_T_PONGASE','ROSETIN MARICAR T. PONGASE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROVELYN_JOY_HUMOL','ROVELYN JOY HUMOL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROVYNNA_ELLAIZA_E_MONTEJO','ROVYNNA ELLAIZA E. MONTEJO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROWEL_CASTANEDA','ROWEL CASTANEDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROWELYN_Y_JUSTINO','ROWELYN Y. JUSTINO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROWIL_VALLICER','ROWIL VALLICER','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROWILEEN_N_DUMAIL','ROWILEEN N. DUMAIL','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ROXIE_R_ABORDO','ROXIE R. ABORDO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUBEN_J_REPONTE','RUBEN J. REPONTE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUBY_CONCHA','RUBY CONCHA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUBY_S_BUGAHOD','RUBY S. BUGAHOD','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUDELYN_L_MALAGANTE','RUDELYN L. MALAGANTE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUDOLPH_PRUDENCE_S_PAILAGO','RUDOLPH PRUDENCE S. PAILAGO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUEL_V_SEMEON','RUEL V. SEMEON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RUTHER_Y_IBANEZ','RUTHER Y. IBAÑEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RYAN_B_HANIBO','RYAN B. HANIBO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RYAN_T_JANDAYAN','RYAN T. JANDAYAN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('RYLLE_ADRIAN_BARONA','RYLLE ADRIAN BARONA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SALOMON_GULLEZ','SALOMON GULLEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SAMUEL_V_RETIZA','SAMUEL V. RETIZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SAYBIE_C_BUGASH','SAYBIE C. BUGASH','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SEMPLECIO_T_CAGNA_AN','SEMPLECIO T. CAGNA-AN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SERZON_JANE_C_GANZALAO','SERZON JANE C. GANZALAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHALLEMER_AMOR_R_GALENZOGA','SHALLEMER AMOR R. GALENZOGA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHAN_RALPH_R_MATEO','SHAN RALPH R. MATEO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHARA_J_T_VASQUEZ','SHARA J. T. VASQUEZ','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHARMINE_O_SARNO','SHARMINE O. SARNO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHARON_ROSE_B_AGUSTIN','SHARON ROSE B. AGUSTIN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHEILA_MAE_E_LINDERO','SHEILA MAE E. LINDERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHEILA_MAY_V_RIVERA','SHEILA MAY V. RIVERA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHEILA_O_AGATINTO','SHEILA O. AGATINTO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHELLA_MAY_L_DANDAN','SHELLA MAY L. DANDAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHEM_B_MAGBUTONG','SHEM B. MAGBUTONG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERIFF_BRYANT_C_TROCIO','SHERIFF BRYANT C. TROCIO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERLYN_S_MAPALO','SHERLYN S. MAPALO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERRIE_MARRIANNE_CALOGCOGAN','SHERRIE MARRIANNE CALOGCOGAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERWIN_A_GALIMBA','SHERWIN A. GALIMBA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERWIN_GALIMBA','SHERWIN GALIMBA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERWIN_KRISTOFFER_TUYCO','SHERWIN KRISTOFFER TUYCO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERYL_A_CAYAO','SHERYL A. CAYAO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERYL_B_AMBROCIO','SHERYL B. AMBROCIO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHERYL_S_TUYOGON','SHERYL S. TUYOGON','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHIELA_L_CAMBONGGA','SHIELA L. CAMBONGGA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHIELA_MAE_C_SALAZAR','SHIELA MAE C. SALAZAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHIELOU_RHEA_C_LINDONG','SHIELOU RHEA C. LINDONG','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHIELU_JAN_MAE_H_PINO','SHIELU JAN MAE H. PINO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SHIRLEY_I_FRONDOZA','SHIRLEY I. FRONDOZA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SOSIMO_D_TAUTHO','SOSIMO D. TAUTHO','DSC')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('STEPHEN_OCZON','STEPHEN OCZON','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('SYRILL_JOHN_A_SOLIS','SYRILL JOHN A. SOLIS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('THEA_CYBELE_C_GECALE','THEA CYBELE C. GECALE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('THERESA_A_EPO','THERESA A. EPO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('THRESCIA_MAE_B_CENAS','THRESCIA MAE B. CENAS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('TRICIA_MAY_B_SUAYBAGUIO','TRICIA MAY B. SUAYBAGUIO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('TUMMY_JUN_F_SABUERO','TUMMY JUN F. SABUERO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VANESSA_M_MUCA','VANESSA M. MUCA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VENJE_E_DURO','VENJE E. DURO','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VENUS_B_CORTES','VENUS B. CORTES','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VENUS_G_DUMALAG','VENUS G. DUMALAG','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VERDALIZLE_C_ARAIS','VERDALIZLE C. ARAIS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VERNA_ALBINO','VERNA ALBINO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VICTORIANO_JACALAN','VICTORIANO JACALAN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VINCE_C_PARCON','VINCE C. PARCON','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VINCE_IAN_A_ABANALES','VINCE IAN A. ABANALES','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VINCENT_PAUL_POLINAR','VINCENT PAUL POLINAR','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('VIVIAN_ALCAZAREN','VIVIAN ALCAZAREN','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('WENDEL_B_SANCHEZ','WENDEL B. SANCHEZ','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('WENDEL_CRUDA','WENDEL CRUDA','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('WILCOR_C_TAYONE','WILCOR C. TAYONE','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('WILFREDO_P_TAKASAN','WILFREDO P. TAKASAN','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('WINZELL_S_FELONGCO','WINZELL S. FELONGCO','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('XYLENE_P_CANTOS','XYLENE P. CANTOS','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('YIENNA_BALE','YIENNA BALE','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('YOLANDA_G_LADAGA','YOLANDA G. LADAGA','TWG')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('YULBRAINER_BOISER','YULBRAINER BOISER','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('YVONNE_G_AMAHIT','YVONNE G. AMAHIT','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ZAFIRA_UGAPANG','ZAFIRA UGAPANG','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);
INSERT INTO pmms_people (person_key,full_name,source_flags)
VALUES ('ZALDE_J_RADJAC','ZALDE J. RADJAC','TM_TO')
ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), source_flags=VALUES(source_flags);

INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('ARCHERY','Archery','regular','ARCHERY','confirmed',1)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('ARNIS','Arnis','regular','ARNIS','confirmed',2)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('ATHLETICS','Athletics','regular','ATHLETICS','confirmed',3)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('BADMINTON','Badminton','regular','BADMINTON','confirmed',4)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('BASEBALL','Baseball','regular','BASEBALL','confirmed',5)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('BASKETBALL','Basketball','regular','BASKETBALL','confirmed',6)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('BILLIARDS','Billiards','regular','BILLIARDS','confirmed',7)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('BOXING','Boxing','regular','BOXING','confirmed',8)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('CHESS','Chess','regular','CHESS','confirmed',9)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('DANCESPORTS','Dancesports','regular','DANCESPORTS','confirmed',10)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('FOOTBALL','Football','regular','FOOTBALL','confirmed',11)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('FUTSAL','Futsal','regular','FUTSAL','confirmed',12)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('GYMNASTICS','Gymnastics','regular','GYMNASTICS','confirmed',13)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('PENCAK_SILAT','Pencak Silat','regular','PENCAK SILAT','confirmed',14)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('SEPAK_TAKRAW','Sepak Takraw','regular','SEPAK TAKRAW','confirmed',15)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('SOFTBALL','Softball','regular','SOFTBALL','confirmed',16)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('SWIMMING','Swimming','regular','SWIMMING','confirmed',17)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('TAEKWONDO','Taekwondo','regular','TAEKWONDO','confirmed',18)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('TABLE_TENNIS','Table Tennis','regular','TABLE TENNIS','confirmed',19)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('TENNIS','Tennis','regular','TENNIS','confirmed',20)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('VOLLEYBALL','Volleyball','regular','VOLLEYBALL','confirmed',21)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('WEIGHTLIFTING_KICKBOXING','Weightlifting / Kickboxing','regular','WEIGHTLIFTING /KICKBOXING','needs_confirmation',22)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('WRESTLING','Wrestling','regular','WRESTLING','confirmed',23)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('WUSHU','Wushu','regular','WUSHU','confirmed',24)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('BOCCE','Bocce','paragames','BOCCE (PARAGAMES)','confirmed',25)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('GOALBALL','Goalball','paragames','GOAL BALL (PARAGAMES)','confirmed',26)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('PARA_ATHLETICS','Para Athletics','paragames','ATHLETICS (PARAGAMES)','confirmed',27)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);
INSERT INTO pmms_sports (code,name,classification,source_label,configuration_status,display_order)
VALUES ('PARA_SWIMMING','Para Swimming','paragames','SWIMMING (PARAGAMES)','confirmed',28)
ON DUPLICATE KEY UPDATE name=VALUES(name), classification=VALUES(classification), source_label=VALUES(source_label), configuration_status=VALUES(configuration_status), display_order=VALUES(display_order);

INSERT INTO pmms_meet_sports (meet_id,sport_id)
SELECT m.id,s.id FROM pmms_meets m JOIN pmms_sports s ON 1=1
WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE is_active=1;

INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'TOP_MANAGEMENT','Top Management','Provides executive direction, policy oversight, approval, and escalation support for the Provincial Meet.',1
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'INCIDENT_COMMAND','Incident Command','Coordinates overall incident command, emergency decision-making, and cross-team response during meet operations.',2
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'SPORTS_LINES_UP_AND_PLACEMENT','Sports Lines Up and Placement','Coordinates sports line-up, placements, event sequencing, and related competition deployment.',3
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'SECRETARIAT','Secretariat','Provides central administrative documentation, records, communications, minutes, and official secretariat support.',4
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'GRIEVANCE','Grievance','Receives, documents, routes, and coordinates resolution of grievances and formal concerns.',5
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'OPENING_AND_CLOSING_PROGRAM','Opening and Closing Program','Plans and coordinates opening and closing ceremonies, program flow, participants, and related logistics.',6
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'DECORATION','Decoration','Plans and manages approved venue/program decorations and visual preparation.',7
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'PLAYING_VENUE','Playing Venue','Coordinates venue readiness, facility assignments, venue concerns, and operational requirements.',8
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'FOOD_MEALS','Food / Meals','Coordinates meal schedules, distribution information, and food-related operational requirements.',9
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'USHERETTES','Usherettes','Provides guest assistance, ushering, seating guidance, and front-of-house support during official activities.',10
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'PEACE_AND_SECURITY','Peace and Security','Coordinates safety, access control, crowd management, security concerns, and liaison with security personnel.',11
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'BILLETING','Billeting','Coordinates delegation accommodation, host-school billeting, capacity, assignments, and billeting concerns.',12
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'FINANCE','Finance','Coordinates approved financial documentation, disbursement support, and finance-related meet records within authorized processes.',13
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'LOGISTICS','Logistics','Coordinates equipment, supplies, movement, staging, and operational logistics across meet activities.',14
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'MEDICAL','Medical','Provides medical evaluation, medical clearance, first-aid/response coordination, referral, and health-related event support.',15
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE','Learners Rights and Protection Desk Committee','Supports learner protection, safeguarding, rights concerns, referral, and appropriate response during the meet.',16
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'QUALITY_ASSURANCE_MONITORING_EVALUATION','Quality Assurance, Monitoring & Evaluation','Monitors implementation quality, compliance, operational performance, and post-activity evaluation.',17
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'WATER_LIGHT_SANITATION','Water, Light & Sanitation','Coordinates water, electrical/light readiness, sanitation, and related facility concerns.',18
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'INFORMATION','Information','Coordinates official information, public advisories, approved communication, and information dissemination.',19
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'CLEAN_GREEN','Clean & Green','Coordinates cleanliness, waste management support, venue environmental readiness, and clean-up activities.',20
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'EVENT_SECRETARIAT','Event Secretariat','Provides sport/event-level documentation support, forms, records routing, and coordination with event officials.',21
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'SUPPORT_STAFF','Support Staff','Provides general operational support as assigned by meet management.',22
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'DSAC','DSAC','Evaluates and validates athlete profile, eligibility documents, school/delegation consistency, category qualifications, and athlete eligibility status.',23
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'ANNOUNCERS','Announcers','Provides approved event announcements, public-address support, and official program/event information.',24
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);
INSERT INTO pmms_twg_units (meet_id,code,name,description,display_order)
SELECT m.id,'KITCHEN_PERSONNEL','Kitchen Personnel','Supports food preparation and kitchen operations under the Food/Meals function.',25
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), display_order=VALUES(display_order);

INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Over -all Chairperson',1
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='PHOEBE_GAY_L_REFAMONTE'
WHERE tu.code='TOP_MANAGEMENT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Deputy Chairperson',2
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ANGELITO_D_CARREON'
WHERE tu.code='TOP_MANAGEMENT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Incident Commander',3
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ARLYN_B_LIM'
WHERE tu.code='INCIDENT_COMMAND';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Deputy Incident Commander',4
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOEL_S_JUMALON'
WHERE tu.code='INCIDENT_COMMAND';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',5
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ALLAN_R_GUERTA'
WHERE tu.code='SPORTS_LINES_UP_AND_PLACEMENT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',6
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ROSALIE_G_MAGHINAY'
WHERE tu.code='SPORTS_LINES_UP_AND_PLACEMENT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',7
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ANDY_P_CABODOC'
WHERE tu.code='SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',8
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ARNIEL_CASTILLO'
WHERE tu.code='SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',9
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ATTY_ARMANDO_DALISAY_JR'
WHERE tu.code='GRIEVANCE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',10
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='NORBERTO_S_MANLANGIT'
WHERE tu.code='GRIEVANCE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',11
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MAROJA_R_BELISARIO'
WHERE tu.code='OPENING_AND_CLOSING_PROGRAM';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',12
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SHIELA_L_CAMBONGGA'
WHERE tu.code='OPENING_AND_CLOSING_PROGRAM';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',13
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='HILDA_OPENA'
WHERE tu.code='DECORATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',14
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='EMMANUAL_CLARION'
WHERE tu.code='DECORATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',15
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RENATO_PACPAKIN'
WHERE tu.code='PLAYING_VENUE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',16
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARIA_BELLA_ALVAREZ'
WHERE tu.code='PLAYING_VENUE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',17
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='NOEMI_P_CANALES'
WHERE tu.code='FOOD_MEALS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',18
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARY_ANN_LAGUITAO'
WHERE tu.code='FOOD_MEALS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',19
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='GRACE_D_PONTILLAS'
WHERE tu.code='USHERETTES';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',20
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='NOHARA_O_PINUTE'
WHERE tu.code='USHERETTES';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',21
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MERCY_GIFT_M_SALINAS'
WHERE tu.code='USHERETTES';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',22
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RUBEN_J_REPONTE'
WHERE tu.code='PEACE_AND_SECURITY';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',23
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ANALYN_M_LORETO'
WHERE tu.code='PEACE_AND_SECURITY';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',24
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MAROJA_R_BELISARIO'
WHERE tu.code='BILLETING';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',NULL
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ALL_HOST_SCHOOL_HEAD'
WHERE tu.code='BILLETING';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',25
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DEXTER_MATT_LEVI_BELLO'
WHERE tu.code='FINANCE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',26
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RANDY_CANEDA'
WHERE tu.code='FINANCE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',27
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='LOWEI_JANUYAN'
WHERE tu.code='FINANCE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',28
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RODEL_YBANEZ'
WHERE tu.code='FINANCE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',29
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARION_ESPINOSA'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',30
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ISIDRO_REFAMONTE_JR'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',31
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JUNARD_ALIDRO'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',32
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='NETHEL_RUBY_REPONTE'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',33
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOHN_PETER_O_OCLEDA'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',34
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RODOLFO_A_PAMEN'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',35
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='BALDWIN_R_BINGIL'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',36
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JODEL_ABENOJA'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',37
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JESSIE_L_REFAMONTE'
WHERE tu.code='LOGISTICS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',38
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DR_GRINGO_JOHN_F_PELAEZ'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',39
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DR_NIKKA_CARLA_R_ALFARO'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',40
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='GERRYPHER_VIERNES'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',41
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='CHERRY_ANN_Y_PANIO'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',42
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MAGDALINA_GALIMBA'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',43
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DONNA_KENNETH_NISTAL'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',44
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='GODFREE_ESPERO'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',45
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SHERWIN_KRISTOFFER_TUYCO'
WHERE tu.code='MEDICAL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',46
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARGIRIE_ASUQUE'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',47
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='FRYZIEL_RHEENAMAE_PACPAKIN'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',48
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='LLANE_M_ALBINA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',49
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JONALYN_M_PAVIA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',50
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='YOLANDA_G_LADAGA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',51
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JENNEYBELLE_J_JACINTO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',52
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JENELYN_H_MARBEBE'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',53
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JAMARISON_L_BALBERO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',54
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JEEFHIL_C_OBUGA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',55
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='FRINE_G_REYES'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',56
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='IRESH_JOY_L_ABON'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',57
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARIA_DIONESSA_A_COYOCA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',58
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RIZA_M_POLINAR'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',59
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='AILYN_B_PUNO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',60
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='FERDINAND_O_VELUZ'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',61
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='CHRIZELL_L_BALILI'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',62
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SHERWIN_GALIMBA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',63
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MAEZY_ALBOROTO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',64
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOSE_R_VILLANUEVA_JR'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',65
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RYAN_T_JANDAYAN'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',66
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DIOSANNE_M_BERANA'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',67
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MERLIE_MARTIR'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',68
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MINERVA_C_BUENO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',69
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ALMA_S_LOREQUE'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',70
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='NENETH_T_SILVARES'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',71
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='VENUS_B_CORTES'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',72
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JACKY_R_MAMACOS'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',73
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MICHELLE_Y_GAMIL'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',74
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='FAITH_GRACE_MANZANARES'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',75
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='HELEN_B_TABUSO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',76
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARICEL_G_MANALO'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',77
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DEBBIE_RANALAN'
WHERE tu.code='LEARNERS_RIGHTS_AND_PROTECTION_DESK_COMMITTEE';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',78
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOSEPH_CORPUZ'
WHERE tu.code='QUALITY_ASSURANCE_MONITORING_EVALUATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',79
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='REY_SAROMINES'
WHERE tu.code='QUALITY_ASSURANCE_MONITORING_EVALUATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',80
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARNELY_JANE_BERNAL'
WHERE tu.code='WATER_LIGHT_SANITATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',81
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ENGR_RANALYN_BALONG'
WHERE tu.code='WATER_LIGHT_SANITATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',82
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='LYZLE_C_ABREGANA'
WHERE tu.code='WATER_LIGHT_SANITATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',83
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='WILFREDO_P_TAKASAN'
WHERE tu.code='INFORMATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',84
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='BOB_DYLAN_MILABAT'
WHERE tu.code='INFORMATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',85
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MEDARD_T_APIT'
WHERE tu.code='INFORMATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',86
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='HARLY_G_GINGO'
WHERE tu.code='INFORMATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',87
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SHERIFF_BRYANT_C_TROCIO'
WHERE tu.code='INFORMATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',88
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ANABEL_P_GUIPITACIO'
WHERE tu.code='INFORMATION';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',89
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ALVIN_ESCOBAR'
WHERE tu.code='CLEAN_GREEN';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',90
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='PEPITO_T_VILLARREIZ'
WHERE tu.code='CLEAN_GREEN';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',91
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOAN_A_ETURMA'
WHERE tu.code='EVENT_SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',92
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='WILCOR_C_TAYONE'
WHERE tu.code='EVENT_SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',93
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ARCHIMEDED_M_TAYONE'
WHERE tu.code='EVENT_SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',94
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DAVE_GALES'
WHERE tu.code='EVENT_SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',95
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='FE_DELOSTRICO'
WHERE tu.code='EVENT_SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',96
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOHNWARD_AMARANTO'
WHERE tu.code='EVENT_SECRETARIAT';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',97
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='WENDEL_B_SANCHEZ'
WHERE tu.code='SUPPORT_STAFF';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',98
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='HAIDE_LAURON'
WHERE tu.code='SUPPORT_STAFF';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',99
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='WILFREDO_P_TAKASAN'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Co-Chairperson',100
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ROXIE_R_ABORDO'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',101
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MICHAEL_JAY_R_L_ESPADILLA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',102
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOHN_EARL_B_TUBIANOSA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',103
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='IVY_MARIE_A_DECATORIA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',104
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='CELERINA_V_MINOZA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',105
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JAN_MARK_D_MANGUILIMUTAN'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',106
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RHODORA_MAY_S_GARCIA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',107
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARISSA_I_LIWAYA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',108
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JEA_AN_B_ENAGA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',109
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SHERYL_S_TUYOGON'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',110
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JENNIE_D_CALAPE'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',111
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SAYBIE_C_BUGASH'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',112
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='VINCE_C_PARCON'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',113
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='VENUS_G_DUMALAG'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',114
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ANNIE_F_VALDERAMA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',115
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JERIC_B_HERBITO'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',116
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='EVELYN_N_YU'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',117
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='BEVERLY_ANN_C_PENARANDA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',118
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='LEONOR_E_MELLIVO'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',119
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JEANETTE_M_PACALDO'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',120
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MAYBEL_H_STA_IGLESIA'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',121
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='KENNETH_E_CABALAN'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',122
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='PEVY_V_DELA_CRUZ'
WHERE tu.code='DSAC';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Chairperson',123
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ANDY_P_CABODOC'
WHERE tu.code='ANNOUNCERS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',124
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DEXTER_CEQUINA'
WHERE tu.code='ANNOUNCERS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',125
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DONNA_DEE_T_BOLOFER'
WHERE tu.code='ANNOUNCERS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',126
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='REWYN_C_MANUEL'
WHERE tu.code='ANNOUNCERS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',127
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JUDYLAND_YU'
WHERE tu.code='ANNOUNCERS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',128
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='SHIELU_JAN_MAE_H_PINO'
WHERE tu.code='ANNOUNCERS';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Members',129
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='REVICK_P_BERMOY'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',130
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='TRICIA_MAY_B_SUAYBAGUIO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',131
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JEHPIE_B_MABIDA'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',132
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='GLECEL_BEJOC'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',133
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='DIOVEROSE_E_CAMPANER'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',134
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JONARDO_ROBLE'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',135
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='CECILE_SEVILLANO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',136
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='JOJI_MARIE_C_BENITO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',137
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='RENANTE_R_JESURO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',138
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='VENJE_E_DURO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',139
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='IAN_JUNTILLA'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',140
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MARY_ROSE_A_DERANO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',141
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='MIRASOL_PANOY'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',142
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ALJUN_A_ABAPO'
WHERE tu.code='KITCHEN_PERSONNEL';
INSERT IGNORE INTO pmms_twg_memberships (twg_unit_id,person_id,role_title,source_sequence)
SELECT tu.id,p.id,'Member',143
FROM pmms_twg_units tu
JOIN pmms_meets m ON m.id=tu.meet_id AND m.code='DDOPAA-2026'
JOIN pmms_people p ON p.person_key='ROLANDO_ALFAREO'
WHERE tu.code='KITCHEN_PERSONNEL';

INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='COMPOSTELA'
JOIN pmms_school_districts sd ON sd.code='COMPOSTELA_EAST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='REYNALDO_M_CATIENZA'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='COMPOSTELA'
JOIN pmms_school_districts sd ON sd.code='COMPOSTELA_WEST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='AQUILINO_CAMUS'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='LAAK'
JOIN pmms_school_districts sd ON sd.code='LAAK_NORTH' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='EDGAR_B_CORTIDOR'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='LAAK'
JOIN pmms_school_districts sd ON sd.code='LAAK_SOUTH' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='DEXTER_C_HAO'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MACO'
JOIN pmms_school_districts sd ON sd.code='MACO_NORTH' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='JOSEPH_BILLIONES'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MACO'
JOIN pmms_school_districts sd ON sd.code='MACO_SOUTH' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='CERIAL_DALAOTA'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MABINI'
JOIN pmms_school_districts sd ON sd.code='MABINI' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='JUNMAR_A_GUISANG'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MAWAB'
JOIN pmms_school_districts sd ON sd.code='MAWAB' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='ROBERT_JAME_P_ARENO'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MONTEVISTA'
JOIN pmms_school_districts sd ON sd.code='MONTEVISTA' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='RENANTE_LIQUIT'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MARAGUSAN'
JOIN pmms_school_districts sd ON sd.code='MARAGUSAN_EAST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='SHIELOU_RHEA_C_LINDONG'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MARAGUSAN'
JOIN pmms_school_districts sd ON sd.code='MARAGUSAN_WEST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='MAR_ULYSSES_SOMBREO'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MONKAYO'
JOIN pmms_school_districts sd ON sd.code='MONKAYO_EAST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='DELSON_B_MUSCA'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='MONKAYO'
JOIN pmms_school_districts sd ON sd.code='MONKAYO_WEST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='ROLANDO_P_ENGBINO'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='NABUNTURAN'
JOIN pmms_school_districts sd ON sd.code='NABUNTURAN_EAST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='ROLLY_AMANTE'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='NABUNTURAN'
JOIN pmms_school_districts sd ON sd.code='NABUNTURAN_WEST' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='RHONNIE_JAY_C_ARADILLA'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='NEW BATAAN'
JOIN pmms_school_districts sd ON sd.code='NEW_BATAAN' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='ALDREN_DAYDAY'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='PANTUKAN'
JOIN pmms_school_districts sd ON sd.code='PANTUKAN_NORTH' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='SOSIMO_D_TAUTHO'
WHERE mt.code='DDOPAA-2026';
INSERT IGNORE INTO pmms_dsc_assignments (meet_id,municipality_id,school_district_id,person_id,is_lead)
SELECT mt.id,mu.id,sd.id,p.id,1
FROM pmms_meets mt
JOIN pmms_municipalities mu ON mu.code='PANTUKAN'
JOIN pmms_school_districts sd ON sd.code='PANTUKAN_SOUTH' AND sd.municipality_id=mu.id
JOIN pmms_people p ON p.person_key='MARLOU_LUMAKANG'
WHERE mt.code='DDOPAA-2026';

INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,1,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='SHELLA_MAY_L_DANDAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,2,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='IAN_P_TAPANAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT-TECHNICAL OFFICIAL',NULL,3,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='MONETTE_A_GAMUTAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,4,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='SEMPLECIO_T_CAGNA_AN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,5,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='JHONREY_B_PALUA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,6,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='CRISTITO_S_SUAN_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,7,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='ARZIEL_PAULINE_JACKIE_T_JAMORA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,8,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='GLORYFEL_B_MAMUSOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,9,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='JAYMES_BALALA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,10,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='AIRES_INSAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,11,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='VINCENT_PAUL_POLINAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,12,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='JESUS_SICAT_M_CATUGAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,13,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='VIVIAN_ALCAZAREN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,14,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='KRISTENE_NOVIE_M_PEREZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,15,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARCHERY'
JOIN pmms_people p ON p.person_key='JOEY_B_AMADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,16,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='LEONILO_S_TORREJAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,17,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='DOMINGO_B_MURILLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,18,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='JONNIFER_T_CHUA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT-TECHNICAL OFFICIAL',NULL,19,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='ARGIE_D_QUIRANTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,20,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='JOECIP_ANINON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,21,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='MAFER_C_POLISTICO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,22,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='MARY_ANN_C_AROCHA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,23,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='JANICE_ESCARPIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,24,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='ANABELLE_E_ALCOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,25,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='NOLE_VILLASON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,26,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='JEREME_SEVILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,27,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='RAYMOND_S_TURBELLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,28,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='ROWEL_CASTANEDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,29,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='FEBLOUT_TAGAWA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,30,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='ERWIN_C_ALQUIZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,31,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='SHEILA_MAE_E_LINDERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,32,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='ALMARIE_P_NAVARRE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,33,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='FRANCIS_LAGUNA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,34,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='ROSEMARIE_D_MAG_ASO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,35,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='PYKE_KUDERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,36,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='BONICA_CORSIGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,37,'LLAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='RODSAL_BARIGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,38,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ARNIS'
JOIN pmms_people p ON p.person_key='WENDEL_CRUDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER_TRACK','TOURNAMENT MANAGER- TRACK','Track',39,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JOEY_B_PAMPLONA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TOURNAMENT_MANAGER_FIELD','TOURNAMENT MANAGER- FIELD','Field',40,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MYLYN_D_GARCIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'ASSISTANT_TM_TRACK','ASSISTANT TM - TRACK','Track',41,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='HAIDE_G_LAURON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,42,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MELVIN_JADE_V_HUBAHIB';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,43,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JACKY_LYN_LACRE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,44,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JENNY_MAE_A_SABLON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,45,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RODNEY_ZEUS_LESCANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,46,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='NELSON_P_BAUDAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,47,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='CHARIZA_I_SAREN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,48,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RHOBELYN_P_SEMACIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,49,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JOSEPHINE_M_VILLAFUERTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,50,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='LYDEL_BRIAN_CANETE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,51,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MELISSA_SHEAREENE_M_MAYORMITA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,52,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RANDY_N_SIPSIP';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,53,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JESSIE_A_CENTILLAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,54,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MARJO_L_LABADLABAD';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,55,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JOHN_RUSSEL_B_CADUNGOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,56,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RAFAEL_NALANGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,57,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='NENITA_H_VALENCIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,58,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MARYJANE_M_CABEROS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,59,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='GERONIMO_B_GALANZA_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,60,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='NEACEL_D_JUNTILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,61,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ALBERT_S_VENTURA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,62,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='LEONILYN_KHRIS_L_ABARECIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,63,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='FERLY_FERNANDO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,64,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ANALOU_L_SIDAYON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,65,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ELIEMAR_JHONE_J_BAMBAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,66,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JOY_I_LUMOCSO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,67,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='SHARA_J_T_VASQUEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,68,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MERRY_JEAN_C_MEDRINA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,69,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ROBERTO_AGUIPO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,70,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MICHAEL_P_PACHECO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,71,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='THRESCIA_MAE_B_CENAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,72,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ERLEN_BANDIBAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,73,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='BRADLEY_VON_SEVILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,74,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RODELIA_G_MIPARANUM';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,75,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ANNA_MARIE_MANIQUEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,76,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RACHELLE_D_INTIG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,77,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='NECKLE_J_ARAIS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,78,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JEAN_J_BASSIG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,79,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='NOIME_C_CASTIL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,80,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JEAN_MAY_A_SABARITA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,81,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='REANE_F_LABANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,82,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MARLON_P_PIZARRAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,83,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='VANESSA_M_MUCA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,84,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ARMIL_GRAFANI';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,85,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JOMARISON_L_BALBERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,86,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JENELYN_H_MARBEBE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,87,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ZAFIRA_UGAPANG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,88,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='RAYMART_CELESTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,89,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='MELGIE_F_NAQUILA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,90,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='ROSE_ANN_P_JIMENA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,91,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='EVELYN_T_PADERANGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,92,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='NOREEN_AGOSTO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,93,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='DINDO_RABAGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,94,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='ATHLETICS'
JOIN pmms_people p ON p.person_key='JULIET_B_GONIDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,95,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='LOUIE_GOMEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,96,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='ERNESTO_GOMEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,97,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='MERIAM_R_ANDRADE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,98,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='EDUARD_C_CUASITO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,99,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='MARISSA_M_ORBINO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,100,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='ELEONOR_A_MUSCA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,101,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='GIMMA_FERNANDEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,102,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='KAIRA_GUIRIGAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,103,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='LEO_SANTO_O_ESCARPE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,104,'NABUNRUEAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='JULIUS_ERNEST_R_MIRANDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,105,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='MARIA_THESALONIKA_PRINCESS_ONA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,106,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='LORELIE_P_BARUIZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,107,'MACO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='KENNETH_SALAMANCA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,108,'MACO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='NOEL_BALMORIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,109,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='ANALYN_P_PANERIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,110,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='EMELY_B_RAMOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,111,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='JULIUS_TAPOT';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,112,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='PEDRO_CABAHUG_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,113,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='PRETS_A_DONGALO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL/ICT',NULL,114,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='BUENAFE_A_TEMAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,115,'COMPOSTELA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='CORRINE_GAIL_R_BULAC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,116,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='NOUMI_T_ANISCAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,117,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='MELCHI_FIGUEROA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,118,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BADMINTON'
JOIN pmms_people p ON p.person_key='JESSA_MAE_S_BRIGOLE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,119,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='JUMAR_IAN_L_TEVES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,120,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='GEOFREY_E_HADLOCON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,121,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='MARWIN_GIL_GOLEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,122,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='ABDUL_LAGUNGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,123,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='CHRIST_IAN_TIMBAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,124,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='JONATHAN_ECAT';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,125,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='BEBERLY_AMPARO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,126,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='JULANNE_F_GANZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,127,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='MICHAEL_FRANCES_PINSOY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,128,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='JOEY_CASILA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,129,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='RENANTE_GACMATAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,130,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='MICHAEL_ESTEBAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,131,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='MARIZ_PORRAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,132,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='VERNA_ALBINO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,133,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='JOEMON_SIBAYAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,134,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='DONNALYN_JOYCE_BRANDINO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,135,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='ARDIANO_DOYDOY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,136,'MACO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='ELPEDIO_JR_D_PERALTA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,137,'MACO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='RONALD_M_PADERANGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,138,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='ARA_A_QUILLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,139,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='MC_VEE_MANALILI';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,140,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASEBALL'
JOIN pmms_people p ON p.person_key='IVY_D_PARAJELE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,141,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RONALD_JAMES_B_BUSIG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,142,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='DENNIS_B_YUMANG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,143,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RENANTE_E_LIQUIT';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,144,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ZALDE_J_RADJAC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,145,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='DIOSDADO_P_VELENCIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,146,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='DOMINIC_B_YUMANG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,147,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='DREAMY_B_BABANTO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,148,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='CHRISTY_MALIG_ON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,149,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='CHRISTIAN_LI_MIRANDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,150,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='JIMMY_VARQUEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,151,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='LYZZA_P_BODIONGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,152,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='NASRODDIN_S_CABUGATAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,153,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='REY_CRIS_JUANILLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,154,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='MARILAG_O_BACALSO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,155,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='PHILIP_C_SERIDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,156,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='JIONE_V_CABACTULAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,157,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='CLARIBEL_S_ELING';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,158,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RENEGILDO_M_GOGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,159,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='JOEMAR_G_COMAWAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,160,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RHAPY_A_VIDAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,161,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ROMMEL_P_LUMASAG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,162,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RONALD_CALAGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,163,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ARCHIE_TANDING';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,164,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RODRIGO_L_CAWAGAS_II';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,165,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ARNIEL_C_MARVAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,166,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RYLLE_ADRIAN_BARONA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,167,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='DENNIS_L_PELIGRO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,168,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='BERNIE_M_NAQUILA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,169,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ALRICH_RYAN_TAGALOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,170,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='JESSON_S_PANERIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,171,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RONNEL_P_JAGONOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,172,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='JURLIE_M_MURING';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,173,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ELY_P_SAROMINES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,174,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ISIDRO_D_SADONGDONG_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,175,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='NONITO_MATA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,176,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RONEL_H_NACARIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,177,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RENE_JOSEPH_ABARQUEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,178,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='MARICHU_P_GARANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,179,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='HAIDEE_C_AMISTOSO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,180,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='SHAN_RALPH_R_MATEO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,181,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ROMMEL_T_GARCIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,182,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='CELINDO_T_RAUT_RAUT';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,183,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='LIRA_P_EYAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,184,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='SAMUEL_V_RETIZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,185,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='CHRISTOPHER_JANOHAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,186,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='LOVELY_M_ENANORIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,187,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='PABLITO_PARADIRO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,188,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ERNIE_LUNGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,189,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='NELSON_C_CANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,190,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ALDIN_M_NAQUILA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,191,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='RONALD_T_TABANAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,192,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='JONAH_JOFFA_FAITH_P_FUENTES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,193,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BASKETBALL'
JOIN pmms_people p ON p.person_key='ELIZABETH_JOY_F_POLINAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,194,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='CHERYL_MARIE_N_MACAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,195,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='JAPETH_SEBUALA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,196,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='JULIUS_RIGOR_DUQUE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,197,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='MARISSA_C_CASTORICO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,198,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='CECELIO_H_ENTECOSA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,199,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='JASSEN_MAE_L_GOMEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,200,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='KIM_B_BANOGBANOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,201,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='JENNILYN_T_SEROTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,202,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='ELJE_MAY_A_PENIDO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,203,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='ALME_A_TALANDRON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,204,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='RONARD_T_MA_AGHOP';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,205,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='GLENN_A_LIMOSNERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,206,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='CORY_CRISTY_P_EYAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,207,'LAAK',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='JOSIE_J_SABERON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,208,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='ELJUN_M_SUMAYANG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,209,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='FE_G_CONTIGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,210,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='ROVYNNA_ELLAIZA_E_MONTEJO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,211,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='CILITO_D_DIAYON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,212,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='MAE_ROSELLER_CHANCO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,213,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='BOYETH_G_RULIDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,214,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BILLIARDS'
JOIN pmms_people p ON p.person_key='JOCEL_P_LOPEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,215,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='JOHNIE_REY_T_ALCOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,216,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='RAMON_M_GENABE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,217,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='JOHN_DAVE_E_RUFANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,218,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='DANELO_A_CASE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,219,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='ROED_SIBANTA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,220,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='JORDAN_C_GOCHOCO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,221,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='EDEN_BERG_P_BONGCAC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,222,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='LAVILLA_L_WATE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,223,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='YULBRAINER_BOISER';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,224,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='MICHAEL_V_DELA_PENA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,225,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='ARIEL_ADOLFO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,226,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='BENJAMIN_O_MADRIAGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,227,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='CHRISTIAN_L_APAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,228,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='JUMAR_BASALO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,229,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='MEVYLIN_A_EMBAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,230,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='SERZON_JANE_C_GANZALAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,231,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='DANNY_BOY_C_ORILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,232,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='JULITO_JR_D_CASES_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,233,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='GLADYS_V_ESPINOSA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,234,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOXING'
JOIN pmms_people p ON p.person_key='HANNA_GRACE_MORDEN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,235,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='LEONARDO_M_CALOLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,236,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='JOE_ART_G_CAPATAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,237,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='JERRY_A_ESPINA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,238,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='MELVA_P_AMPILAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,239,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='ROMEO_GONZAGA_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,240,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='NERISSA_G_DAROLLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,241,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='RUTHER_Y_IBANEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,242,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='ACE_MARLO_A_CELADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,243,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='MARICEL_MERCADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,244,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='RITCHIE_C_BASTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,245,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='JERSON_YBANES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,246,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='SHEM_B_MAGBUTONG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,247,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='HENRY_JAN_C_DALAGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,248,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='JOHN_MAPELLE_LABITIGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,249,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='BEVERLY_C_REYES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,250,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='HARVEY_C_PIOQUINTO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,251,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='JOCEL_LABADAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,252,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='CARLO_ANTHONY_L_AMAC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,253,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='FE_B_FORMENTERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,254,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='NERISSA_G_HERNANDEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,255,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='GLEE_BLANCO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,256,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='ORLANDO_JR_M_ESCOLLAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,257,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='CHESS'
JOIN pmms_people p ON p.person_key='DAISY_M_BESAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,258,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='MILTON_L_BENILAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,259,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='TUMMY_JUN_F_SABUERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,260,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='CZARINE_MAE_F_INCLONAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT / TECHNICAL OFFICIAL',NULL,261,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='RAQUEL_M_MANUEL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,262,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='MARY_PAZ_V_PASTERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,263,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='SHARMINE_O_SARNO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,264,'COMPOSTELA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='JONALEE_J_TORLAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,265,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='LLOYD_D_NABASCA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,266,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='JAMES_M_MERANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,267,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='FRELIPACAR_B_GUCOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,268,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='HARVEY_NICKY_ESMEDIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,269,'MACO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='MEDELYN_TIMARIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,270,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='BELLA_JOSIE_T_LIMBAGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,271,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='JUN_REY_AGOY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,272,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='LINDSAY_CANEZO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,273,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='RENANTE_C_ALIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,274,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='DIANA_ROSE_GARBAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,275,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='JENNIFER_LAZO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,276,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='DANCESPORTS'
JOIN pmms_people p ON p.person_key='BLAIRE_IAN_V_ADARO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,277,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='EDISON_D_BAUTISTA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,278,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='REYMART_C_MALIG_ON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,279,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='LANI_M_GOGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,280,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='LEO_L_LARGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,281,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='JUADJIE_PARBA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,282,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='DEMETRIO_OPENA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,283,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='JAYSON_MARUNDAN_MANGAPAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,284,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='RANSOM_PINSOY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,285,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='JOSEPHINE_S_PERSEGAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,286,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='RONEL_LARGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,287,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='RIZA_B_RUIZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,288,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='ROSALINA_G_TAPAYAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,289,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='JOSHUA_MEPICO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,290,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='ROBERT_BRIONES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,291,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='NORLITO_LASAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,292,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='LHESTER_JAY_U_SALAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,293,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='MARJON_G_ABONERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,294,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='ANNALYN_ADVINCULA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,295,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='NEILMHAR_E_MAGALLANES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,296,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='ALRIN_TANUDRA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,297,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='ELEOMAR_CARDENIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,298,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='REYNALDO_CASTILLO_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,299,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FOOTBALL'
JOIN pmms_people p ON p.person_key='BON_JOIE_LIMBAGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,300,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='EDWIN_N_ESTANOL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL',NULL,301,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='ANGELO_C_GUTIERREZ_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,302,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='SHERWIN_A_GALIMBA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,303,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='JOYLEN_B_ELLAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,304,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='JOHANESS_N_ESCOVILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,305,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='DAFRHIL_ROSE_QUIJADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,306,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='JAY_R_C_ANG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,307,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='RYAN_B_HANIBO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,308,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='ALVIN_J_ABARECIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,309,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='RUBY_CONCHA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,310,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='VINCE_IAN_A_ABANALES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,311,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='RHUJELEN_C_TORMIS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,312,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='JANICE_D_TAASAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,313,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='HYUAN_C_MALUPAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,314,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='DOWEE_EVE_TAGUD';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,315,'LAAK',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='FUTSAL'
JOIN pmms_people p ON p.person_key='MARY_ROSE_CANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,316,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='GERARO_L_MONTANA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,317,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JOAN_A_BULAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,318,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JENNIFER_T_TEMPLONUEVO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,319,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='MARJORIE_L_GANADEN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,320,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='CHELSEA_FAYE_Y_ESTRERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,321,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='NORBERT_VINCENT_S_MIACO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,322,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='RIZAL_JR_H_CAPANGPANGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/ TOURNAMENT OFFICIAL (MAG)','MAG',323,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JOSHUA_YBANEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,324,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='XYLENE_P_CANTOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,325,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='MARY_JOY_C_MISSION';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,326,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='ELENA_M_ESTRADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT/TECHNICAL OFFICIAL (AERO)','AERO',327,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='RAINBOW_A_NAVASCA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,328,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='MARIFE_D_SHAGOL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,329,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='CHARMINE_A_CUNADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,330,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='MARY_JOAN_C_SENO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,331,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='KATLEEN_JOY_S_MARTINEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,332,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='ROGELYN_T_ESPINOSA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,333,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JEMAR_C_CATOC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,334,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='ARCELI_A_HUMOL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,335,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='CRISPIN_SAMOYA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,336,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='KRISTIA_REYNA_B_TABANYAG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,337,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='CHAZAIRA_MAE_M_TAPAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,338,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='IRISH_GENE_A_SALINAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,339,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JOANNIE_PALABIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,340,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='IRIS_B_LABADAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,341,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='ROWIL_VALLICER';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,342,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JOANNE_Y_SALVADOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,343,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='YIENNA_BALE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,344,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='GREZEL_C_CASTRO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,345,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='MELTHER_FE_O_PADILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,346,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='JESSA_L_COLARES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,347,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='MARY_JOY_NAQUILA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,348,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GYMNASTICS'
JOIN pmms_people p ON p.person_key='KRISTINE_MAE_OCHEA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,349,'COMPOSTELA-WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='JAY_A_CASAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),NULL,'TOURNAMENT_SECRETARY','SECRETARY',NULL,350,'MONKAYO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='NONA_BAE_MANUDSOD';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,351,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='ARGIE_BILANDRES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,352,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='JOKO_FRITS_B_DIAZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,353,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='SHIELA_MAE_C_SALAZAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,354,'COMPOSTELA-EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='JAQUELYN_V_PASTOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,355,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='JOHANNE_PRINCESS_A_WAGAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,356,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='GRACE_P_ALURA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,357,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='JAY_NINO_ROA_MAQUILAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,358,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PENCAK_SILAT'
JOIN pmms_people p ON p.person_key='RESHELLE_MAY_AYE_H_TUBAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,359,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JOVANNI_A_LUSICA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,360,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JERSON_O_RINGCONADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,361,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JOHNBERT_F_INCLONAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','TOURNAMENT SECRETARY/ICT',NULL,362,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='NELIA_R_SANCHEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','TOURNAMENT SECRETARY/ICT',NULL,363,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='HONEYLYN_C_JAINAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,364,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MERRY_CRIS_D_BATUCAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,365,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JULUIS_BALANSAG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,366,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='KEVIN_BUL_AN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,367,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MARIVIC_R_TULO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,368,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='KIM_CHRISTOPHER_R_NIALA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,369,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MIRA_N_ARRANGUEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,370,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='KRIS_D_BARNIDO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,371,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='GRACE_MAE_T_TORREON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,372,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MARIO_ALUAD_II';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,373,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JOSEPH_M_MARTOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,374,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JED_LUCKY_S_RACHO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,375,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='SHARON_ROSE_B_AGUSTIN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','TOURNAMENT SECRETARY/ICT',NULL,376,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MARITES_A_MALDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,377,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='ALOHA_FE_V_BATAR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,378,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JASMIN_P_PASCAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,379,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JOHN_PAUL_J_QUIBAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,380,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='SALOMON_GULLEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','TOURNAMENT SECRETARY/ICT',NULL,381,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JULIBETH_P_BOBO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,382,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MICHAEL_KEMS_Z_COLEGADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,383,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JOVAR_T_SAGING';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,384,'LAAK',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='RENE_L_LEGARA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,385,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='ANNABILLE_B_BASTASA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,386,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='KEN_CLARK_PUGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,387,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JOCELYN_D_MOLERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,388,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='EDWARD_S_YOUNG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,389,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='ALBIN_LACABA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,390,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MAIZA_D_OPISAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,391,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JESTER_PESCADERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,392,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='KJAY_D_ARO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,393,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='FREEDETEZ_P_ENICUELA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,394,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='CHRISTOPHER_A_LASARTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,395,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='ROVELYN_JOY_HUMOL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,396,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='LYKA_PATULOT';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,397,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JASIEL_M_MARIANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,398,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='JESLIE_O_TIANZON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,399,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='MARVI_S_ESPENA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,400,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='RUDELYN_L_MALAGANTE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,401,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SEPAK_TAKRAW'
JOIN pmms_people p ON p.person_key='DAISY_MAE_DIAN_P_ABANIZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,402,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='ROSEMARIE_L_CAFE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,403,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='ALEX_G_BARREDO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','Technical Official',NULL,404,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='JONAS_T_BATONGHINOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','Tournament Secretary/ICT',NULL,405,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='THEA_CYBELE_C_GECALE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,406,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='ARTCHELL_M_REYES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,407,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='NORLYN_JANE_M_ESPINOSA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,408,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='NIEL_MAR_L_CORPUZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,409,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='PRINCESS_B_DAGONDON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,410,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='MELISSA_T_SELEGENCIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,411,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='MARK_AUBREY_P_POSADAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,412,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='SHERYL_A_CAYAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,413,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='JOYCEE_MAE_CALDITO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,414,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='MILDRED_COMODA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,415,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='IVY_CLAIRE_SALINAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,416,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='LIBENLY_E_CASTILLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,417,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='CHARISSE_MAY_Z_SABAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,418,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='RAQUEL_H_SOTELO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,419,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='ROJANE_L_DAITE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,420,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='CHERRY_ANN_B_RIVERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,421,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='LUDWIG_VON_C_BRAGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,422,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='GALDYS_LAUSA_VIRTUDAZO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,423,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SOFTBALL'
JOIN pmms_people p ON p.person_key='ROWELYN_Y_JUSTINO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,424,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JEIRMANE_P_DAHAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,425,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='MARLON_G_PLAZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,426,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='SHIRLEY_I_FRONDOZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,427,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='SHEILA_O_AGATINTO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,428,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JESTONI_A_SAMPUTON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,429,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JENNIFER_N_JAMAROLIN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,430,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='GEMMA_I_PAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,431,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='MORAIDA_P_SALAPANTAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,432,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JANINE_J_OLIVA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','Tournament Secretary/ICT',NULL,433,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='CLAIRE_E_QUIJANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,434,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='RONNIE_O_NAPULAG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,435,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='GESTY_BUGHAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,436,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='NELSON_CATIPAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,437,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='GERALDINE_CALESA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,438,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='MARJORIE_ORAIS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,439,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ANALYN_M_SANGUINZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,440,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='RANDY_M_RALLOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,441,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='MARY_ROSE_AMORA_QUEBEC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,442,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ANALISA_P_GLORIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,443,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ACE_DAVE_D_CANE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,444,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ELYN_MARIE_D_SARAEL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,445,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JANICE_S_CAPUYAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,446,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='FATIMA_C_JALANE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,447,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JOHNY_A_SARAEL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,448,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='CHARLITO_REYES_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,449,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ESHUEL_C_GIGARE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,450,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='RICARDO_ALBERIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,451,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='JELYN_M_DIFUNTORUM';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,452,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ANALIZA_R_GLINOGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,453,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='LIOBA_C_LINDONG_II';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,454,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='PELBERT_JAMES_DURADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,455,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='SWIMMING'
JOIN pmms_people p ON p.person_key='ALEXANDER_M_LAGARE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,456,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='JOSETTE_ASILO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,457,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='THERESA_A_EPO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,458,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='HONEY_BETH_C_OPPUS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,459,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='CYRIL_B_UNGGOY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,460,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='JAMES_V_ROSALINDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,461,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='ROLAND_KYLLE_H_VILLACRUSIS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,462,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='SHALLEMER_AMOR_R_GALENZOGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,463,'NABUNTURAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='JAYSON_B_SALILI';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,464,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='REYNAN_LIBRETA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,465,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='VERDALIZLE_C_ARAIS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,466,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='JESLY_A_MAGNO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL / ICT',NULL,467,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='LEO_REY_CAGAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,468,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='NILDA_N_SENO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,469,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='JOHN_MARK_MANGARON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,470,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='CLEFFER_S_GARBAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,471,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='CARLO_TUTOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,472,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='JHEA_MAE_P_DAGAME';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,473,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='ANGELBERT_DOYOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,474,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TAEKWONDO'
JOIN pmms_people p ON p.person_key='NILIETA_MACALANGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,475,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='FILTRICIO_A_SALINAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,476,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='RUEL_V_SEMEON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'ASSISTANT_TOURNAMENT_MANAGER','ASST. TOURNAMENT MANAGER',NULL,477,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='JO_MARIE_M_VILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,478,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='ANABEL_C_BAYLON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,479,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='DINA_MARIECOR_P_MOJADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,480,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='ROWILEEN_N_DUMAIL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,481,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='MARRY_ANN_PAULINES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,482,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='DOREEN_JOY_D_GLINOGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,483,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='MAXWELL_ZOILON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,484,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='ROSELA_L_BUCIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,485,'MONKAYO',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='GUILLERMO_AYALA_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,486,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='SHERLYN_S_MAPALO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,487,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='LOURENCE_U_FEBRIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,488,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='ELVIE_JOY_T_SERIDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,489,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='LEAHNA_GEM_R_JUBANE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,490,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='LOUVIEN_DEE_L_IBARRA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,491,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='YVONNE_G_AMAHIT';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,492,'NABUNTURAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='JONATHAN_A_URGEL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,493,'NABUNTURAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='ANACEL_A_NACARIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,494,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='LINDY_LOU_DUG_OY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,495,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='JAYVEE_T_CRUDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,496,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='JOVER_L_BASTASA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,497,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='JANNEL_P_CALOGMOC';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,498,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='LEO_MARLO_P_RAMOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,499,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='FROIL_BEN_I_PELIGRINO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,500,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='RODJIT_S_PLETE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,501,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='LOUWELL_ZAL_C_ALMILLA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,502,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='MICHEAL_JOHN_SOLANO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,503,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='KARL_BRYAN_Q_SABERON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,504,'MABINI DISTRICT',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='CRISSELY_B_LAMORIN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,505,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='MARGIELYN_M_AWAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,506,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='ARNOLD_S_TABAQUIN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,507,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='JAYSON_COSO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,508,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='GERWIN_RUGAY';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,509,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TABLE_TENNIS'
JOIN pmms_people p ON p.person_key='CHERRY_LAURENCE_SUAZO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,510,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='RHEEX_G_CASTOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),NULL,'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,511,'COMPOSTELA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='MARIA_TERESA_A_APOG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,512,'PANTUKAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='GEZER_A_CAGAPE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,513,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='ARCELIE_SALINAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,514,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='OEJOFIEL_JOHN_P_SANCHEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,515,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='KRITINE_ROBBIE_L_SANCHEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,516,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='CHRISTINE_JAY_M_URBINA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,517,'LAAK',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='CHRISTLE_MARIE_A_CASADOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,518,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='JAN_JOFFRED_B_COMAINGKING';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,519,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='MARIA_LORENA_M_JIMENEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,520,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='LOREMIE_J_PELANTES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,521,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='KAROLE_E_MOJECA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,522,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='CHRISTIAN_JAY_PATENIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,523,'NABUNTURAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='DONALD_P_PIZARAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,524,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='REGGIE_M_ABSIN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,525,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='ELBEN_DOCENA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,526,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='GINA_CASERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,527,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='ANA_LYN_H_MICUTUAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,528,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='ALYSSA_NERI_TUMANDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,529,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='EVELYN_E_SAAD';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,530,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='TENNIS'
JOIN pmms_people p ON p.person_key='MARLON_SUAZO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,531,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='RANDY_H_MAESTRE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,532,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='LESTER_C_ENRIQUEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,533,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='CYRIL_G_ESTRADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,534,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='AIMEE_A_TOMAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,535,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='PAMEL_ROSE_DARATO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,536,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='HYRO_JAMES_P_COMODA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT- SECONDARY','Secondary',537,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='LOVELY_FAITH_L_ESTRADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT-ELEMENTARY','Elementary',538,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='AIRES_P_TIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,539,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='MERLIE_P_MARTIR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,540,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='ARJUN_C_MOJERES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,541,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='FELIPE_III_AUTOR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,542,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='JAY_ZON_B_LUMBA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,543,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='JEREMIAH_G_RECREO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,544,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='FREDDIE_BOLONOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,545,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='MARY_JANE_J_ORDENIZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,546,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='JUNALITA_M_LOPEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,547,'MACO SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='MARGIE_B_CELADA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,548,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='ANALIZA_D_LIMIKID';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,549,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='MERLYN_B_BAQUERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,550,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='SHERRIE_MARRIANNE_CALOGCOGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,551,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='KEYMARK_FELIPAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,552,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='PAMEL_ROSE_DARATO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,553,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='ANALYN_P_DINGAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,554,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='MARNELLE_TERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,555,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='MICHELLE_M_BALDONADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,556,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='ARNEL_A_SALOMON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,557,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='BEVERLY_T_TAGUPA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONTEVISTA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONTEVISTA' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,558,'MONTEVISTA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='SYRILL_JOHN_A_SOLIS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,559,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='CHRISTOPHER_A_NISTAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,560,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='RICHARD_FERNANDEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,561,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='VICTORIANO_JACALAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,562,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='JOCELYN_Q_BESTIL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,563,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='CHRISTOPHER_L_CRUDA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,564,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='JEANETH_E_RAGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,565,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='ERWIN_J_SALTA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,566,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='ROBERT_REY_ALABA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,567,'PANTUKAN SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='VOLLEYBALL'
JOIN pmms_people p ON p.person_key='JEREMIAS_M_GALLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,568,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='JOVIL_T_VILLARREIZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MABINI' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MABINI' LIMIT 1),'ASSISTANT_TOURNAMENT_MANAGER','ASST. TOURNAMENT MANAGER',NULL,569,'MABINI',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='DENNIS_D_MAURICIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,570,'NABUNTURAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='MERAFLOR_ONGAYO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,571,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='GARBAN_CLEFFER';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,572,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='BRYAN_G_PANINDIM';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,573,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='WINZELL_S_FELONGCO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,574,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='LLYOD_BON_M_LEGONES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,575,'MONKAYO WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WEIGHTLIFTING_KICKBOXING'
JOIN pmms_people p ON p.person_key='ROMEO_ENERO_CATAYAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,576,'COMPOSTELA WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='EDWIN_D_REMORERAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TOURNAMENT_SECRETARY','TOURNAMENT SECRETARY',NULL,577,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='JOCELYN_P_QUIMBA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,578,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='MECHEL_F_MONTIBON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,579,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='FERNANDO_JR_CORTEZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,580,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='SHEILA_MAY_V_RIVERA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,581,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='SHERYL_B_AMBROCIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,582,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='STEPHEN_OCZON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,583,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WRESTLING'
JOIN pmms_people p ON p.person_key='JOYCE_FE_R_BAGATNAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,584,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='JOSEPHINE_C_QUILATON';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,585,'COMPOSTELA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='JUNERIC_A_RICALDE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,586,'COMPOSTELA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='ROMULO_D_RACHO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,587,'LAAK',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='RONALD_T_RICO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,588,'NABUNTURAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='JUDY_ANN_R_ORTEGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,589,'NABUNTURAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='RODILO_RAMOS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,590,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='JONATHAN_B_LIMOSNERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,591,'MARAGUSAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='ROSETIN_MARICAR_T_PONGASE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,592,'MARAGUSAN EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='RIZA_M_DIAZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,593,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='ALJEAN_MANSANADES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MONKAYO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MONKAYO_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,594,'MONKAYO EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='WUSHU'
JOIN pmms_people p ON p.person_key='ROSELA_SUAREZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,595,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='PACITA_D_MANCAO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','IT / TECHNICAL OFFICIAL',NULL,596,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='FAITH_A_SALINAS';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,597,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='GIRLIE_JEAN_E_JAVIERTO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,598,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='RUDOLPH_PRUDENCE_S_PAILAGO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,599,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='LOVELY_KRIS_Q_BATINGAL';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,600,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='ELVIE_B_JUNIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,601,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='ROMAR_JANE_LUPOGAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,602,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='BOCCE'
JOIN pmms_people p ON p.person_key='RACHEL_B_ALBET';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,603,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='ROSAMATER_ORIBADO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'ASSISTANT_TOURNAMENT_MANAGER','ASST TOURNAMENT MANAGER',NULL,604,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='ROGELIO_ORBANEJA_JR';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,605,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='CLAVINOVA_B_BOLONIA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,606,'LAAK',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='MARGARITA_DEIPARINE';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,607,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='NANCY_CARILLO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),NULL,'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,608,'COMPOSTELA',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='FREITZY_AMOT_L_COJEN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,609,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='GOALBALL'
JOIN pmms_people p ON p.person_key='JENNIFER_D_ESTRIVO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,610,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='JOEL_E_CALISO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MARAGUSAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MARAGUSAN_WEST' LIMIT 1),'TOURNAMENT_ICT_TECHNICAL_OFFICIAL','ICT / TECHNICAL OFFICIAL',NULL,611,'MARAGUSAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='JOANN_S_BALIONG';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MACO' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MACO_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,612,'MACO NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='DONNABEL_BETONIO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,613,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='ANGELOU_E_DAYANAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,614,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='RUBY_S_BUGAHOD';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='COMPOSTELA' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='COMPOSTELA_EAST' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,615,'COMPOSTELA EAST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='RONNEL_NORTIGA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='MAWAB' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='MAWAB' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,616,'MAWAB',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='EUNISA_ALLIONES';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,617,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='GLESSEL_BANGIAN';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,618,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='RAQUEL_T_LAZO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NEW BATAAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NEW_BATAAN' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,619,'NEW BATAAN',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='ROCEL_C_HOMEO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_SOUTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,620,'LAAK SOUTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_ATHLETICS'
JOIN pmms_people p ON p.person_key='CECILY_LOURDS_M_BARUIZ';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='NABUNTURAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='NABUNTURAN_WEST' LIMIT 1),'TOURNAMENT_MANAGER','TOURNAMENT MANAGER',NULL,621,'NABUNTURAN WEST',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_SWIMMING'
JOIN pmms_people p ON p.person_key='EMMA_RITA_S_MENDOZA';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='LAAK' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='LAAK_NORTH' LIMIT 1),'TOURNAMENT_SECRETARY_ICT','TOURNAMENT SECRETARY/ICT',NULL,622,'LAAK NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_SWIMMING'
JOIN pmms_people p ON p.person_key='EDEN_S_ESPARTERO';
INSERT IGNORE INTO pmms_sport_personnel_assignments
(meet_sport_id,person_id,municipality_id,school_district_id,role_code,role_label,assignment_scope,source_sequence,source_district_text,requires_system_user)
SELECT ms.id,p.id,(SELECT id FROM pmms_municipalities WHERE code='PANTUKAN' LIMIT 1),(SELECT id FROM pmms_school_districts WHERE code='PANTUKAN_NORTH' LIMIT 1),'TECHNICAL_OFFICIAL','TECHNICAL OFFICIAL',NULL,623,'PANTUKAN NORTH',1
FROM pmms_meet_sports ms
JOIN pmms_meets mt ON mt.id=ms.meet_id AND mt.code='DDOPAA-2026'
JOIN pmms_sports s ON s.id=ms.sport_id AND s.code='PARA_SWIMMING'
JOIN pmms_people p ON p.person_key='CAREN_P_MANIQUEZ';

INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'abdul.lagungan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ABDUL_LAGUNGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ace.dave.d.cane','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ACE_DAVE_D_CANE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ace.marlo.a.celada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ACE_MARLO_A_CELADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'aimee.a.tomas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='AIMEE_A_TOMAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'aires.insao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='AIRES_INSAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'aires.p.tia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='AIRES_P_TIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'albert.s.ventura','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALBERT_S_VENTURA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'albin.lacaba','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALBIN_LACABA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'aldin.m.naquila','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALDIN_M_NAQUILA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alex.g.barredo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALEX_G_BARREDO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alexander.m.lagare','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALEXANDER_M_LAGARE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'aljean.mansanades','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALJEAN_MANSANADES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'almarie.p.navarre','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALMARIE_P_NAVARRE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alme.a.talandron','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALME_A_TALANDRON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'aloha.fe.v.batar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALOHA_FE_V_BATAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alrich.ryan.tagalog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALRICH_RYAN_TAGALOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alrin.tanudra','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALRIN_TANUDRA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alvin.j.abarecio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALVIN_J_ABARECIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'alyssa.neri.tumanda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ALYSSA_NERI_TUMANDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ana.lyn.h.micutuan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANA_LYN_H_MICUTUAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'anabel.c.baylon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANABEL_C_BAYLON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'anabelle.e.alcos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANABELLE_E_ALCOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'anacel.a.nacario','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANACEL_A_NACARIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analisa.p.gloria','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALISA_P_GLORIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analiza.d.limikid','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALIZA_D_LIMIKID'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analiza.r.glinogo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALIZA_R_GLINOGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analou.l.sidayon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALOU_L_SIDAYON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analyn.m.sanguinza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALYN_M_SANGUINZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analyn.p.dingal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALYN_P_DINGAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'analyn.p.panerio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANALYN_P_PANERIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'angelbert.doyog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANGELBERT_DOYOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'angelo.c.gutierrez.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANGELO_C_GUTIERREZ_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'angelou.e.dayanan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANGELOU_E_DAYANAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'anna.marie.maniquez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANNA_MARIE_MANIQUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'annabille.b.bastasa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANNABILLE_B_BASTASA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'annalyn.advincula','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ANNALYN_ADVINCULA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ara.a.quillo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARA_A_QUILLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arceli.a.humol','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARCELI_A_HUMOL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arcelie.salinas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARCELIE_SALINAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'archie.tanding','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARCHIE_TANDING'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ardiano.doydoy','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARDIANO_DOYDOY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'argie.bilandres','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARGIE_BILANDRES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'argie.d.quirante','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARGIE_D_QUIRANTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ariel.adolfo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARIEL_ADOLFO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arjun.c.mojeres','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARJUN_C_MOJERES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'armil.grafani','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARMIL_GRAFANI'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arnel.a.salomon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARNEL_A_SALOMON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arniel.c.marvas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARNIEL_C_MARVAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arnold.s.tabaquin','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARNOLD_S_TABAQUIN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'artchell.m.reyes','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARTCHELL_M_REYES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'arziel.pauline.jackie.t.jamora','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ARZIEL_PAULINE_JACKIE_T_JAMORA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'beberly.amparo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BEBERLY_AMPARO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'bella.josie.t.limbaga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BELLA_JOSIE_T_LIMBAGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'benjamin.o.madriaga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BENJAMIN_O_MADRIAGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'bernie.m.naquila','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BERNIE_M_NAQUILA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'beverly.c.reyes','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BEVERLY_C_REYES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'beverly.t.tagupa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BEVERLY_T_TAGUPA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'blaire.ian.v.adaro','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BLAIRE_IAN_V_ADARO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'bon.joie.limbaga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BON_JOIE_LIMBAGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'bonica.corsiga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BONICA_CORSIGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'boyeth.g.rulida','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BOYETH_G_RULIDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'bradley.von.sevilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BRADLEY_VON_SEVILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'bryan.g.panindim','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BRYAN_G_PANINDIM'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'buenafe.a.teman','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='BUENAFE_A_TEMAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'caren.p.maniquez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CAREN_P_MANIQUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'carlo.anthony.l.amac','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CARLO_ANTHONY_L_AMAC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'carlo.tutor','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CARLO_TUTOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cecelio.h.entecosa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CECELIO_H_ENTECOSA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cecily.lourds.m.baruiz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CECILY_LOURDS_M_BARUIZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'celindo.t.raut.raut','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CELINDO_T_RAUT_RAUT'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'charisse.may.z.sabay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHARISSE_MAY_Z_SABAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'chariza.i.saren','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHARIZA_I_SAREN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'charlito.reyes.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHARLITO_REYES_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'charmine.a.cunado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHARMINE_A_CUNADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'chazaira.mae.m.tapay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHAZAIRA_MAE_M_TAPAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'chelsea.faye.y.estrera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHELSEA_FAYE_Y_ESTRERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cherry.ann.b.rivera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHERRY_ANN_B_RIVERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cherry.laurence.suazo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHERRY_LAURENCE_SUAZO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cheryl.marie.n.macas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHERYL_MARIE_N_MACAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christ.ian.timbal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRIST_IAN_TIMBAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christian.jay.patenio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTIAN_JAY_PATENIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christian.l.apao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTIAN_L_APAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christian.li.miranda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTIAN_LI_MIRANDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christine.jay.m.urbina','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTINE_JAY_M_URBINA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christle.marie.a.casador','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTLE_MARIE_A_CASADOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christopher.a.lasarte','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTOPHER_A_LASARTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christopher.a.nistal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTOPHER_A_NISTAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christopher.janohan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTOPHER_JANOHAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christopher.l.cruda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTOPHER_L_CRUDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'christy.malig.on','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CHRISTY_MALIG_ON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cilito.d.diayon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CILITO_D_DIAYON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'claire.e.quijano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CLAIRE_E_QUIJANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'claribel.s.eling','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CLARIBEL_S_ELING'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'clavinova.b.bolonia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CLAVINOVA_B_BOLONIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cleffer.s.garban','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CLEFFER_S_GARBAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'corrine.gail.r.bulac','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CORRINE_GAIL_R_BULAC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cory.cristy.p.eyas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CORY_CRISTY_P_EYAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'crispin.samoya','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CRISPIN_SAMOYA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'crissely.b.lamorin','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CRISSELY_B_LAMORIN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cristito.s.suan.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CRISTITO_S_SUAN_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cyril.b.unggoy','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CYRIL_B_UNGGOY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'cyril.g.estrada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CYRIL_G_ESTRADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'czarine.mae.f.inclonar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='CZARINE_MAE_F_INCLONAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dafrhil.rose.quijada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DAFRHIL_ROSE_QUIJADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'daisy.m.besas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DAISY_M_BESAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'daisy.mae.dian.p.abaniza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DAISY_MAE_DIAN_P_ABANIZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'danelo.a.case','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DANELO_A_CASE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'danny.boy.c.orilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DANNY_BOY_C_ORILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'demetrio.opena','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DEMETRIO_OPENA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dennis.b.yumang','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DENNIS_B_YUMANG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dennis.d.mauricio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DENNIS_D_MAURICIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dennis.l.peligro','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DENNIS_L_PELIGRO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'diana.rose.garban','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DIANA_ROSE_GARBAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dina.mariecor.p.mojado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DINA_MARIECOR_P_MOJADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dindo.rabago','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DINDO_RABAGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'diosdado.p.velencio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DIOSDADO_P_VELENCIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'domingo.b.murillo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DOMINGO_B_MURILLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dominic.b.yumang','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DOMINIC_B_YUMANG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'donald.p.pizaras','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DONALD_P_PIZARAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'donnabel.betonio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DONNABEL_BETONIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'donnalyn.joyce.brandino','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DONNALYN_JOYCE_BRANDINO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'doreen.joy.d.glinogo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DOREEN_JOY_D_GLINOGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dowee.eve.tagud','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DOWEE_EVE_TAGUD'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'dreamy.b.babanto','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='DREAMY_B_BABANTO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eden.berg.p.bongcac','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDEN_BERG_P_BONGCAC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eden.s.espartero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDEN_S_ESPARTERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'edison.d.bautista','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDISON_D_BAUTISTA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eduard.c.cuasito','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDUARD_C_CUASITO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'edward.s.young','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDWARD_S_YOUNG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'edwin.d.remoreras','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDWIN_D_REMORERAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'edwin.n.estanol','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EDWIN_N_ESTANOL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elben.docena','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELBEN_DOCENA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elena.m.estrada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELENA_M_ESTRADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eleomar.cardenio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELEOMAR_CARDENIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eleonor.a.musca','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELEONOR_A_MUSCA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eliemar.jhone.j.bambao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELIEMAR_JHONE_J_BAMBAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elizabeth.joy.f.polinar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELIZABETH_JOY_F_POLINAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elje.may.a.penido','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELJE_MAY_A_PENIDO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eljun.m.sumayang','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELJUN_M_SUMAYANG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elpedio.jr.d.peralta','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELPEDIO_JR_D_PERALTA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elvie.b.junio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELVIE_B_JUNIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elvie.joy.t.serida','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELVIE_JOY_T_SERIDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ely.p.saromines','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELY_P_SAROMINES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'elyn.marie.d.sarael','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ELYN_MARIE_D_SARAEL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'emely.b.ramos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EMELY_B_RAMOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'emma.rita.s.mendoza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EMMA_RITA_S_MENDOZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'erlen.bandibas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ERLEN_BANDIBAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ernesto.gomez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ERNESTO_GOMEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ernie.lungan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ERNIE_LUNGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'erwin.c.alquiza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ERWIN_C_ALQUIZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'erwin.j.salta','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ERWIN_J_SALTA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eshuel.c.gigare','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ESHUEL_C_GIGARE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'eunisa.alliones','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EUNISA_ALLIONES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'evelyn.e.saad','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EVELYN_E_SAAD'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'evelyn.t.paderanga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='EVELYN_T_PADERANGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'faith.a.salinas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FAITH_A_SALINAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'fatima.c.jalane','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FATIMA_C_JALANE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'fe.b.formentera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FE_B_FORMENTERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'fe.g.contiga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FE_G_CONTIGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'feblout.tagawa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FEBLOUT_TAGAWA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'felipe.iii.autor','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FELIPE_III_AUTOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ferly.fernando','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FERLY_FERNANDO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'fernando.jr.cortez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FERNANDO_JR_CORTEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'filtricio.a.salinas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FILTRICIO_A_SALINAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'francis.laguna','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FRANCIS_LAGUNA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'freddie.bolonos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FREDDIE_BOLONOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'freedetez.p.enicuela','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FREEDETEZ_P_ENICUELA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'freitzy.amot.l.cojen','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FREITZY_AMOT_L_COJEN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'frelipacar.b.gucor','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FRELIPACAR_B_GUCOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'froil.ben.i.peligrino','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='FROIL_BEN_I_PELIGRINO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'galdys.lausa.virtudazo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GALDYS_LAUSA_VIRTUDAZO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'garban.cleffer','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GARBAN_CLEFFER'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gemma.i.pal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GEMMA_I_PAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'geofrey.e.hadlocon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GEOFREY_E_HADLOCON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'geraldine.calesa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GERALDINE_CALESA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'geraro.l.montana','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GERARO_L_MONTANA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'geronimo.b.galanza.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GERONIMO_B_GALANZA_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gerwin.rugay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GERWIN_RUGAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gesty.bughao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GESTY_BUGHAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gezer.a.cagape','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GEZER_A_CAGAPE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gimma.fernandez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GIMMA_FERNANDEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gina.casera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GINA_CASERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'girlie.jean.e.javierto','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GIRLIE_JEAN_E_JAVIERTO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gladys.v.espinosa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GLADYS_V_ESPINOSA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'glee.blanco','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GLEE_BLANCO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'glenn.a.limosnero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GLENN_A_LIMOSNERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'glessel.bangian','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GLESSEL_BANGIAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'gloryfel.b.mamusog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GLORYFEL_B_MAMUSOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'grace.mae.t.torreon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GRACE_MAE_T_TORREON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'grace.p.alura','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GRACE_P_ALURA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'grezel.c.castro','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GREZEL_C_CASTRO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'guillermo.ayala.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='GUILLERMO_AYALA_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'haide.g.lauron','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HAIDE_G_LAURON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'haidee.c.amistoso','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HAIDEE_C_AMISTOSO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'hanna.grace.morden','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HANNA_GRACE_MORDEN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'harvey.c.pioquinto','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HARVEY_C_PIOQUINTO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'harvey.nicky.esmedia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HARVEY_NICKY_ESMEDIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'henry.jan.c.dalagan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HENRY_JAN_C_DALAGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'honey.beth.c.oppus','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HONEY_BETH_C_OPPUS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'honeylyn.c.jainar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HONEYLYN_C_JAINAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'hyro.james.p.comoda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HYRO_JAMES_P_COMODA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'hyuan.c.malupay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='HYUAN_C_MALUPAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ian.p.tapanan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='IAN_P_TAPANAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'iris.b.labadan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='IRIS_B_LABADAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'irish.gene.a.salinas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='IRISH_GENE_A_SALINAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'isidro.d.sadongdong.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ISIDRO_D_SADONGDONG_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ivy.claire.salinas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='IVY_CLAIRE_SALINAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ivy.d.parajele','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='IVY_D_PARAJELE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jacky.lyn.lacre','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JACKY_LYN_LACRE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'james.m.merano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAMES_M_MERANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'james.v.rosalinda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAMES_V_ROSALINDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jan.joffred.b.comaingking','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAN_JOFFRED_B_COMAINGKING'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'janice.d.taasan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JANICE_D_TAASAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'janice.escarpio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JANICE_ESCARPIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'janice.s.capuyan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JANICE_S_CAPUYAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'janine.j.oliva','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JANINE_J_OLIVA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jannel.p.calogmoc','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JANNEL_P_CALOGMOC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'japeth.sebuala','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAPETH_SEBUALA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jaquelyn.v.pastor','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAQUELYN_V_PASTOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jasiel.m.mariano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JASIEL_M_MARIANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jasmin.p.pascan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JASMIN_P_PASCAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jassen.mae.l.gomez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JASSEN_MAE_L_GOMEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jay.a.casas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAY_A_CASAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jay.nino.roa.maquilan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAY_NINO_ROA_MAQUILAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jay.r.c.ang','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAY_R_C_ANG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jay.zon.b.lumba','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAY_ZON_B_LUMBA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jaymes.balala','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAYMES_BALALA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jayson.b.salili','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAYSON_B_SALILI'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jayson.coso','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAYSON_COSO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jayson.marundan.mangapas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAYSON_MARUNDAN_MANGAPAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jayvee.t.cruda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JAYVEE_T_CRUDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jean.j.bassig','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEAN_J_BASSIG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jean.may.a.sabarita','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEAN_MAY_A_SABARITA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jeaneth.e.raga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEANETH_E_RAGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jed.lucky.s.racho','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JED_LUCKY_S_RACHO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jeirmane.p.dahay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEIRMANE_P_DAHAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jelyn.m.difuntorum','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JELYN_M_DIFUNTORUM'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jemar.c.catoc','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEMAR_C_CATOC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jenelyn.h.marbebe','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENELYN_H_MARBEBE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jennifer.d.estrivo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENNIFER_D_ESTRIVO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jennifer.lazo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENNIFER_LAZO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jennifer.n.jamarolin','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENNIFER_N_JAMAROLIN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jennifer.t.templonuevo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENNIFER_T_TEMPLONUEVO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jennilyn.t.serote','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENNILYN_T_SEROTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jenny.mae.a.sablon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JENNY_MAE_A_SABLON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jereme.sevilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEREME_SEVILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jeremiah.g.recreo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEREMIAH_G_RECREO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jeremias.m.gallo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JEREMIAS_M_GALLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jerry.a.espina','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JERRY_A_ESPINA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jerson.o.ringconada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JERSON_O_RINGCONADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jerson.ybanes','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JERSON_YBANES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jeslie.o.tianzon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESLIE_O_TIANZON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jesly.a.magno','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESLY_A_MAGNO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jessa.l.colares','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESSA_L_COLARES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jessa.mae.s.brigole','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESSA_MAE_S_BRIGOLE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jessie.a.centillas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESSIE_A_CENTILLAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jesson.s.panerio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESSON_S_PANERIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jester.pescadero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESTER_PESCADERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jestoni.a.samputon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESTONI_A_SAMPUTON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jesus.sicat.m.catugal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JESUS_SICAT_M_CATUGAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jhea.mae.p.dagame','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JHEA_MAE_P_DAGAME'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jhonrey.b.palua','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JHONREY_B_PALUA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jimmy.varquez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JIMMY_VARQUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jione.v.cabactulan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JIONE_V_CABACTULAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jo.marie.m.villa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JO_MARIE_M_VILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joan.a.bulan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOAN_A_BULAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joann.s.baliong','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOANN_S_BALIONG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joanne.y.salvador','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOANNE_Y_SALVADOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joannie.palabia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOANNIE_PALABIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jocel.labadan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOCEL_LABADAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jocel.p.lopez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOCEL_P_LOPEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jocelyn.d.molero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOCELYN_D_MOLERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jocelyn.p.quimba','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOCELYN_P_QUIMBA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jocelyn.q.bestil','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOCELYN_Q_BESTIL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joe.art.g.capatan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOE_ART_G_CAPATAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joecip.aninon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOECIP_ANINON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joel.e.caliso','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOEL_E_CALISO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joemar.g.comawas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOEMAR_G_COMAWAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joemon.sibayan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOEMON_SIBAYAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joey.b.amada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOEY_B_AMADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joey.b.pamplona','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOEY_B_PAMPLONA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joey.casila','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOEY_CASILA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'johaness.n.escovilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHANESS_N_ESCOVILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'johanne.princess.a.wagas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHANNE_PRINCESS_A_WAGAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'john.dave.e.rufano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHN_DAVE_E_RUFANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'john.mapelle.labitigan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHN_MAPELLE_LABITIGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'john.mark.mangaron','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHN_MARK_MANGARON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'john.paul.j.quibal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHN_PAUL_J_QUIBAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'john.russel.b.cadungog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHN_RUSSEL_B_CADUNGOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'johnbert.f.inclonar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHNBERT_F_INCLONAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'johnie.rey.t.alcos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHNIE_REY_T_ALCOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'johny.a.sarael','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOHNY_A_SARAEL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joko.frits.b.diaz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOKO_FRITS_B_DIAZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jomarison.l.balbero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOMARISON_L_BALBERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonah.joffa.faith.p.fuentes','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONAH_JOFFA_FAITH_P_FUENTES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonalee.j.torlao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONALEE_J_TORLAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonas.t.batonghinog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONAS_T_BATONGHINOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonathan.a.urgel','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONATHAN_A_URGEL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonathan.b.limosnero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONATHAN_B_LIMOSNERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonathan.ecat','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONATHAN_ECAT'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jonnifer.t.chua','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JONNIFER_T_CHUA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jordan.c.gochoco','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JORDAN_C_GOCHOCO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joseph.m.martos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSEPH_M_MARTOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'josephine.c.quilaton','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSEPHINE_C_QUILATON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'josephine.m.villafuerte','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSEPHINE_M_VILLAFUERTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'josephine.s.persegas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSEPHINE_S_PERSEGAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'josette.asilo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSETTE_ASILO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joshua.mepico','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSHUA_MEPICO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joshua.ybanez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSHUA_YBANEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'josie.j.saberon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOSIE_J_SABERON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jovanni.a.lusica','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOVANNI_A_LUSICA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jovar.t.saging','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOVAR_T_SAGING'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jover.l.bastasa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOVER_L_BASTASA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jovil.t.villarreiz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOVIL_T_VILLARREIZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joy.i.lumocso','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOY_I_LUMOCSO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joyce.fe.r.bagatnan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOYCE_FE_R_BAGATNAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joycee.mae.caldito','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOYCEE_MAE_CALDITO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'joylen.b.ellan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JOYLEN_B_ELLAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'juadjie.parba','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUADJIE_PARBA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'judy.ann.r.ortega','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUDY_ANN_R_ORTEGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'julanne.f.ganza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULANNE_F_GANZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'julibeth.p.bobo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULIBETH_P_BOBO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'juliet.b.gonida','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULIET_B_GONIDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'julito.jr.d.cases.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULITO_JR_D_CASES_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'julius.ernest.r.miranda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULIUS_ERNEST_R_MIRANDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'julius.rigor.duque','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULIUS_RIGOR_DUQUE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'julius.tapot','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULIUS_TAPOT'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'juluis.balansag','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JULUIS_BALANSAG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jumar.basalo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUMAR_BASALO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jumar.ian.l.teves','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUMAR_IAN_L_TEVES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jun.rey.agoy','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUN_REY_AGOY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'junalita.m.lopez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUNALITA_M_LOPEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'juneric.a.ricalde','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JUNERIC_A_RICALDE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'jurlie.m.muring','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='JURLIE_M_MURING'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kaira.guirigay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KAIRA_GUIRIGAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'karl.bryan.q.saberon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KARL_BRYAN_Q_SABERON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'karole.e.mojeca','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KAROLE_E_MOJECA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'katleen.joy.s.martinez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KATLEEN_JOY_S_MARTINEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ken.clark.pugo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KEN_CLARK_PUGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kenneth.salamanca','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KENNETH_SALAMANCA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kevin.bul.an','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KEVIN_BUL_AN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'keymark.felipas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KEYMARK_FELIPAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kim.b.banogbanog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KIM_B_BANOGBANOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kim.christopher.r.niala','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KIM_CHRISTOPHER_R_NIALA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kjay.d.aro','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KJAY_D_ARO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kris.d.barnido','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KRIS_D_BARNIDO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kristene.novie.m.perez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KRISTENE_NOVIE_M_PEREZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kristia.reyna.b.tabanyag','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KRISTIA_REYNA_B_TABANYAG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kristine.mae.ochea','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KRISTINE_MAE_OCHEA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'kritine.robbie.l.sanchez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='KRITINE_ROBBIE_L_SANCHEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lani.m.gogo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LANI_M_GOGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lavilla.l.wate','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LAVILLA_L_WATE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leahna.gem.r.jubane','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEAHNA_GEM_R_JUBANE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leo.l.largo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEO_L_LARGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leo.marlo.p.ramos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEO_MARLO_P_RAMOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leo.rey.cagas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEO_REY_CAGAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leo.santo.o.escarpe','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEO_SANTO_O_ESCARPE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leonardo.m.calolo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEONARDO_M_CALOLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leonilo.s.torrejas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEONILO_S_TORREJAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'leonilyn.khris.l.abarecio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LEONILYN_KHRIS_L_ABARECIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lester.c.enriquez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LESTER_C_ENRIQUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lhester.jay.u.salao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LHESTER_JAY_U_SALAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'libenly.e.castillo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LIBENLY_E_CASTILLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lindsay.canezo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LINDSAY_CANEZO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lindy.lou.dug.oy','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LINDY_LOU_DUG_OY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lioba.c.lindong.ii','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LIOBA_C_LINDONG_II'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lira.p.eyas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LIRA_P_EYAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lloyd.d.nabasca','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LLOYD_D_NABASCA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'llyod.bon.m.legones','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LLYOD_BON_M_LEGONES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lorelie.p.baruiz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LORELIE_P_BARUIZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'loremie.j.pelantes','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOREMIE_J_PELANTES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'louie.gomez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOUIE_GOMEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lourence.u.febria','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOURENCE_U_FEBRIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'louvien.dee.l.ibarra','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOUVIEN_DEE_L_IBARRA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'louwell.zal.c.almilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOUWELL_ZAL_C_ALMILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lovely.faith.l.estrada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOVELY_FAITH_L_ESTRADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lovely.kris.q.batingal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOVELY_KRIS_Q_BATINGAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lovely.m.enanoria','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LOVELY_M_ENANORIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ludwig.von.c.braga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LUDWIG_VON_C_BRAGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lydel.brian.canete','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LYDEL_BRIAN_CANETE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lyka.patulot','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LYKA_PATULOT'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'lyzza.p.bodiongan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='LYZZA_P_BODIONGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mae.roseller.chanco','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MAE_ROSELLER_CHANCO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mafer.c.polistico','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MAFER_C_POLISTICO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maiza.d.opisan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MAIZA_D_OPISAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'margarita.deiparine','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARGARITA_DEIPARINE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'margie.b.celada','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARGIE_B_CELADA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'margielyn.m.awas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARGIELYN_M_AWAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maria.lorena.m.jimenez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIA_LORENA_M_JIMENEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maria.teresa.a.apog','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIA_TERESA_A_APOG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maria.thesalonika.princess.ona','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIA_THESALONIKA_PRINCESS_ONA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maricel.mercado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARICEL_MERCADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marichu.p.garano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARICHU_P_GARANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marife.d.shagol','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIFE_D_SHAGOL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marilag.o.bacalso','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARILAG_O_BACALSO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mario.aluad.ii','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIO_ALUAD_II'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marissa.c.castorico','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARISSA_C_CASTORICO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marissa.m.orbino','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARISSA_M_ORBINO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marites.a.malda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARITES_A_MALDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marivic.r.tulo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIVIC_R_TULO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mariz.porras','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARIZ_PORRAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marjo.l.labadlabad','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARJO_L_LABADLABAD'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marjon.g.abonero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARJON_G_ABONERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marjorie.l.ganaden','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARJORIE_L_GANADEN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marjorie.orais','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARJORIE_ORAIS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mark.aubrey.p.posadas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARK_AUBREY_P_POSADAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marlon.g.plaza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARLON_G_PLAZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marlon.p.pizarras','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARLON_P_PIZARRAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marlon.suazo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARLON_SUAZO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marnelle.tero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARNELLE_TERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marry.ann.paulines','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARRY_ANN_PAULINES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marvi.s.espena','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARVI_S_ESPENA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'marwin.gil.golez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARWIN_GIL_GOLEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.ann.c.arocha','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_ANN_C_AROCHA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.jane.j.ordeniza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_JANE_J_ORDENIZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.joan.c.seno','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_JOAN_C_SENO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.joy.c.mission','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_JOY_C_MISSION'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.joy.naquila','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_JOY_NAQUILA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.paz.v.pastera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_PAZ_V_PASTERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.rose.amora.quebec','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_ROSE_AMORA_QUEBEC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mary.rose.cano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARY_ROSE_CANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maryjane.m.caberos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MARYJANE_M_CABEROS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'maxwell.zoilon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MAXWELL_ZOILON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mc.vee.manalili','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MC_VEE_MANALILI'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mechel.f.montibon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MECHEL_F_MONTIBON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'medelyn.timario','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MEDELYN_TIMARIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melchi.figueroa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELCHI_FIGUEROA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melgie.f.naquila','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELGIE_F_NAQUILA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melissa.sheareene.m.mayormita','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELISSA_SHEAREENE_M_MAYORMITA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melissa.t.selegencia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELISSA_T_SELEGENCIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melther.fe.o.padilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELTHER_FE_O_PADILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melva.p.ampilan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELVA_P_AMPILAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'melvin.jade.v.hubahib','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MELVIN_JADE_V_HUBAHIB'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'meraflor.ongayo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MERAFLOR_ONGAYO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'meriam.r.andrade','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MERIAM_R_ANDRADE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'merlie.p.martir','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MERLIE_P_MARTIR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'merlyn.b.baquero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MERLYN_B_BAQUERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'merry.cris.d.batucan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MERRY_CRIS_D_BATUCAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'merry.jean.c.medrina','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MERRY_JEAN_C_MEDRINA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mevylin.a.embay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MEVYLIN_A_EMBAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'michael.esteban','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHAEL_ESTEBAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'michael.frances.pinsoy','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHAEL_FRANCES_PINSOY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'michael.kems.z.colegado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHAEL_KEMS_Z_COLEGADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'michael.p.pacheco','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHAEL_P_PACHECO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'michael.v.dela.pena','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHAEL_V_DELA_PENA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'micheal.john.solano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHEAL_JOHN_SOLANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'michelle.m.baldonado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MICHELLE_M_BALDONADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mildred.comoda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MILDRED_COMODA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'milton.l.benilan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MILTON_L_BENILAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mira.n.arranguez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MIRA_N_ARRANGUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'monette.a.gamutan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MONETTE_A_GAMUTAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'moraida.p.salapantan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MORAIDA_P_SALAPANTAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'mylyn.d.garcia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='MYLYN_D_GARCIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nancy.carillo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NANCY_CARILLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nasroddin.s.cabugatan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NASRODDIN_S_CABUGATAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'neacel.d.juntilla','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NEACEL_D_JUNTILLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'neckle.j.arais','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NECKLE_J_ARAIS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'neilmhar.e.magallanes','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NEILMHAR_E_MAGALLANES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nelia.r.sanchez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NELIA_R_SANCHEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nelson.c.cano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NELSON_C_CANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nelson.catipay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NELSON_CATIPAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nelson.p.baudan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NELSON_P_BAUDAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nenita.h.valencia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NENITA_H_VALENCIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nerissa.g.darollo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NERISSA_G_DAROLLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nerissa.g.hernandez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NERISSA_G_HERNANDEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'niel.mar.l.corpuz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NIEL_MAR_L_CORPUZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nilda.n.seno','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NILDA_N_SENO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nilieta.macalanga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NILIETA_MACALANGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'noel.balmoria','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NOEL_BALMORIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'noime.c.castil','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NOIME_C_CASTIL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nole.villason','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NOLE_VILLASON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nona.bae.manudsod','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NONA_BAE_MANUDSOD'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'nonito.mata','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NONITO_MATA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'norbert.vincent.s.miaco','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NORBERT_VINCENT_S_MIACO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'noreen.agosto','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NOREEN_AGOSTO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'norlito.lasay','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NORLITO_LASAY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'norlyn.jane.m.espinosa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NORLYN_JANE_M_ESPINOSA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'noumi.t.aniscal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='NOUMI_T_ANISCAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'oejofiel.john.p.sanchez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='OEJOFIEL_JOHN_P_SANCHEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'orlando.jr.m.escollar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ORLANDO_JR_M_ESCOLLAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'pablito.paradiro','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PABLITO_PARADIRO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'pacita.d.mancao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PACITA_D_MANCAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'pamel.rose.darato','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PAMEL_ROSE_DARATO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'pedro.cabahug.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PEDRO_CABAHUG_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'pelbert.james.durado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PELBERT_JAMES_DURADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'philip.c.serida','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PHILIP_C_SERIDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'prets.a.dongalo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PRETS_A_DONGALO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'princess.b.dagondon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PRINCESS_B_DAGONDON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'pyke.kudera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='PYKE_KUDERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rachel.b.albet','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RACHEL_B_ALBET'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rachelle.d.intig','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RACHELLE_D_INTIG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rafael.nalangan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAFAEL_NALANGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rainbow.a.navasca','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAINBOW_A_NAVASCA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ramon.m.genabe','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAMON_M_GENABE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'randy.h.maestre','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RANDY_H_MAESTRE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'randy.m.rallos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RANDY_M_RALLOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'randy.n.sipsip','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RANDY_N_SIPSIP'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ransom.pinsoy','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RANSOM_PINSOY'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'raquel.h.sotelo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAQUEL_H_SOTELO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'raquel.m.manuel','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAQUEL_M_MANUEL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'raquel.t.lazo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAQUEL_T_LAZO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'raymart.celeste','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAYMART_CELESTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'raymond.s.turbella','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RAYMOND_S_TURBELLA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'reane.f.labano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='REANE_F_LABANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'reggie.m.absin','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='REGGIE_M_ABSIN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'renante.c.alia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RENANTE_C_ALIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'renante.e.liquit','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RENANTE_E_LIQUIT'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'renante.gacmatan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RENANTE_GACMATAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rene.joseph.abarquez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RENE_JOSEPH_ABARQUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rene.l.legara','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RENE_L_LEGARA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'renegildo.m.gogo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RENEGILDO_M_GOGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'reshelle.may.aye.h.tubal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RESHELLE_MAY_AYE_H_TUBAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rey.cris.juanillo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='REY_CRIS_JUANILLO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'reymart.c.malig.on','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='REYMART_C_MALIG_ON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'reynaldo.castillo.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='REYNALDO_CASTILLO_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'reynan.libreta','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='REYNAN_LIBRETA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rhapy.a.vidal','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RHAPY_A_VIDAL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rheex.g.castor','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RHEEX_G_CASTOR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rhobelyn.p.semacio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RHOBELYN_P_SEMACIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rhujelen.c.tormis','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RHUJELEN_C_TORMIS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ricardo.alberio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RICARDO_ALBERIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'richard.fernandez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RICHARD_FERNANDEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ritchie.c.baste','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RITCHIE_C_BASTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'riza.b.ruiz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RIZA_B_RUIZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'riza.m.diaz','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RIZA_M_DIAZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rizal.jr.h.capangpangan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RIZAL_JR_H_CAPANGPANGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'robert.briones','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROBERT_BRIONES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'robert.rey.alaba','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROBERT_REY_ALABA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'roberto.aguipo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROBERTO_AGUIPO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rocel.c.homeo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROCEL_C_HOMEO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rodelia.g.miparanum','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RODELIA_G_MIPARANUM'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rodilo.ramos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RODILO_RAMOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rodjit.s.plete','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RODJIT_S_PLETE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rodney.zeus.lescano','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RODNEY_ZEUS_LESCANO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rodrigo.l.cawagas.ii','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RODRIGO_L_CAWAGAS_II'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rodsal.bariga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RODSAL_BARIGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'roed.sibanta','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROED_SIBANTA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rogelio.orbaneja.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROGELIO_ORBANEJA_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rogelyn.t.espinosa','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROGELYN_T_ESPINOSA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rojane.l.daite','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROJANE_L_DAITE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'roland.kylle.h.villacrusis','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROLAND_KYLLE_H_VILLACRUSIS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'romar.jane.lupogan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROMAR_JANE_LUPOGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'romeo.enero.catayas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROMEO_ENERO_CATAYAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'romeo.gonzaga.jr','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROMEO_GONZAGA_JR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rommel.p.lumasag','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROMMEL_P_LUMASAG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rommel.t.garcia','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROMMEL_T_GARCIA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'romulo.d.racho','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROMULO_D_RACHO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronald.calago','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONALD_CALAGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronald.james.b.busig','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONALD_JAMES_B_BUSIG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronald.m.paderanga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONALD_M_PADERANGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronald.t.rico','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONALD_T_RICO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronald.t.tabanao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONALD_T_TABANAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronard.t.ma.aghop','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONARD_T_MA_AGHOP'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronel.h.nacario','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONEL_H_NACARIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronel.largo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONEL_LARGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronnel.nortiga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONNEL_NORTIGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronnel.p.jagonos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONNEL_P_JAGONOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ronnie.o.napulag','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RONNIE_O_NAPULAG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosalina.g.tapayan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSALINA_G_TAPAYAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosamater.oribado','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSAMATER_ORIBADO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rose.ann.p.jimena','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSE_ANN_P_JIMENA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosela.l.bucio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSELA_L_BUCIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosela.suarez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSELA_SUAREZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosemarie.d.mag.aso','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSEMARIE_D_MAG_ASO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosemarie.l.cafe','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSEMARIE_L_CAFE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rosetin.maricar.t.pongase','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROSETIN_MARICAR_T_PONGASE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rovelyn.joy.humol','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROVELYN_JOY_HUMOL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rovynna.ellaiza.e.montejo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROVYNNA_ELLAIZA_E_MONTEJO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rowel.castaneda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROWEL_CASTANEDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rowelyn.y.justino','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROWELYN_Y_JUSTINO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rowil.vallicer','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROWIL_VALLICER'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rowileen.n.dumail','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ROWILEEN_N_DUMAIL'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ruby.concha','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RUBY_CONCHA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ruby.s.bugahod','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RUBY_S_BUGAHOD'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rudelyn.l.malagante','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RUDELYN_L_MALAGANTE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rudolph.prudence.s.pailago','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RUDOLPH_PRUDENCE_S_PAILAGO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ruel.v.semeon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RUEL_V_SEMEON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ruther.y.ibanez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RUTHER_Y_IBANEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'ryan.b.hanibo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RYAN_B_HANIBO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'rylle.adrian.barona','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='RYLLE_ADRIAN_BARONA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'salomon.gullez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SALOMON_GULLEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'samuel.v.retiza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SAMUEL_V_RETIZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'semplecio.t.cagna.an','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SEMPLECIO_T_CAGNA_AN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'serzon.jane.c.ganzalao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SERZON_JANE_C_GANZALAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shallemer.amor.r.galenzoga','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHALLEMER_AMOR_R_GALENZOGA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shan.ralph.r.mateo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHAN_RALPH_R_MATEO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shara.j.t.vasquez','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHARA_J_T_VASQUEZ'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sharmine.o.sarno','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHARMINE_O_SARNO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sharon.rose.b.agustin','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHARON_ROSE_B_AGUSTIN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sheila.mae.e.lindero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHEILA_MAE_E_LINDERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sheila.may.v.rivera','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHEILA_MAY_V_RIVERA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sheila.o.agatinto','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHEILA_O_AGATINTO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shella.may.l.dandan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHELLA_MAY_L_DANDAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shem.b.magbutong','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHEM_B_MAGBUTONG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sherlyn.s.mapalo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHERLYN_S_MAPALO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sherrie.marrianne.calogcogan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHERRIE_MARRIANNE_CALOGCOGAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sherwin.a.galimba','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHERWIN_A_GALIMBA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sheryl.a.cayao','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHERYL_A_CAYAO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'sheryl.b.ambrocio','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHERYL_B_AMBROCIO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shiela.mae.c.salazar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHIELA_MAE_C_SALAZAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'shirley.i.frondoza','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SHIRLEY_I_FRONDOZA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'stephen.oczon','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='STEPHEN_OCZON'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'syrill.john.a.solis','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='SYRILL_JOHN_A_SOLIS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'thea.cybele.c.gecale','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='THEA_CYBELE_C_GECALE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'theresa.a.epo','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='THERESA_A_EPO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'threscia.mae.b.cenas','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='THRESCIA_MAE_B_CENAS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'tummy.jun.f.sabuero','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='TUMMY_JUN_F_SABUERO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'vanessa.m.muca','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VANESSA_M_MUCA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'verdalizle.c.arais','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VERDALIZLE_C_ARAIS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'verna.albino','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VERNA_ALBINO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'victoriano.jacalan','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VICTORIANO_JACALAN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'vince.ian.a.abanales','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VINCE_IAN_A_ABANALES'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'vincent.paul.polinar','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VINCENT_PAUL_POLINAR'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'vivian.alcazaren','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='VIVIAN_ALCAZAREN'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'wendel.cruda','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='WENDEL_CRUDA'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'winzell.s.felongco','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='WINZELL_S_FELONGCO'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'xylene.p.cantos','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='XYLENE_P_CANTOS'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'yienna.bale','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='YIENNA_BALE'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'yulbrainer.boiser','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='YULBRAINER_BOISER'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'yvonne.g.amahit','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='YVONNE_G_AMAHIT'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'zafira.ugapang','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ZAFIRA_UGAPANG'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);
INSERT INTO pmms_user_provisioning (person_id,suggested_username,target_role,provisioning_reason)
SELECT p.id,'zalde.j.radjac','sport_personnel','SPORT_PERSONNEL_2026'
FROM pmms_people p WHERE p.person_key='ZALDE_J_RADJAC'
ON DUPLICATE KEY UPDATE suggested_username=VALUES(suggested_username), provisioning_reason=VALUES(provisioning_reason);

INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'ARCHERY','regular','elementary','NONE',3,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'ARCHERY','regular','secondary','INDIVIDUAL',3,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'ARNIS','regular','elementary','INDIVIDUAL & DOUBLE',4,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'ARNIS','regular','secondary','INDIVIDUAL & DOUBLE',4,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'ATHLETICS','regular','elementary','INDIVIDUAL',5,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'ATHLETICS','regular','secondary','INDIVIDUAL',5,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BADMINTON','regular','elementary','INDIVIDUAL',6,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BADMINTON','regular','secondary','INDIVIDUAL',6,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BASEBALL','regular','elementary','12 CORE GROUP + 3 HYBRID',7,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BASEBALL','regular','secondary','12 CORE GROUP + 3 HYBRID',7,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BASKETBALL','regular','elementary','12 CORE GROUP + 3 HYBRID',8,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BASKETBALL','regular','secondary','12 CORE GROUP + 3 HYBRID',8,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BASKETBALL_3X3','regular','elementary','NONE',9,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BASKETBALL_3X3','regular','secondary','4',9,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BILLIARDS','regular','elementary','NONE',10,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BILLIARDS','regular','secondary','INDIVIDUAL',10,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BOXING','regular','elementary','NONE',11,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BOXING','regular','secondary','INDIVIDUAL',11,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'CHESS','regular','elementary','INDIVIDUAL',12,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'CHESS','regular','secondary','INDIVIDUAL',12,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'DANCESPORTS','regular','elementary','PAIR LATIN',13,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'DANCESPORTS','regular','secondary','PAIR LATIN',13,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'DANCESPORTS','regular','elementary','PAIR STANDARD',14,'Continuation row from source workbook'
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'DANCESPORTS','regular','secondary','PAIR STANDARD',14,'Continuation row from source workbook'
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'FOOTBALL','regular','elementary','12 CORE GROUP + 3 HYBRID',15,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'FOOTBALL','regular','secondary','12 CORE GROUP + 3 HYBRID',15,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WAG','regular','elementary','INDIVIDUAL',18,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WAG','regular','secondary','INDIVIDUAL',18,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'MAG','regular','elementary','INDIVIDUAL',19,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'MAG','regular','secondary','INDIVIDUAL',19,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'RG','regular','elementary','INDIVIDUAL',20,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'RG','regular','secondary','INDIVIDUAL',20,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SEPAK_TAKRAW','regular','elementary','4',22,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SEPAK_TAKRAW','regular','secondary','12 CORE GROUP + 3 HYBRID',22,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SEPAK_TAKRAW','regular','secondary','12 CORE GROUP IN 1 SCHOOL',23,'Continuation row from source workbook'
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SEPAK_TAKRAW','regular','secondary','8 IN 1 SCHOOL & 4 IN ANOTHER SCHOOL',24,'Continuation row from source workbook'
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SEPAK_TAKRAW','regular','secondary','4 IN 3 DIFFERENT SCHOOL',25,'Continuation row from source workbook'
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SOFTBALL','regular','elementary','12 CORE GROUP + 3 HYBRID',26,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SOFTBALL','regular','secondary','12 CORE GROUP + 3 HYBRID',26,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SWIMMING','regular','elementary','INDIVIDUAL',27,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'SWIMMING','regular','secondary','INDIVIDUAL',27,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'TAEKWONDO','regular','elementary','INDIVIDUAL',28,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'TAEKWONDO','regular','secondary','INDIVIDUAL',28,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'TABLE_TENNIS','regular','elementary','INDIVIDUAL & DOUBLES',29,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'TABLE_TENNIS','regular','secondary','INDIVIDUAL & DOUBLES',29,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'TENNIS','regular','elementary','INDIVIDUAL & DOUBLES',30,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'TENNIS','regular','secondary','INDIVIDUAL & DOUBLES',30,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'VOLLEYBALL','regular','elementary','12 CORE GROUP + 3 HYBRID',31,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'VOLLEYBALL','regular','secondary','12 CORE GROUP + 3 HYBRID',31,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WEIGHTLIFTING','regular','elementary','NONE',32,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WEIGHTLIFTING','regular','secondary','INDIVIDUAL',32,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WRESTLING','regular','elementary','NONE',33,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WRESTLING','regular','secondary','INDIVIDUAL',33,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WUSHU','regular','elementary','NONE',34,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'WUSHU','regular','secondary','INDIVIDUAL',34,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'PENCAK_SILAT','regular','elementary','NONE',35,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'PENCAK_SILAT','regular','secondary','INDIVIDUAL',35,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'BOCCE','paragames','elementary','3',37,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'GOALBALL','paragames','elementary','3',38,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'PARA_ATHLETICS','paragames','elementary','INDIVIDUAL',39,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);
INSERT INTO pmms_sport_participation_rules
(meet_id,sport_code,classification,level,participation_rule,source_row,notes)
SELECT m.id,'PARA_SWIMMING','paragames','elementary','INDIVIDUAL',40,NULL
FROM pmms_meets m WHERE m.code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE participation_rule=VALUES(participation_rule), notes=VALUES(notes);

INSERT INTO pmms_coach_registration_settings
(meet_id,self_registration_enabled,coach_selects_sports,coach_enrolls_athletes,requires_assignment_approval)
SELECT id,1,1,1,1 FROM pmms_meets WHERE code='DDOPAA-2026'
ON DUPLICATE KEY UPDATE
self_registration_enabled=1,
coach_selects_sports=1,
coach_enrolls_athletes=1,
requires_assignment_approval=1;

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- Verification queries
SELECT COUNT(*) AS twg_members FROM pmms_twg_memberships;
SELECT COUNT(*) AS dsc_assignments FROM pmms_dsc_assignments;
SELECT COUNT(*) AS sport_personnel_assignments FROM pmms_sport_personnel_assignments;
SELECT COUNT(*) AS pending_user_accounts FROM pmms_user_provisioning WHERE provisioning_status='pending';
SELECT s.name,s.classification,COUNT(spa.id) AS assigned_personnel FROM pmms_meet_sports ms JOIN pmms_sports s ON s.id=ms.sport_id LEFT JOIN pmms_sport_personnel_assignments spa ON spa.meet_sport_id=ms.id GROUP BY s.id,s.name,s.classification ORDER BY s.display_order;