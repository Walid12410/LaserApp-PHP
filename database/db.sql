USE laser_app;



CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100), 
    last_name VARCHAR(100), role VARCHAR(50), email VARCHAR(255) UNIQUE, password VARCHAR(255), phone_number VARCHAR(20),
    role VARCHAR(50), 
    email VARCHAR(255) UNIQUE, 
    password VARCHAR(255), 
    phone_number VARCHAR(20) 
);


CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_uid VARCHAR(64) NOT NULL UNIQUE,
  user_id INT NOT NULL,
  status ENUM('queued','processing','done','failed') NOT NULL DEFAULT 'queued',
  options_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at TIMESTAMP NULL,
  INDEX (user_id),
  CONSTRAINT fk_jobs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  original_path VARCHAR(255) NOT NULL,
  result_png VARCHAR(255) DEFAULT NULL,
  result_svg VARCHAR(255) DEFAULT NULL,
  status ENUM('queued','processing','done','failed') NOT NULL DEFAULT 'queued',
  error_msg VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at TIMESTAMP NULL,
  INDEX (job_id),
  CONSTRAINT fk_files_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);


