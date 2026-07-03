CREATE DATABASE IF NOT EXISTS simpi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE simpi;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS progres_revisi;
DROP TABLE IF EXISTS anggaran_revisi;
DROP TABLE IF EXISTS user_status_log;
DROP TABLE IF EXISTS email_log;
DROP TABLE IF EXISTS password_reset_token;
DROP TABLE IF EXISTS password_reset_log;
DROP TABLE IF EXISTS login_log;
DROP TABLE IF EXISTS progres;
DROP TABLE IF EXISTS proyek;
DROP TABLE IF EXISTS `user`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `user` (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) DEFAULT NULL,
  role ENUM('admin','petugas') NOT NULL DEFAULT 'petugas',
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  account_status ENUM('belum_aktif','aktif','arsip') NOT NULL DEFAULT 'belum_aktif',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login_at DATETIME DEFAULT NULL,
  password_changed_at DATETIME DEFAULT NULL,
  deactivated_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_role (role),
  INDEX idx_is_active (is_active),
  INDEX idx_account_status (account_status),
  INDEX idx_last_login_at (last_login_at)
) ENGINE=InnoDB;

CREATE TABLE proyek (
  id_proyek INT AUTO_INCREMENT PRIMARY KEY,
  nama_proyek VARCHAR(150) NOT NULL,
  jenis_proyek VARCHAR(80) NOT NULL,
  lokasi VARCHAR(150) NOT NULL,
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  status ENUM('Perencanaan','Berjalan','Selesai','Tertunda') NOT NULL DEFAULT 'Perencanaan',
  anggaran_awal DECIMAL(15,2) NOT NULL DEFAULT 0,
  anggaran DECIMAL(15,2) NOT NULL DEFAULT 0,
  deskripsi TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_jenis_proyek (jenis_proyek),
  INDEX idx_status (status),
  INDEX idx_tanggal_mulai (tanggal_mulai),
  INDEX idx_anggaran (anggaran),
  INDEX idx_anggaran_awal (anggaran_awal)
) ENGINE=InnoDB;

CREATE TABLE progres (
  id_progres INT AUTO_INCREMENT PRIMARY KEY,
  id_proyek INT NOT NULL,
  created_by INT NOT NULL,
  tanggal_laporan DATE NOT NULL,
  persentase INT NOT NULL,
  keterangan TEXT NOT NULL,
  dokumentasi VARCHAR(255),
  is_locked TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_progres_proyek
    FOREIGN KEY (id_proyek) REFERENCES proyek(id_proyek)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_progres_user
    FOREIGN KEY (created_by) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT cek_persentase CHECK (persentase >= 0 AND persentase <= 100),
  INDEX idx_id_proyek (id_proyek),
  INDEX idx_created_by (created_by),
  INDEX idx_is_locked (is_locked),
  INDEX idx_tanggal_laporan (tanggal_laporan),
  INDEX idx_persentase (persentase)
) ENGINE=InnoDB;

CREATE TABLE anggaran_revisi (
  id_revisi INT AUTO_INCREMENT PRIMARY KEY,
  id_proyek INT NOT NULL,
  anggaran_lama DECIMAL(15,2) NOT NULL,
  anggaran_baru DECIMAL(15,2) NOT NULL,
  alasan TEXT NOT NULL,
  revised_by INT NOT NULL,
  revised_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  CONSTRAINT fk_anggaran_revisi_proyek
    FOREIGN KEY (id_proyek) REFERENCES proyek(id_proyek)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_anggaran_revisi_user
    FOREIGN KEY (revised_by) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX idx_anggaran_revisi_proyek (id_proyek),
  INDEX idx_anggaran_revisi_user (revised_by),
  INDEX idx_anggaran_revisi_at (revised_at)
) ENGINE=InnoDB;

CREATE TABLE progres_revisi (
  id_revisi INT AUTO_INCREMENT PRIMARY KEY,
  id_progres INT NOT NULL,
  persentase_lama INT NOT NULL,
  persentase_baru INT NOT NULL,
  keterangan_lama TEXT NOT NULL,
  keterangan_baru TEXT NOT NULL,
  dokumentasi_lama VARCHAR(255),
  dokumentasi_baru VARCHAR(255),
  alasan TEXT NOT NULL,
  revised_by INT NOT NULL,
  revised_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  CONSTRAINT fk_progres_revisi_progres
    FOREIGN KEY (id_progres) REFERENCES progres(id_progres)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_progres_revisi_user
    FOREIGN KEY (revised_by) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT cek_persentase_revisi_lama CHECK (persentase_lama >= 0 AND persentase_lama <= 100),
  CONSTRAINT cek_persentase_revisi_baru CHECK (persentase_baru >= 0 AND persentase_baru <= 100),
  INDEX idx_progres_revisi_progres (id_progres),
  INDEX idx_progres_revisi_user (revised_by),
  INDEX idx_progres_revisi_at (revised_at)
) ENGINE=InnoDB;

CREATE TABLE password_reset_log (
  id_reset INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  email VARCHAR(150) NOT NULL,
  purpose ENUM('aktivasi','reset') NOT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME DEFAULT NULL,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  CONSTRAINT fk_reset_log_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX idx_reset_log_user (id_user),
  INDEX idx_reset_log_email (email),
  INDEX idx_reset_log_purpose (purpose),
  INDEX idx_reset_log_requested_at (requested_at)
) ENGINE=InnoDB;

CREATE TABLE password_reset_token (
  id_token INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_reset INT DEFAULT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  purpose ENUM('aktivasi','reset') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  CONSTRAINT fk_reset_token_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_reset_token_log
    FOREIGN KEY (id_reset) REFERENCES password_reset_log(id_reset)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  INDEX idx_reset_token_user (id_user),
  INDEX idx_reset_token_hash (token_hash),
  INDEX idx_reset_token_expire (expires_at),
  INDEX idx_reset_token_used (used_at)
) ENGINE=InnoDB;

CREATE TABLE email_log (
  id_email INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT DEFAULT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  status ENUM('terkirim','gagal') NOT NULL,
  error_message TEXT,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_email_log_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  INDEX idx_email_log_user (id_user),
  INDEX idx_email_log_email (email),
  INDEX idx_email_log_status (status),
  INDEX idx_email_log_sent_at (sent_at)
) ENGINE=InnoDB;

CREATE TABLE login_log (
  id_login INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  login_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  CONSTRAINT fk_login_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX idx_login_user (id_user),
  INDEX idx_login_at (login_at)
) ENGINE=InnoDB;

CREATE TABLE user_status_log (
  id_log INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  actor_user_id INT DEFAULT NULL,
  action ENUM('dibuat','aktivasi','arsip_admin','nonaktif_admin','nonaktif_sendiri') NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  CONSTRAINT fk_status_log_user
    FOREIGN KEY (id_user) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_status_log_actor
    FOREIGN KEY (actor_user_id) REFERENCES `user`(id_user)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  INDEX idx_status_log_user (id_user),
  INDEX idx_status_log_action (action),
  INDEX idx_status_log_created_at (created_at)
) ENGINE=InnoDB;
