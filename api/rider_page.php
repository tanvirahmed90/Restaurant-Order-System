<?php
// api/rider_page.php - Serverless Router Entrypoint for Courier Delivery on Vercel
require_once __DIR__ . '/../config/session.php';
chdir(dirname(__DIR__));
require_once __DIR__ . '/../rider.php';
