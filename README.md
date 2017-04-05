# PHP Layout compositor for Drupal

This module uses the [makinacorpus/php-layout](https://github.com/makinacorpus/php-layout)
library to provide a powerful layout builder atop of Drupal nodes.

# Installation

For this to work, your Drupal installation needs to be composer based:

```sh
composer require makinacorpus/drupal-phplayout
drush -y en phplayout
```

Default configuration will automatically provide a single layout for each node
in the ``content`` region, for further configuration, you may want to
implement a Symfony event plugged on the
``MakinaCorpus\Drupal\Layout\Event\CollectLayoutEvent::EVENT_NAME``
event in order to change the layout discovery and loading business logic.

# Runtime configuration

Per default, this module renders layouts using a Twitter Bootstrap 3 compatible
HTML layout for which you may want to configure a few behaviours.

First, you have to understand that each layout is based upon a region. In order
to configure their behaviours you will configure it on a per region basis,
providing an array of option per region. Those array keys are detailed in the
following chapters.

## Disable the *container* div

Per default the layouts grids will be surrounded by a Bootstrap container div,
but if your page template already contain a container, this is lead to invalid
Bootstrap grid system usage (indeed, containers should not be nested). To
disable the container on a per region basis, set this option:

```php
$conf['phplayout_region_options'] = [
    'my_region' => [
        // ... other options
        'container-none': true
    ],
];
```

## Make the *container* div being a *container-fluid* div

On the other hand, if you wish to keep the container div but wishes to make it
fluid, set this options:

```php
$conf['phplayout_region_options'] = [
    'my_region' => [
        // ... other options
        'container-fluid': true
    ],
];
```

Please note that usage of the ``container-none`` option will superseed this one.

## Example of a complete configuration

Just for the sake of example, here is a complete example:

```php
$conf['phplayout_region_options'] = [
    'default' => [
        'container-drop': true
    ],
    'content' => [
        'container-fluid': true
    ],
    'header' => [
        'container-fluid': false
    ],
    'footer' => [
        'container-fluid': false
    ],
    'sidebar_left' => [
        'container-none': true
    ],
];
```

The ``default`` key is not a region name, but default options applied if none
are providen for a specific region.

# Change the default grid HTML markup

This is sadly not supported yet but will be very soon.

@todo make bare grid html overridable

