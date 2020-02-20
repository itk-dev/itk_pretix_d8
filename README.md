# ITK Pretix

This Drupal 8 module creates a new field type that enables a link to
https://pretix.eu/ to be made.

Through the Pretix API it is possible to add, change or remove Pretix
event date entries from the Drupal interface.

1) Add a new field of pretix_field_type to an entity.
2) Add a pretix connection through the settings (local.settings.php).
3) Watch the magic happen.

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
