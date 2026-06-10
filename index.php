<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect_to('dashboard.php');
}

redirect_to('login.php');

