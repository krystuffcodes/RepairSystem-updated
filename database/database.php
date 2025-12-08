<?php

//Supports PDO and mysqli

// Use environment variables for Docker deployment, fallback to localhost for local development
$config = [
  'host' => getenv('DB_HOST') ?: 'host.docker.internal',
  'username' => getenv('DB_USER') ?: 'root',
  'password' => getenv('DB_PASSWORD') ?: '',
  'dbname' => getenv('DB_NAME') ?: 'repairsystem',
];

//for PDO and mysqli
return $config;
