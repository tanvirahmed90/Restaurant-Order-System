<?php
// api/index.php - Serverless Router Entrypoint for Vercel
require_once __DIR__ . '/../config/session.php';
chdir(dirname(__DIR__));
require_once __DIR__ . '/../index.php';
