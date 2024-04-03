<?php

return [

	'target_php_version' => '8.2',

	'directory_list' => [
		'src/',
		'tests/',
		'vendor/',
	],

	'exclude_analysis_directory_list' => [
		'vendor/',
	],


	'processes'                       => 1,

	'analyze_signature_compatibility' => true,
	'simplify_ast'                    => true,
	'generic_types_enabled'           => true,
	'scalar_implicit_cast'            => false,

];

