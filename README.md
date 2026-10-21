# Filament Media Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/majid-shafik/filament-media-library.svg?style=flat-square)](https://packagist.org/packages/majid-shafik/filament-media-library)
[![Total Downloads](https://img.shields.io/packagist/dt/majid-shafik/filament-media-library.svg?style=flat-square)](https://packagist.org/packages/majid-shafik/filament-media-library)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

An advanced, secure, and modern centralized **Media Library Plugin for Filament 5** featuring:
- 🚀 **SHA-256 Deduplication**: Automatic byte-level fingerprinting to prevent uploading duplicates and save disk storage.
- 🌳 **Hierarchical Categories Tree**: Interactive folder organization with cyclic recursion protection.
- 🛡️ **Deep Security Sanitization**: Magic byte signatures, GD re-encoding to strip malicious EXIF/payloads, and decompression bomb protection.
- 🔗 **Smart Usages Tracking**: Prevent breaking links by tracking where media is used across content, sections, and page builder blocks.
- ⚡ **Native RTL & Arabic Support**: Built from the ground up for modern enterprise workflows with full RTL ergonomics.

---

## 📦 Installation

You can install the package via composer:

`ash
composer require majid-shafik/filament-media-library
`

Publish and run the migrations:

`ash
php artisan vendor:publish --tag=filament-media-library-migrations
php artisan migrate
`

Optionally publish the config file:

`ash
php artisan vendor:publish --tag=filament-media-library-config
`

---

## 🚀 Registering with Filament Panel

Register the plugin in your AdminPanelProvider:

`php
use MajidShafik\FilamentMediaLibrary\FilamentMediaLibraryPlugin;

public function panel(Panel ): Panel
{
    return 
        // ...
        ->plugins([
            FilamentMediaLibraryPlugin::make(),
        ]);
}
`

---

## 🎨 Form Component Usage

Use MediaImagePicker directly inside any Filament Form, Infolist, or PageBuilder Block:

`php
use MajidShafik\FilamentMediaLibrary\Components\MediaImagePicker;

MediaImagePicker::make(
    imagePathField: 'image',
    mediaIdField: 'image_media_id',
    label: 'الصورة البارزة',
    directory: 'uploads/contents',
    required: true,
)
`

---

## 🛠️ Console Commands

To calculate SHA-256 fingerprints for legacy media:

`ash
php artisan media:backfill-hashes --all
`

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
