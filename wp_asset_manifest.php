<?php
/**
 * wp-asset-manifest
 * Access assets via manifest
 * 
 */
 
// Defines a glob pattern which is used to scan recursively for manifest file
//define('WP_ASSET_MANIFEST', '{manifest,assets}.json');


/**
 * Asset path helper
 */
if (!function_exists('asset_path')) {
  
  function asset_path($logical_path, $options = array()) {
    // Merge options with defaults
    $options = array_merge(array(
      'base_uri' => get_template_directory_uri(),
      'base_dir' => get_template_directory(),
      'manifest' => defined("WP_ASSET_MANIFEST") ? WP_ASSET_MANIFEST : '{manifest,assets}.json'
    ), $options);
    
    // Setup directory root
    $base_dir = $options['base_dir'];
    $base_uri = $options['base_uri'];
    
    // Setup asset uri by logical path
    $assetPath = join('/', array($base_uri, trim($logical_path, '/')));

    // Find manifest file
    $manifest = "";
    
    // Iterate directories recursively 
    $dir_iterator = new RecursiveDirectoryIterator($base_dir);
    $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $file) {
      if (is_dir($file) === true && (realpath($file) === $base_dir || basename($file) !== ".") && basename($file) !== "..") {
        // Glob
        $pattern = join('/', array(rtrim($file, "/"), rtrim($options['manifest'], "/")));
        $files = glob($pattern, GLOB_BRACE);
        if (count($files)) {
          $manifest = $files[0];
          break;
        }
      }
    }
    
    if (!$manifest || !file_exists($manifest)) {
      // Manifest not found
      return $assetPath;
    }
    
    // Manifest found
    
    // Get relative manifest dir
    if (substr($manifest, 0, strlen($base_dir)) == $base_dir) {
      $manifest_dir = dirname(ltrim(substr($manifest, strlen($base_dir)), "/"));
    }
    
    // Read file
    $json = json_decode(file_get_contents($manifest), TRUE);
    if ($json) {
      if (isset($json['assets'])) {
        $assets = $json['assets'];
        // Find asset
        if (isset($assets[$logical_path])) {
          // Asset found
          $assetPath = join('/', array($base_uri, $manifest_dir, trim($assets[$logical_path], '/')));
        }
      }
    }
    return $assetPath;
  }
}


/**
 * Asset action hooks
 */
if (!function_exists('wpam_setup_asset_paths')) {

  function wpam_setup_asset_paths() {
    // Find template styles
    global $wp_styles;
    $base_uri = get_template_directory_uri();
    foreach($wp_styles->registered as $name => $dep) {
      if (substr($dep->src, 0, strlen($base_uri)) == $base_uri) {
        // Inject asset path
        $logical_path = ltrim(substr($dep->src, strlen($base_uri)), "/");
        $dep->src = asset_path($logical_path);
      } 
    }
    // Find template scripts
    global $wp_scripts;
    foreach($wp_scripts->registered as $name => $dep) {
      if (substr($dep->src, 0, strlen($base_uri)) == $base_uri) {
        // Inject asset path
        $logical_path = ltrim(substr($dep->src, strlen($base_uri)), "/");
        $dep->src = asset_path($logical_path);
      } 
    }
  }
  
  // Add action hooks
  add_action('wp_print_scripts', 'wpam_setup_asset_paths');
  add_action('wp_print_styles', 'wpam_setup_asset_paths');
  
}

?>