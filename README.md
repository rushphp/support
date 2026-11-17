[English](README.md) | [Português](README.pt.md)

![PHP](https://img.shields.io/badge/PHP-%20^8.3-777BB4)
![PHPStan-Level](https://img.shields.io/badge/PHPStan-Level%209-224488)
![License](https://img.shields.io/badge/License-MIT-777)

# RUSH Support

**RUSH** — Registry-driven Unified Shell Helper.

Shared helpers used by the RUSH manager and its tools. No runtime dependencies.

> **Pre-1.0:** minor releases may include breaking changes.

## ✨ Features

- **`FileSystem`**: safe directory creation, atomic writes, recursive copy/delete.
- **`Operator`**: value object for the shared operator identity.
- **`OperatorFile`**: reads and writes `.rush/operator.json`.

## 📦 Installation

```bash
composer require rushphp/support
```

### Requirements

- PHP 8.3 or newer.
- No runtime dependencies.

## 🧩 Usage

```php
use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;

FileSystem::ensureDirectory('/srv/app/.rush/code');

OperatorFile::save('/srv/app', new Operator('Developer', 'developer@example.com'));

$operator = OperatorFile::load('/srv/app');

echo $operator->name;
```

The operator file is stored at:

```text
.rush/operator.json
```

and is mandatory for tool commands that record who performed an action.

## 🤝 Contributions

Contributions are welcome. Open an issue or submit a pull request.

## 📄 License

MIT. See [LICENSE](LICENSE).
