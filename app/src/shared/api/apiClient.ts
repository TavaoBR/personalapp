export interface RequestOptions extends RequestInit {
  params?: Record<string, string | number | boolean | null | undefined>;
}

class ApiClient {
  private baseUrl: string;
  private isRefreshing: boolean = false;
  private refreshSubscribers: Array<(token: string) => void> = [];

  constructor() {
    this.baseUrl = (import.meta.env.VITE_API_BASE_URL as string) || '/api';
  }

  private getToken(): string | null {
    return localStorage.getItem('access_token');
  }

  private getActiveTenantId(): string | null {
    return localStorage.getItem('active_tenant_id');
  }

  public setTokens(accessToken: string): void {
    localStorage.setItem('access_token', accessToken);
  }

  public clearTokens(): void {
    localStorage.removeItem('access_token');
    localStorage.removeItem('active_tenant_id');
  }

  public setActiveTenantId(tenantId: string): void {
    localStorage.setItem('active_tenant_id', tenantId);
  }

  private onRefreshed(token: string): void {
    this.refreshSubscribers.forEach((callback) => callback(token));
    this.refreshSubscribers = [];
  }

  private addRefreshSubscriber(callback: (token: string) => void): void {
    this.refreshSubscribers.push(callback);
  }

  public async request<T = any>(endpoint: string, options: RequestOptions = {}): Promise<T> {
    const { params, headers, ...customConfig } = options;

    let url = endpoint.startsWith('http') ? endpoint : `${this.baseUrl}${endpoint.startsWith('/') ? '' : '/'}${endpoint}`;

    if (params) {
      const searchParams = new URLSearchParams();
      Object.entries(params).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') {
          searchParams.append(k, String(v));
        }
      });
      const queryString = searchParams.toString();
      if (queryString) {
        url += (url.includes('?') ? '&' : '?') + queryString;
      }
    }

    const token = this.getToken();
    const activeTenantId = this.getActiveTenantId();

    const requestHeaders: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...((headers as Record<string, string>) || {}),
    };

    if (token) {
      requestHeaders['Authorization'] = `Bearer ${token}`;
    }

    if (activeTenantId) {
      requestHeaders['X-Tenant-ID'] = activeTenantId;
    }

    const config: RequestInit = {
      ...customConfig,
      headers: requestHeaders,
    };

    try {
      const response = await fetch(url, config);

      if (response.status === 401 && !endpoint.includes('/auth/login') && !endpoint.includes('/auth/refresh')) {
        // Tenta renovar o token via refresh endpoint
        if (!this.isRefreshing) {
          this.isRefreshing = true;
          try {
            const refreshRes = await fetch(`${this.baseUrl}/auth/refresh`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              credentials: 'include',
            });

            if (refreshRes.ok) {
              const data = await refreshRes.json();
              const newToken = data.access_token;
              this.setTokens(newToken);
              this.isRefreshing = false;
              this.onRefreshed(newToken);

              // Repete a requisição original
              requestHeaders['Authorization'] = `Bearer ${newToken}`;
              const retryRes = await fetch(url, { ...config, headers: requestHeaders });
              return (await retryRes.json()) as T;
            } else {
              this.clearTokens();
              this.isRefreshing = false;
              window.dispatchEvent(new CustomEvent('auth:expired'));
              throw new Error('Sessão expirada. Por favor, faça login novamente.');
            }
          } catch (err) {
            this.clearTokens();
            this.isRefreshing = false;
            window.dispatchEvent(new CustomEvent('auth:expired'));
            throw err;
          }
        } else {
          // Aguarda o refresh em andamento
          return new Promise<T>((resolve) => {
            this.addRefreshSubscriber(async (newToken: string) => {
              requestHeaders['Authorization'] = `Bearer ${newToken}`;
              const retryRes = await fetch(url, { ...config, headers: requestHeaders });
              resolve((await retryRes.json()) as T);
            });
          });
        }
      }

      if (!response.ok) {
        let errorData: any = {};
        try {
          errorData = await response.json();
        } catch {
          errorData = { message: response.statusText || 'Erro na requisição' };
        }

        const error = new Error(errorData.message || errorData.error || 'Erro na requisição');
        (error as any).status = response.status;
        (error as any).data = errorData;
        throw error;
      }

      // 204 No Content
      if (response.status === 204) {
        return null as unknown as T;
      }

      return (await response.json()) as T;
    } catch (error) {
      throw error;
    }
  }

  public get<T = any>(endpoint: string, options?: RequestOptions): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'GET' });
  }

  public post<T = any>(endpoint: string, data?: any, options?: RequestOptions): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: 'POST',
      body: data !== undefined ? JSON.stringify(data) : undefined,
    });
  }

  public put<T = any>(endpoint: string, data?: any, options?: RequestOptions): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: 'PUT',
      body: data !== undefined ? JSON.stringify(data) : undefined,
    });
  }

  public patch<T = any>(endpoint: string, data?: any, options?: RequestOptions): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: 'PATCH',
      body: data !== undefined ? JSON.stringify(data) : undefined,
    });
  }

  public delete<T = any>(endpoint: string, options?: RequestOptions): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: 'DELETE' });
  }
}

export const apiClient = new ApiClient();
