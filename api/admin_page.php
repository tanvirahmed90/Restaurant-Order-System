<?php
// api/admin_page.php - Serverless Router Entrypoint for Admin Operations on Vercel
require_once __DIR__ . '/../config/session.php';
chdir(dirname(__DIR__));
require_once __DIR__ . '/../admin/index.php';
