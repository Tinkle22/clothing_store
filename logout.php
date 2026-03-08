<?php
// logout.php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logoutUser();
setFlashMessage('info', 'You have been successfully logged out.');
redirect('/index.php');
