<?
$CONFIG['Smarty']['Smarty'] = array(	'compile_dir' => "cache/templates_compile/",
										'cache_dir' => "cache/templates_cache/",
										'template_dir' => "templates/",
										'default_layout' => "general",
										'default_plugins_dir' => STINGLE_PATH . 'smarty_plugins/',
										'pluginDirs' => array('incs/smarty_local_plugins/'),
										
										'errors_module' => "error",
										'error_page' => "error",
										'error_404_page' => "404",
										'exception_page' => "exception"
									);
?>