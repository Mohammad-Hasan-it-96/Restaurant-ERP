/**
 * Feature-flag helper for the customer SPA.
 *
 * The public settings payload (GET /api/v1/settings/public) includes a
 * `features` object — the whitelisted, client-safe subset of system feature
 * flags, nested by group, e.g. { orders: { modification: true }, ... }.
 *
 * Read a flag with a dot-path against the `settings` object passed down from
 * useRestaurantData():
 *
 *   featureEnabled(settings, 'orders.modification')
 *
 * Defaults to TRUE when the flag is missing so a settings payload that hasn't
 * loaded yet (or an older backend) behaves like before rather than hiding
 * everything.
 */
export function featureEnabled(settings, path) {
  const features = settings?.features;
  if (!features) return true; // not loaded / not provided → assume enabled

  const value = path.split('.').reduce((acc, key) => (acc == null ? undefined : acc[key]), features);
  return value === undefined ? true : !!value;
}
