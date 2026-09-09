<?php
require_once __DIR__ . '/includes/bootstrap.php';
cms_logout();
header('Location: login.php');
exit;
