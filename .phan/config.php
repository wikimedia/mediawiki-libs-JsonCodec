<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = [ 'src', 'vendor', 'tests' ];
$cfg['suppress_issue_types'] = [];
$cfg['exclude_analysis_directory_list'][] = 'vendor';

return $cfg;
