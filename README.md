# Laravel Versioned Model

[![Latest Version on Packagist](https://img.shields.io/packagist/v/thumbrise/laravel-versioned-model.svg?style=flat-square)](https://packagist.org/packages/thumbrise/laravel-versioned-model)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/thumbrise/laravel-versioned-model/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/thumbrise/laravel-versioned-model/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/thumbrise/laravel-versioned-model/code-quality.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/thumbrise/laravel-versioned-model/actions?query=workflow%3Acode-quality+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/thumbrise/laravel-versioned-model.svg?style=flat-square)](https://packagist.org/packages/thumbrise/laravel-versioned-model)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat-square)](phpstan.neon)

A powerful and easy-to-use Laravel package that provides automatic versioning for your Eloquent models. Track changes, create snapshots, compare versions, and revert to previous states with a simple API.

---

📖 **[Documentation](#documentation)** • 
🤝 **[Contributing](docs/CONTRIBUTING.md)** • 
📋 **[Changelog](CHANGELOG.md)** • 
📜 **[License](#license)**

---

## Features

✨ **Full Snapshot Versioning** - Store complete model state at each version  
🔄 **Automatic Version Tracking** - Track who, when, and what changed  
📊 **Diff Comparison** - Compare any two versions to see what changed  
⏮️ **Version Rollback** - Easily revert to any previous version  
📝 **Field History** - Get complete history of changes for specific fields  
🔒 **Transaction Safety** - All operations wrapped in database transactions  
🎯 **Selective Tracking** - Exclude specific fields from versioning  
⚡ **Performance Optimized** - Uses `latestOfMany()` for efficient queries  
🔗 **Polymorphic Relations** - Track changes made by any model (users, admins, etc.)

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x

## Installation

You can install the package via composer:

```bash
composer require thumbrise/laravel-versioned-model
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="versioned-model-migrations"
php artisan migrate
```

## Usage

### Basic Setup

Add the `HasVersions` trait to your model:

```php
use Illuminate\Database\Eloquent\Model;
use Thumbrise\LaravelVersionedModel\Traits\HasVersions;

class Article extends Model
{
    use HasVersions;

    protected $fillable = ['title', 'content', 'status'];
}
```

### Creating Versions

Use the `updateVersioned()` method instead of regular `update()` to automatically create a version:

```php
$article = Article::create([
    'title' => 'My First Article',
    'content' => 'Initial content',
    'status' => 'draft'
]);

// Update and create a version snapshot
$article->updateVersioned([
    'title' => 'My Updated Article',
    'content' => 'Updated content'
]);

// Each update creates a new version
$article->updateVersioned(['status' => 'published']);
```

### Retrieving Versions

```php
// Get all versions
$versions = $article->getVersions();

// Get a specific version
$version = $article->getVersion(2);

// Get the latest version
$latestVersion = $article->getLatestVersion();

// Access version data
echo $version->version;        // Version number
echo $version->snapshot;       // Full model snapshot as array
echo $version->created_at;     // When this version was created
echo $version->changer;        // Who made the change (authenticated user)
```

### Comparing Versions

Get the differences between any two versions:

```php
// Compare version 1 and version 2
$diff = $article->getDiff(1, 2);

// Compare version 1 with current state
$diff = $article->getDiff(1, null);

// Compare initial state (before first version) with current state
$diff = $article->getDiff(null, null);

// Result format:
// [
//     'title' => [
//         'old' => 'My First Article',
//         'new' => 'My Updated Article'
//     ],
//     'content' => [
//         'old' => 'Initial content',
//         'new' => 'Updated content'
//     ]
// ]
```

### Reverting to Previous Versions

```php
// Revert to version 1
$article->revertToVersion(1);

// This creates a new version with the old data
echo $article->title; // Back to the original title
```

### Field History

Track the complete history of changes for specific fields:

```php
// Get history for a single field
$history = $article->getFieldHistory('status');

// Result:
// [
//     [
//         'version' => 1,
//         'value' => 'draft',
//         'changed_at' => Carbon instance,
//         'changer' => User model
//     ],
//     [
//         'version' => 2,
//         'value' => 'published',
//         'changed_at' => Carbon instance,
//         'changer' => User model
//     ]
// ]

// Get history for multiple fields
$history = $article->getFieldsHistory(['title', 'status']);
```

### Excluding Fields from Versioning

By default, `created_at` and `updated_at` are excluded. You can exclude additional fields:

```php
class Article extends Model
{
    use HasVersions;

    protected static function getExcludedVersionFields(): array
    {
        return ['view_count', 'last_viewed_at'];
    }
}
```

### Custom Changer Resolution

By default, the package tracks changes made by the authenticated user. You can customize this:

```php
class Article extends Model
{
    use HasVersions;

    protected static function resolveChanger(): ?Model
    {
        // Track by admin instead of regular user
        return auth()->guard('admin')->user();
        
        // Or use a different model entirely
        return SystemLog::getCurrentActor();
    }
}
```

### Using Relationships

```php
// Eager load versions
$article = Article::with('versions')->find(1);

// Eager load only the latest version
$article = Article::with('latestVersion')->find(1);

// Query versions
$article->versions()
    ->where('created_at', '>', now()->subDays(7))
    ->get();
```

## Advanced Usage

### Manual Version Creation

While `updateVersioned()` is the recommended approach, you can also create versions manually:

```php
use Thumbrise\LaravelVersionedModel\Models\ModelVersion;

ModelVersion::create([
    'model_type' => $article->getMorphClass(),
    'model_id' => $article->getKey(),
    'changer_type' => auth()->user()?->getMorphClass(),
    'changer_id' => auth()->id(),
    'version' => 1,
    'snapshot' => [
        'title' => 'Custom Title',
        'content' => 'Custom Content'
    ]
]);
```

### Accessing Version Relationships

```php
$version = $article->getVersion(1);

// Get the model this version belongs to
$model = $version->model; // Returns Article instance

// Get who made this change
$user = $version->changer; // Returns User instance (or null)
```

## Database Schema

The package creates a `model_versions` table with the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| model_type | string | Polymorphic type of the versioned model |
| model_id | bigint | ID of the versioned model |
| changer_type | string | Polymorphic type of who made the change (nullable) |
| changer_id | bigint | ID of who made the change (nullable) |
| version | integer | Sequential version number |
| snapshot | json/jsonb | Complete snapshot of model state |
| created_at | timestamp | When this version was created |

Indexes:
- `(model_type, model_id, version)` - Unique constraint
- `(model_type, model_id)` - Query optimization
- `(changer_type, changer_id)` - Polymorphic relation

## Testing

```bash
composer test
```

Run code quality checks:

```bash
composer lint
```

Fix code style:

```bash
composer fmt
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](docs/CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability, please email Ruslan Kokoev at ruslan.kokoev.1999@gmail.com. All security vulnerabilities will be promptly addressed.

## Credits

- [Ruslan Kokoev](https://github.com/thumbrise)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
