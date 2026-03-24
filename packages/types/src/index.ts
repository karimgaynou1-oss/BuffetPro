// ─── Enums ───────────────────────────────────────────────────────────────────

export enum UserRole {
  SUPER_ADMIN = "SUPER_ADMIN",
  HOTEL_ADMIN = "HOTEL_ADMIN",
  CHEF = "CHEF",
  COORDINATOR = "COORDINATOR",
  CLIENT = "CLIENT",
}

export enum SubscriptionPlan {
  STARTER = "STARTER",
  PROFESSIONAL = "PROFESSIONAL",
  ENTERPRISE = "ENTERPRISE",
}

export enum SubscriptionStatus {
  ACTIVE = "ACTIVE",
  TRIALING = "TRIALING",
  PAST_DUE = "PAST_DUE",
  CANCELED = "CANCELED",
  UNPAID = "UNPAID",
}

export enum BufferStatus {
  DRAFT = "DRAFT",
  PUBLISHED = "PUBLISHED",
  RECEIVED = "RECEIVED",
  CUSTOMIZED = "CUSTOMIZED",
  FINALIZED = "FINALIZED",
}

export enum DishCategory {
  STARTER = "STARTER",
  MAIN = "MAIN",
  DESSERT = "DESSERT",
  BEVERAGE = "BEVERAGE",
  SIDE = "SIDE",
  BREAD = "BREAD",
  SALAD = "SALAD",
}

// ─── Entity Types ─────────────────────────────────────────────────────────────

export interface Hotel {
  id: string;
  name: string;
  slug: string;
  logoUrl?: string;
  primaryColor?: string;
  timezone: string;
  locale: string;
  createdAt: Date;
  updatedAt: Date;
}

export interface User {
  id: string;
  hotelId: string;
  email: string;
  name: string;
  role: UserRole;
  avatarUrl?: string;
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface Allergen {
  id: string;
  code: string;
  label: string;
  labelFr?: string;
  labelEs?: string;
  icon?: string;
}

export interface Dish {
  id: string;
  hotelId: string;
  name: string;
  nameFr?: string;
  nameEs?: string;
  description?: string;
  category: DishCategory;
  unit: string;
  costPerUnit: number;
  portionSize: number;
  imageUrl?: string;
  isActive: boolean;
  allergens: Allergen[];
  createdAt: Date;
  updatedAt: Date;
}

export interface BufferItem {
  id: string;
  bufferId: string;
  dishId: string;
  dish?: Dish;
  quantity: number;
  portionCount: number;
  notes?: string;
  sortOrder: number;
}

export interface Buffer {
  id: string;
  hotelId: string;
  chefId: string;
  chef?: User;
  coordinatorId?: string;
  coordinator?: User;
  name: string;
  eventDate: Date;
  guestCount: number;
  status: BufferStatus;
  notes?: string;
  items: BufferItem[];
  totalCost: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface Subscription {
  id: string;
  hotelId: string;
  stripeCustomerId: string;
  stripeSubscriptionId: string;
  stripePriceId: string;
  plan: SubscriptionPlan;
  status: SubscriptionStatus;
  currentPeriodStart: Date;
  currentPeriodEnd: Date;
  cancelAtPeriodEnd: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface AuditLog {
  id: string;
  hotelId: string;
  userId: string;
  user?: User;
  action: string;
  entity: string;
  entityId: string;
  oldValues?: Record<string, unknown>;
  newValues?: Record<string, unknown>;
  ipAddress?: string;
  userAgent?: string;
  createdAt: Date;
}

// ─── API Types ────────────────────────────────────────────────────────────────

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
}

export interface PaginatedResponse<T> {
  items: T[];
  total: number;
  page: number;
  pageSize: number;
  totalPages: number;
}

export interface PaginationParams {
  page?: number;
  pageSize?: number;
}

// ─── Session / Auth Types ─────────────────────────────────────────────────────

export interface SessionUser {
  id: string;
  email: string;
  name: string;
  role: UserRole;
  hotelId: string;
  hotelSlug: string;
}

export interface JwtPayload {
  sub: string;
  email: string;
  role: UserRole;
  hotelId: string;
  hotelSlug: string;
  iat?: number;
  exp?: number;
}

// ─── Request / DTO Types ──────────────────────────────────────────────────────

export interface CreateDishDto {
  name: string;
  nameFr?: string;
  nameEs?: string;
  description?: string;
  category: DishCategory;
  unit: string;
  costPerUnit: number;
  portionSize: number;
  imageUrl?: string;
  allergenIds?: string[];
}

export interface UpdateDishDto extends Partial<CreateDishDto> {}

export interface CreateBufferDto {
  name: string;
  eventDate: Date;
  guestCount: number;
  notes?: string;
  items: {
    dishId: string;
    quantity: number;
    portionCount: number;
    notes?: string;
    sortOrder: number;
  }[];
}

export interface UpdateBufferDto extends Partial<CreateBufferDto> {
  status?: BufferStatus;
  coordinatorId?: string;
}
