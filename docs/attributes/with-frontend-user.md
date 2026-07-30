# [`#[WithFrontendUser]`](../../src/Attribute/WithFrontendUser.php)

_Scope: Class & Method level_

Counterpart to [`#[WithBackendUser]`](with-backend-user.md): provides a lightweight `$GLOBALS['FE_USER']` stub (a `FrontendUserAuthentication` subclass with a skipped constructor and a populated user record), registers it as the `Context`'s `frontend.user` aspect, and restores both afterwards.

`$GLOBALS['FE_USER']` is carried alongside the aspect as a compatibility fallback: it remains a common touchpoint in TYPO3 code (e.g. via `$GLOBALS['TSFE']->fe_user` or direct global access) even where the modern `Context` aspect is also read.

Requires `typo3/cms-frontend`.

## Example

```php
#[WithFrontendUser(uid: 42, groups: [1, 2])]
```

<details>
<summary>More examples</summary>

### Anonymous, explicitly not logged in

Defaults to an anonymous, not-logged-in user (`uid: 0`). Use `#[WithFrontendUser]` on its own to simulate the "not logged in" case explicitly:

```php
#[WithFrontendUser]
public function deniesActionForAnonymousUser(): void {}
```

### Logged-in user with group membership

The optional `$groups` parameter populates the aspect's `groupIds` directly (bypassing the default anonymous/logged-in group calculation), so `isMemberOfGroup()`-style checks work for tests that check frontend-user-group membership:

```php
#[WithFrontendUser(uid: 42, username: 'jane.doe', groups: [1, 2])]
public function allowsActionForGroupMembers(): void {}
```

</details>
