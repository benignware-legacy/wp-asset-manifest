<?php

// Defines a glob pattern which is used to scan recursively for manifest file
//define('WP_ASSET_MANIFEST', '{manifest,assets}.json');


/** 
 * brglob
 * Find files recursively by using glob pattern
 */
 
if (!function_exists('brglob')) {
  
  function brglob($pattern, $flags = 0) {
    
    // brace support
    $matches = array();
    $bpatterns = array("");
    preg_match_all("/\{([^\}]*)\}/", $pattern, $matches, PREG_OFFSET_CAPTURE);
    $end_index = 0;
    foreach($matches[1] as $i => $match) {
      $index = $match[1];
      $values = explode(",", $match[0]);
      $start_str = substr($pattern, $end_index, $index - 1 - $end_index);
      $end_index = $index + strlen($match[0]) + 1;
      if ($i == count($matches[1]) - 1) {
        $end_str = substr($pattern, $end_index);
      } else {
        $end_str = "";
      }
      $s = array();
      foreach ($values as $value) {
        foreach ($bpatterns as $part) {
          array_push($s, $part . $start_str . $value . $end_str);
        }
      }
      $bpatterns = $s;
      
    }
    
    $patterns = array();
    // 
    foreach($bpatterns as $pattern) {
      $end_index = 0;
      $rpatterns = array("");
      preg_match_all("/(?:^|\/)(\*\*)/", $pattern, $matches, PREG_OFFSET_CAPTURE);
      foreach($matches[1] as $match) {
        $index = $match[1];
        $start_str = substr($pattern, $end_index, $index - $end_index);
        $end_index = $index + strlen($match[0]) + 1;
        $end_str = substr($pattern, $end_index);
        $rootpath = $start_str;
        if (!is_dir($rootpath)) {
          continue; 
        }
        $fileinfos = new RecursiveIteratorIterator(
          new RecursiveDirectoryIterator($rootpath)
        );
        $s = array();
        $depth = substr_count(dirname($start_str), DIRECTORY_SEPARATOR);
        $max_depth = 0;
        $as_dirs = array();
        foreach($fileinfos as $pathname => $fileinfo) {
          if ($fileinfo->isDir() && basename($pathname) === ".") {
            $count = substr_count(dirname($pathname), DIRECTORY_SEPARATOR) - $depth;
            $max_depth = max($count, $max_depth);
          }
        }
        $p = "";
        for ($i = 0; $i < $max_depth; $i++) {
          $p.=  "*" . DIRECTORY_SEPARATOR;
          foreach ($rpatterns as $part) {
            array_push($s, $part . $start_str . $p . $end_str);
          }
        }
        $rpatterns = $s;
      } 
      $patterns = array_merge($patterns, $rpatterns);
    }
    //return;
    $result = array();
    foreach ($patterns as $pattern) {
      $result = array_merge($result, glob($pattern, $flags));
    }
    return $result;
  }
}


/**
 * Asset path helper
 */
if (function_exists('brglob') && !function_exists('asset_path')) {
  
  function asset_path($logical_path, $options = array()) {
    
    global $___asset_manifest_cache;
    
    // Merge options with defaults
    if (function_exists('get_template_directory_uri')) {
      
    }
    $base_uri = function_exists('get_template_directory_uri') ? get_template_directory_uri() : "";
    $base_dir = function_exists('get_template_directory') ? get_template_directory() : "";
    
    $options = array_merge(array(
      'base_uri' => $base_uri,
      'base_dir' => $base_dir,
      'manifest' => defined("WP_ASSET_MANIFEST") ? WP_ASSET_MANIFEST : '**/{manifest,assets}.json'
    ), $options);
    
    // Setup directory root
    $base_dir = $options['base_dir'];
    $base_uri = $options['base_uri'];
    
    // Setup asset uri by logical path
    $assetPath = join('/', array($base_uri, trim($logical_path, '/')));
    
    // Find manifest file
    // Iterate directories recursively
    
    if (!isset($___asset_manifest_cache)) {
      $___asset_manifest_cache = new stdClass();
      // Find manifest file
      $pattern = rtrim($base_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $options['manifest'];
      $files = brglob($pattern);
      if (count($files)) {
        // Manifest found
        $manifest_file = $files[0];
        $___asset_manifest_cache->file = $manifest_file;
        // Get relative manifest dir
        if (substr($manifest_file, 0, strlen($base_dir)) === $base_dir) {
          $___asset_manifest_cache->dir = dirname(ltrim(substr($manifest_file, strlen($base_dir)), "/"));
        }
        // Read file
        $json = json_decode(file_get_contents($manifest_file), TRUE);
        if ($json) {
          if (isset($json['assets'])) {
            $___asset_manifest_cache->json = $json;
          }
        }
      }
    }
    
    $manifest = isset($___asset_manifest_cache) ? $___asset_manifest_cache : null;
    
    if (!$manifest || !isset($manifest->file) || !isset($manifest->dir)) {
      // No manifest data
      return $assetPath;
    }
    
    $assets = $manifest->json['assets'];
    
    // Find asset
    if (isset($assets[$logical_path])) {
      // Asset found
      $assetPath = join('/', array($base_uri, $manifest->dir, trim($assets[$logical_path], '/')));
    }

    return $assetPath;
  }
}


/**
 * Asset action hooks
 */
if (!function_exists('wpam_setup_asset_paths')) {

  function wpam_setup_asset_paths() {
    $base_uri = get_template_directory_uri();
    // Find template styles
    global $wp_styles;
    if (isset($wp_styles)) {
      foreach($wp_styles->registered as $name => $dep) {
        if (substr($dep->src, 0, strlen($base_uri)) == $base_uri) {
          // Inject asset path
          $logical_path = ltrim(substr($dep->src, strlen($base_uri)), "/");
          $dep->src = asset_path($logical_path);
        } 
      }
    }
    // Find template scripts
    global $wp_scripts;
    if (isset($wp_scripts)) {
      foreach($wp_scripts->registered as $name => $dep) {
        if (substr($dep->src, 0, strlen($base_uri)) == $base_uri) {
          // Inject asset path
          $logical_path = ltrim(substr($dep->src, strlen($base_uri)), "/");
          $dep->src = asset_path($logical_path);
        } 
      }
    }
  }
  
  if (function_exists('add_action')) {
    // Add action hooks
    add_action('wp_print_scripts', 'wpam_setup_asset_paths');
    add_action('wp_print_styles', 'wpam_setup_asset_paths');
  }
}



?>
