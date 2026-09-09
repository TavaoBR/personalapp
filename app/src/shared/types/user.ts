export type UserStatus = 'ACTIVE' | 'INACTIVE' | 'BLOCKED';

export type TenantRole = 'OWNER' | 'ADMIN' | 'TRAINER' | 'STUDENT';

export type MembershipStatus = 'ACTIVE' | 'INACTIVE' | 'INVITED';

export interface User {
  id: string;
  email: string;
  name: string;
  phone?: string | null;
  status: UserStatus;
  isPlatformAdmin: boolean;
  createdAt: string;
}

export interface TenantMembershipInfo {
  id: string;
  tenantId: string;
  tenantName: string;
  tenantType: 'PERSONAL' | 'ASSESSORIA';
  role: TenantRole;
  status: MembershipStatus;
}
