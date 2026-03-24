import { formatCurrency, formatDate, clamp, calculateBufferCost } from "../utils";

describe("utils", () => {
  describe("formatCurrency", () => {
    it("formats EUR by default", () => {
      const result = formatCurrency(1234.5);
      expect(result).toContain("1,234.50");
    });

    it("formats USD", () => {
      const result = formatCurrency(99.9, "USD", "en-US");
      expect(result).toContain("99.90");
    });
  });

  describe("formatDate", () => {
    it("formats a Date object", () => {
      const date = new Date("2024-03-15");
      const result = formatDate(date);
      expect(result).toContain("2024");
    });

    it("formats an ISO string", () => {
      const result = formatDate("2024-01-01");
      expect(result).toContain("2024");
    });
  });

  describe("clamp", () => {
    it("clamps below min", () => {
      expect(clamp(-5, 0, 100)).toBe(0);
    });

    it("clamps above max", () => {
      expect(clamp(150, 0, 100)).toBe(100);
    });

    it("returns value within range", () => {
      expect(clamp(42, 0, 100)).toBe(42);
    });
  });

  describe("calculateBufferCost", () => {
    it("returns 0 for empty items", () => {
      expect(calculateBufferCost([])).toBe(0);
    });

    it("sums quantity * costPerUnit", () => {
      const items = [
        { quantity: 10, dish: { costPerUnit: 2.5 } },
        { quantity: 5, dish: { costPerUnit: 4.0 } },
      ];
      expect(calculateBufferCost(items)).toBe(45);
    });

    it("handles string costPerUnit (Prisma Decimal)", () => {
      const items = [{ quantity: 3, dish: { costPerUnit: "10.50" } }];
      expect(calculateBufferCost(items)).toBe(31.5);
    });
  });
});
