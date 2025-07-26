🛡️ Security Tips
----------------

For private update servers or GitHub repos, consider these practices:

- Use **HTTPS** for all update servers.
- Avoid committing your `github_token` directly into public repositories.
- Use `key` authentication for private update endpoints:

```php
'key' => 'your-secret-key',
```

- Limit write access to your `index.json` or deployment tools.
- Rotate your GitHub token regularly and use a token with minimal required scopes.
