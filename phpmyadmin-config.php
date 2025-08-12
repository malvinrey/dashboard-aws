<?php

/**
 * phpMyAdmin Configuration for SCADA Dashboard
 * Place this file in C:\phpmyadmin\config.inc.php
 */

// Database server configuration
$cfg['Servers'][$i]['host'] = 'localhost';
$cfg['Servers'][$i]['port'] = '3306';
$cfg['Servers'][$i]['user'] = 'root';
$cfg['Servers'][$i]['password'] = 'anjingbela12';
$cfg['Servers'][$i]['auth_type'] = 'config';

// Default database
$cfg['Servers'][$i]['only_db'] = 'scada_dashboard';

// Security settings
$cfg['LoginCookieValidity'] = 3600; // 1 hour
$cfg['LoginCookieStore'] = 0;
$cfg['LoginCookieDeleteAll'] = true;

// UI settings
$cfg['ThemeDefault'] = 'pmahomme';
$cfg['DefaultLang'] = 'en';
$cfg['ServerDefault'] = 1;

// Features
$cfg['ShowPhpInfo'] = false;
$cfg['ShowChgPassword'] = false;
$cfg['ShowCreateDb'] = false;
$cfg['ShowServerInfo'] = false;

// Export settings
$cfg['Export']['format'] = 'sql';
$cfg['Export']['compression'] = 'none';

// Import settings
$cfg['Import']['allow_interrupt'] = true;
$cfg['Import']['skip_queries'] = 0;

echo "phpMyAdmin configuration loaded successfully!";
