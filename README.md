# ITK Pretix

This Drupal 8 module creates a new field type that enables a link to
https://pretix.eu/ to be made.

Through the Pretix API it is possible to add, change or remove Pretix
event date entries from the Drupal interface.

1. Add a new field of type `pretix date field type` to an entity.
2. Add a new field of type `pretix event settings` to the entity.
3. Add a pretix connection through the settings (local.settings.php).
4. Watch the magic happen.

## Configuration

Go to `/admin/config/itk_pretix/pretixconfig` and enter your pretix details.

A hidden configuration option, `pretix_event_slug_template`, controls how pretix
event slugs (short forms) are generated. The default value is `!nid` and `!nid`
will be replaced with the actual node id when creating a pretix event.

To change the value of `pretix_event_slug_template`, set it in your site's
settings, e.g. (in `settings.local.php`):

```php
$config['itk_pretix.pretixconfig']['pretix_event_slug_template'] = 'dev-local';
```

If the value of `pretix_event_slug_template` is not empty, but `!nid` does not
occur in the value, `-!nid` will be appended and the final template will be
`dev-local-!nid`.

## Building assets

First, install tools and requirements:

```sh
yarn install
```

Build for development:

```sh
yarn encore dev --watch
```

Build for production:

```sh
yarn encore production
```

## Coding standards

The code must follw the [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards)

Check the coding standards by running

```sh
composer install
composer check-coding-standards
```

Apply the coding standards by running

```sh
composer apply-coding-standards
```

### Assets

Check the coding standards in assets by running

```sh
yarn coding-standards-check
```

Apply the coding standards by running

```sh
yarn coding-standards-apply
```
