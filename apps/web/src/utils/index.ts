/**
 * Format a number as a currency string.
 * @example formatCurrency(1234.5, 'EUR') → '€1,234.50'
 */
export function formatCurrency(amount: number, currency = "EUR", locale = "en-GB"): string {
  return new Intl.NumberFormat(locale, {
    style: "currency",
    currency,
    minimumFractionDigits: 2,
  }).format(amount);
}

/**
 * Format a date as a locale date string.
 */
export function formatDate(date: Date | string, locale = "en-GB"): string {
  return new Intl.DateTimeFormat(locale, {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(new Date(date));
}

/**
 * Clamp a number between min and max.
 */
export function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}

/**
 * Calculate total buffer cost from items.
 */
export function calculateBufferCost(
  items: { quantity: number; dish: { costPerUnit: number | string } }[]
): number {
  return items.reduce(
    (total, item) => total + item.quantity * Number(item.dish.costPerUnit),
    0
  );
}
