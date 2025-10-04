CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_uid CHAR(24) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('pending','processing','done','error') DEFAULT 'pending',
  style VARCHAR(64) DEFAULT 'line-art',
  message TEXT
);

CREATE TABLE IF NOT EXISTS job_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  src_filename VARCHAR(255) NOT NULL,
  out_filename VARCHAR(255) DEFAULT NULL,
  status ENUM('queued','working','done','error') DEFAULT 'queued',
  error TEXT,
  FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  url VARCHAR(512) NOT NULL,
  size INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
