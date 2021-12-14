<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [Column sorting for Laravel 5.5-8](#column-sorting-for-laravel-55-8)
- [Setup](#setup)
  - [Composer](#composer)
    - [Laravel's >=5.5 auto discovery](#laravels-55-auto-discovery)
    - [Manual installation (pre 5.5)](#manual-installation-pre-55)
  - [Publish configuration](#publish-configuration)
- [Usage](#usage)
  - [Blade Extension](#blade-extension)
  - [Configuration in few words](#configuration-in-few-words)
  - [Font Awesome (default font classes)](#font-awesome-default-font-classes)
    - [Font Awesome 5](#font-awesome-5)
  - [Full Example](#full-example)
    - [Routes](#routes)
    - [Controller's `index()` method](#controllers-index-method)
    - [View (_pagination included_)](#view-pagination-included)
- [HasOne / BelongsTo Relation sorting](#hasone--belongsto-relation-sorting)
  - [Define hasOne relation](#define-hasone-relation)
  - [Define belongsTo relation](#define-belongsto-relation)
  - [Define `$sortable` arrays](#define-sortable-arrays)
  - [Blade and relation sorting](#blade-and-relation-sorting)
- [ColumnSortable overriding (advanced)](#columnsortable-overriding-advanced)
- [Aliasing](#aliasing)
  - [Using `withCount()`](#using-withcount)
- [Exception to catch](#exception-to-catch)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# Column sorting for Laravel 5.5-8 and above
# This repository is a fork of the Laravel Column Sorter originally created by https://github.com/Kyslik/column-sortable.
I have modified the code so now it works for up to 4 levels of nesting such as ->sortable(['relation1.subrelation2.subrelation3.subrelation4.columnName' => 'desc'])

Please raise an issue if you find any bug or want to improve. Thank you
