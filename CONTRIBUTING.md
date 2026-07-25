# Contributing to ON Toolkit

First off, thank you for considering contributing to ON Toolkit! It's people like you that make ON Toolkit such a great tool.

## Where do I go from here?

If you've noticed a bug or have a feature request, make one! It's generally best if you get confirmation of your bug or approval for your feature request this way before starting to code.

## Developing

1. Fork the repo and create your branch from `main`.
2. Run `composer install` to install dependencies.
3. If you've added code that should be tested, add tests in the `tests/` directory.
4. Ensure the test suite passes (`composer test`).
5. Ensure your code passes standard checks (`composer phpcs` and `composer phpstan`).

## Code Style

We follow modern WordPress PSR-4 OOP standards. Our `phpcs.xml` covers the exact ruleset. Before committing, please run:
```bash
composer phpcbf
composer phpcs
```

## Pull Requests

1. Describe your changes clearly in the PR description.
2. Link any relevant issues.
3. Make sure all GitHub Actions CI checks pass.

Thank you for contributing!
