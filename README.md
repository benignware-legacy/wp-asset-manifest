wp-asset-manifest
=================

> Access assets via manifest


To make wordpress aware of your precompiled assets, include the script into your theme: 
```php
require_once("wp_asset_manifest.php");
```

Place a file named `manifest.json` or `assets.json` which contains your asset mappings into your template directory. 
> If you`re using [Mincer sprockets engine](https://github.com/nodeca/mincer "View mincer project on github.com"), an asset manifest is autogenerated for you.

##### Example 
```json
{
  "assets": {
    "app.js": "app-6f3aabb494f7719cb9b7f22a2725ca8b.js",
    "app.css": "app-72d0361ab0c6555db16c44dc2b267673.css",
    "image.png": "image-e32906c53e045fb9242b83bb63aa9b38.png"
  }
}
```

### Action hook
The script provides an action hook to automatically adjust enqueued template scripts to their corresponding asset paths.

### Asset path helper
Also made accessible is a helper that can be used to reference assets throughout the template: 

```php
string function asset_path( string $logical_path, array $options )
```

The following options can be provided optionally: `manifest` `base_url` `base_dir`
 
##### Example
```php
asset_path('image.png');

```

### Manifest path
By default the script will scan your template directory recursively for a file matching `{manifest,assets}.json`.
You can customize the glob pattern by the use of a constant:
```php
define('WP_ASSET_MANIFEST', '**/{manifest,assets}.json');
``` 

