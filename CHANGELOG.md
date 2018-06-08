# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html), starting with v1.1.0.


## 1.3.0 - TBD

### Added

- Dependency to li3_behaviors added, as this plugin will be integrated into lithium 2.0.
  This allows Managing and loading of Behaviors on Models like this:

  ```php
    // ...
    class Posts extends \lithium\data\Model {

       use li3_behaviors\data\model\Behaviors;

       protected $_actsAs = [
           'Softdeletable' => ['field' => 'deleted']
       ];
        
       // ...
  ```

  The Behavior Trait makes it easy to attach/detach and/or configure Behaviors on Models
  like that:

  ```php
    // Bind the Softdeletable behavior with configuration.
    Posts::bindBehavior('Softdeletable', ['field' => 'slug', 'label' => 'title']);

    // Accessing configuration.
    Posts::behavior('Softdeletable')->config();
    Posts::behavior('Softdeletable')->config('field');

    // Updating configuration.
    Posts::behavior('Softdeletable')->config('field', 'alt');

    // Unbinding it again.
    Posts::unbindBehavior('Softdeletable');
  ```

  See [github.com/UnionOfRad/li3_behaviors](https://github.com/UnionOfRad/li3_behaviors)
  for more information on how to use Behaviors.

- Most of the BaseModels core functionality is going to be moved to Behaviors to allow
  for easier usage of these, without the need to derive your Models from BaseModel.
  To simplify this even more, we split the Code in the BaseModel into a Trait 
  `Base` that holds some of the generic functionality for Models.

- Added new Behavior `SoftDeletable` which allows soft-deletion of records, i.e. marking
  them as deleted, instead of removing them physically. Usage is identical to the
  imlementation in the 1.* releases, so there is no need to adapt your code accordingly.

  This Behavior can be installed into your Models like this:

  ```php
       protected $_actsAs = [
           'Softdeletable' => ['field' => 'deleted']
       ];
  ```

- Added new Behavior `Revisionable` which allows creation of Revisions upon Model::save().
  If a Model uses this Behavior but disables the `revision` functionality, it will still
  save a `created` and `updated` timestamp, therefore its usage is a bit more broad than
  the name might suggest.

  This Behavior can be installed into your Models like this:

  ```php
       protected $_actsAs = [
         'Revisionable' => [
            'timestamps' => true,
            'revisions' => true,
            // 'revisions' => function($entity, $options, $behavior){ /* ... */ return true; },
            'class' => '\radium\models\Revisions',  // has to implement add() and restore()
            'fields' => [
                'revision' => 'revision_id',
                'created' => 'created',
                'updated' => 'updated',
            ],
         ]
       ];
  ```

- Added new attribute to Models Schema `annotation` which allows easy implementation of additional
  help-text for a field to be entered. In the scaffolded views, it will be rendered between label
  and the input-field.

  ```php
  // in Model class
  protected $_schema = [
      'count' => ['type' => 'int', 'annotation' => 'Please enter only numbers.'],
  ]
  ```

- Added new method on Scaffolded Controllers, named `schema` which displays a comprehensible list
  of fields and other useful information about all fields of a model Schema definition.
- Added Parsedown as library, therefore implementing the Markdown Converter.
- Added more DataObject information in scaffolded-views, i.e. `status`, `type` as well
  as `created` and `updated` information to default views.

### Changed

- Renamed `Versions` Model to `Revisions`
- The `BaseModel` has been split into `BaseModel` and `DataModel`. Only `DataModel` are
  thought to be used as Scaffolded Models.
- Uses Trait for Scaffold Logic, therefore allows for easier integration of Scaffold
  into User-land Controllers.
- Added new layout for scaffolded views, named `scaffold` for easier separation

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.


## 1.2.0 - 2018-04-30

### Added
- Changelog

### Changed
- Switched dependencies completely, requires lithium 1.2 (new mongodb-driver) to run

### Removed
- Removed Dependency for PHP constraint, lithium and alcaeus MongoDB compatibility-layer

### Fixed
- Wrong class usage of Neon Renderer


## 1.1.2 - 2017-05-05

### Added
- Nothing.

### Changed
- Dependency Management 

### Removed
- dependency to alcaeus MongoDB compatibility layer

### Fixed
- NeonRenderer
- Doing faceted searches via URL parameters in scaffolded views


## 1.1.1 - 2017-05-05

### Improvements
- Use Handlebars via Composer

## 1.1.0 - 2017-04-26

### Backwards Incompatible Changes
- Requires PHP7.0^


## 1.0.0 - 2017-04-26

Starting Point of Versioning and Releases.
https://namingschemes.com/2-D_Objects

