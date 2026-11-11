<?php
/**
 * Auto-prepend file for vendor-bin/openapi-extractor/vendor/bin/generate-spec.
 * It makes the scoped vendor libraries and the app classes available to the
 * extractor. The app directory is passed in the environment variable
 * OPENAPI_APP_DIR.
 */

$appDir = getenv('OPENAPI_APP_DIR');

require $appDir . '/vendor-wrapped/autoload.php';

spl_autoload_register(function (string $class) use ($appDir) {
  $prefix = 'OCA\\CAFeVDBMembers\\';
  if (str_starts_with($class, $prefix)) {
    $file = $appDir . '/lib/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
      require $file;
    }
  }
}, true, true);
