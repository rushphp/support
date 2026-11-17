[English](README.md) | [Português](README.pt.md)

![PHP](https://img.shields.io/badge/PHP-%20^8.3-777BB4)
![PHPStan-Level](https://img.shields.io/badge/PHPStan-Level%209-224488)
![License](https://img.shields.io/badge/License-MIT-777)

# RUSH Support

**RUSH** — Registry-driven Unified Shell Helper.

Helpers compartilhados usados pelo manager do RUSH e suas tools. Sem
dependências de runtime.

> **Pré-1.0:** releases minor podem incluir mudanças incompatíveis.

## ✨ Recursos

- **`FileSystem`**: criação segura de diretórios, escrita atômica e cópia/remoção recursiva.
- **`Operator`**: tipo de valor para a identidade compartilhada do operador.
- **`OperatorFile`**: leitura e escrita de `.rush/operator.json`.

## 📦 Instalação

```bash
composer require rushphp/support
```

### Requisitos

- PHP 8.3 ou superior.
- Sem dependências de runtime.

## 🧩 Uso

```php
use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;

FileSystem::ensureDirectory('/srv/app/.rush/code');

OperatorFile::save('/srv/app', new Operator('Developer', 'developer@example.com'));

$operator = OperatorFile::load('/srv/app');

echo $operator->name;
```

O arquivo do operador é armazenado em:

```text
.rush/operator.json
```

e é obrigatório para comandos de tools que registram quem executou uma ação.

## 🤝 Contribuições

Contribuições são bem-vindas. Abra uma issue ou envie um pull request.

## 📄 Licença

MIT. Consulte [LICENSE](LICENSE).
