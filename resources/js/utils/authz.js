export function getStoredUser() {
  const raw = localStorage.getItem('user');
  if (!raw || raw === 'undefined') return null;

  try {
    return JSON.parse(raw);
  } catch (error) {
    localStorage.removeItem('user');
    return null;
  }
}

export function setStoredUser(user) {
  localStorage.setItem('user', JSON.stringify(user || {}));
}

export function clearStoredUser() {
  localStorage.removeItem('user');
}

export function getRoleSlugs(user) {
  if (!Array.isArray(user?.roles)) return [];
  return user.roles
    .filter(role => role?.is_active)
    .map(role => role.slug);
}

export function isAdminUser(user) {
  const roleSlugs = getRoleSlugs(user);
  return roleSlugs.includes('admin') || Boolean(user?.is_admin);
}

export function getPermissionSlugs(user) {
  if (!Array.isArray(user?.roles)) return [];
  return user.roles
    .filter(role => role?.is_active)
    .flatMap(role =>
    Array.isArray(role.permissions) ? role.permissions.map(permission => permission.slug) : []
  );
}

export function hasPermission(user, permission) {
  return isAdminUser(user) || getPermissionSlugs(user).includes(permission);
}

export function hasAllPermissions(user, permissions) {
  return permissions.every(permission => hasPermission(user, permission));
}
