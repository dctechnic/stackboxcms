<?php
// @todo: Include different config files for each HOST (ex. 'localhost.cfg.php' and 'domain.com.cfg.php') for different environments

// Configuration
$cfg = array();
$cfg['cx']['env']['https'] = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') ? false : true;
$cfg['cx']['request'] = (isset($_GET['url']) ? urldecode($_GET['url']) : '' );
$cfg['cx']['path_root'] = dirname(dirname(__FILE__));

$cfg['cx']['dir_www'] = '/www/';
$cfg['cx']['dir_assets'] = $cfg['cx']['dir_www'] . 'assets/';
$cfg['cx']['dir_assets_admin'] = $cfg['cx']['dir_assets'] . 'admin/';
$cfg['cx']['dir_lib'] = '/lib/';
$cfg['cx']['dir_modules'] = '/app/';
$cfg['cx']['dir_themes'] = $cfg['cx']['dir_www'] . 'themes/';

$cfg['cx']['path_app'] = dirname(__FILE__);
$cfg['cx']['path_lib'] = $cfg['cx']['path_root'] . $cfg['cx']['dir_lib'];
$cfg['cx']['path_modules'] = $cfg['cx']['path_root'] . $cfg['cx']['dir_modules'];
$cfg['cx']['path_public'] = $cfg['cx']['path_root'] . $cfg['cx']['dir_www'];
$cfg['cx']['path_themes'] = $cfg['cx']['path_root'] . $cfg['cx']['dir_themes'];

$cfg['cx']['url'] = 'http' . (($cfg['cx']['env']['https']) ? 's' : '' ) . '://' . $_SERVER['HTTP_HOST'] . '/' . str_replace('\\', '/', substr($cfg['cx']['path_root'] . $cfg['cx']['dir_www'], strlen($_SERVER['DOCUMENT_ROOT'])));
$cfg['cx']['url_themes'] = $cfg['cx']['url'] . str_replace($cfg['cx']['dir_www'], '', $cfg['cx']['dir_themes']);
$cfg['cx']['url_assets'] = $cfg['cx']['url'] . str_replace($cfg['cx']['dir_www'], '', $cfg['cx']['dir_assets']);
$cfg['cx']['url_assets_admin'] = $cfg['cx']['url'] . str_replace($cfg['cx']['dir_www'], '', $cfg['cx']['dir_assets_admin']);

// Debug?
$cfg['cx']['debug'] = true;

// In Development Mode?
$cfg['cx']['mode']['development'] = true;

// Error Reporting
$cfg['cx']['error_reporting'] = true;

// Use Apache's mod_rewrite on URLs?
$cfg['cx']['mod_rewrite'] = true;

// Defaults
$cfg['cx']['default']['module'] = 'page';
$cfg['cx']['default']['action'] = 'index';
$cfg['cx']['default']['theme'] = 'default';
$cfg['cx']['default']['theme_template'] = 'index';

// Database - Param names to match Zend_Config
$cfg['cx']['database']['master']['adapter'] = 'MySQL';
$cfg['cx']['database']['master']['host'] = 'localhost';
$cfg['cx']['database']['master']['username'] = 'test';
$cfg['cx']['database']['master']['password'] = 'password';
$cfg['cx']['database']['master']['dbname'] = 'cx_cms';
$cfg['cx']['database']['master']['options'] = array(
	PDO::ERRMODE_EXCEPTION => true,
	PDO::ATTR_PERSISTENT => false,
	PDO::ATTR_EMULATE_PREPARES=> true
	);

// Session Settings
$cfg['cx']['session']['lifetime'] = 28000;

// Locale Settings
$cfg['cx']['i18n']['charset'] = 'UTF-8';
$cfg['cx']['i18n']['language'] = 'en_US';
$cfg['cx']['i18n']['timezone'] = 'America/Chicago';

// Global setup
date_default_timezone_set($cfg['cx']['i18n']['timezone']);
ini_set("session.gc_maxlifetime", $cfg['cx']['session']['lifetime']);

return $cfg;