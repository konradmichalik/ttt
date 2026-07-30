# [`#[WithBackendUser]`](../../src/Attribute/WithBackendUser.php)

_Scope: Class & Method level_

Provides a lightweight `$GLOBALS['BE_USER']` stub (a `BackendUserAuthentication` subclass with a skipped constructor and a populated user record), registers it as the `Context`'s `backend.user` aspect, and restores both afterwards.

Requires `typo3/cms-core`.

## Example

```php
#[WithBackendUser(admin: true)]
public function allowsAdminOnlyAction(): void {}
```

<details>
<summary>More examples</summary>

### Non-admin user with a custom uid/username

```php
#[WithBackendUser(uid: 42, username: 'editor')]
public function deniesAdminOnlyAction(): void {}
```

### Group membership

The optional `$groups` parameter populates `userGroupsUID`, so `isMemberOfGroup()` works for tests that check backend-user-group membership:

```php
#[WithBackendUser(groups: [3, 7])]
public function allowsActionForGroupMembers(): void {}
```

### Workspace context

```php
#[WithBackendUser(workspace: 1)]
public function operatesInsideDraftWorkspace(): void {}
```

</details>

## Migrating from hand-written code

**Before:**

```php
$user = $this->createMock(BackendUserAuthentication::class);
$user->method('isAdmin')->willReturn(true);
$GLOBALS['BE_USER'] = $user;
// tearDown: unset($GLOBALS['BE_USER']);
```

**After:**

```php
#[Test]
#[WithBackendUser(admin: true, uid: 42)]
public function showsIndicatorForAdmins(): void {}
```

The stub is a real `BackendUserAuthentication` subclass with a populated `user` array: `isAdmin()`, `$user->user['uid']` etc. work without mock configuration. Where tests need specific mock behavior (e.g. `check()` expectations), stay with the mock; the attribute covers the 80% case "there just needs to be an (admin) user".
