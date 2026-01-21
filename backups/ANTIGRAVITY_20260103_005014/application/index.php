<?php
/**
 * index.php
 * Redirection vers le dashboard principal
 */

require_once 'auth.php';
header('Location: dashboard.php');
exit;
