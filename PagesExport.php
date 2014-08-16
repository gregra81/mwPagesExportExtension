<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
    require_once($IP."/extensions/PagesExport/PagesExport.php");
EOT;
        exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
		'name'           => 'PagesExport',
		'author'         => 'Greg',
		'description'    => 'Show all created pages from a specific date with an option for export'
);

$dir = dirname(__FILE__) . '/'; 
$wgExtensionMessagesFiles['PagesExport'] = $dir . '/PagesExport.i18n.php';
$wgAutoloadClasses['specialPagesExport'] = $dir . 'specialPagesExport.php'; # Tell MediaWiki to load the extension body.
$wgSpecialPages['specialPagesExport'] = 'specialPagesExport'; # Let MediaWiki know about your new special page.
$wgSpecialPageGroups['specialPagesExport'] = 'wiki';


?>