# Contributing Guide

Thank you for considering contributing! We welcome contributions from everyone.

## Development Process

1. Fork the repository
2. Create a feature branch (`git checkout -b feat/amazing-feature`)
3. Make your changes following our standards
4. Write/update tests
5. Run quality checks
6. Submit a pull request

## Setup Development Environment

```bash
# Clone your fork
git clone git@github.com:YOUR_USERNAME/PACKAGE_NAME.git
cd PACKAGE_NAME

# Install dependencies
composer install

# Run tests
composer test
```

## Coding Standards

We follow PSR-12 and enforce it with PHP CS Fixer.

```bash
# Auto-fix code style
composer fmt

# Check code quality (CS Fixer + PHPStan + Security audit)
composer lint

# Run tests
composer test
```

## Commit Message Format

We use [Conventional Commits](https://www.conventionalcommits.org/) for automated versioning.

### Format

```
<type>(<scope>): <subject>

[optional body]

[optional footer]
```

### Types

| Type | Description | Example |
|------|-------------|---------|
| `feat` | New feature | `feat: add field filtering` |
| `fix` | Bug fix | `fix: prevent null pointer` |
| `docs` | Documentation | `docs: update API examples` |
| `style` | Code style | `style: format code` |
| `refactor` | Code refactoring | `refactor: simplify logic` |
| `perf` | Performance | `perf: optimize query` |
| `test` | Tests | `test: add edge cases` |
| `chore` | Maintenance | `chore: update deps` |
| `ci` | CI/CD | `ci: add PHP 8.4` |

### Examples

```bash
# Good ✅
git commit -m "feat: add support for soft deletes"
git commit -m "fix(validation): handle empty arrays"
git commit -m "docs: add usage examples to README"

# Bad ❌
git commit -m "Update code"
git commit -m "Fix bug"
git commit -m "Added new feature."
```

### Breaking Changes

```bash
# With ! suffix
git commit -m "feat!: change API signature"

# With footer
git commit -m "feat: redesign API

BREAKING CHANGE: Method signature changed from foo(a) to foo(a, b)"
```

## Testing

### Writing Tests

- Place unit tests in `tests/Unit/`
- Place feature tests in `tests/Feature/`
- Follow existing patterns
- Aim for high coverage

```php
public function test_it_does_something(): void
{
    // Arrange
    $model = TestModel::create(['name' => 'Test']);
    
    // Act
    $result = $model->doSomething();
    
    // Assert
    $this->assertEquals('expected', $result);
}
```

### Running Tests

```bash
# All tests
composer test

# With coverage
./vendor/bin/phpunit --coverage-html coverage

# Specific test
./vendor/bin/phpunit --filter test_name
```

## Pull Request Guidelines

### Before Submitting

- ✅ Code follows PSR-12 (`composer fmt`)
- ✅ All tests pass (`composer test`)
- ✅ PHPStan level 9 passes (`composer lint`)
- ✅ Security audit clean (`composer lint`)
- ✅ New features have tests
- ✅ Documentation updated

### PR Title

Use conventional commit format:

```
feat: add pagination support
fix: correct version calculation
docs: improve installation guide
```

### PR Description

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
Describe how you tested changes

## Checklist
- [ ] Tests added/updated
- [ ] Documentation updated
- [ ] Code formatted
- [ ] All checks passing
```

## Development Workflow

### Feature Development

```bash
# Create feature branch
git checkout -b feat/new-feature

# Make changes
# ... edit files ...

# Check quality
composer fmt
composer lint
composer test

# Commit using conventional format
git commit -m "feat: add new feature"

# Push and create PR
git push origin feat/new-feature
```

### Bug Fixes

```bash
# Create fix branch
git checkout -b fix/bug-description

# Fix the issue
# ... edit files ...

# Add regression test
# ... add test ...

# Verify fix
composer test

# Commit
git commit -m "fix: resolve bug description"

# Push and create PR
git push origin fix/bug-description
```

## Code Review Process

1. Maintainers review your PR
2. Address feedback if requested
3. Once approved, PR will be merged
4. Changes will be included in next release

## Reporting Issues

### Bug Reports

Include:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Environment (PHP version, Laravel version, package version)
- Code example if possible

### Feature Requests

Include:
- Clear description of feature
- Use case and motivation
- Proposed implementation (optional)

## Questions?

Feel free to open an issue or discussion if you have questions.

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
