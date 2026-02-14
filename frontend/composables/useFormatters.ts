import { computed } from 'vue';

export function useFormatters() {
  /**
   * Format currency value
   */
  const formatCurrency = (value: number | string, currency: string = 'UAH'): string => {
    return new Intl.NumberFormat('uk-UA', {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(Number(value));
  };

  /**
   * Format date for display
   */
  const formatDate = (value: string | Date): string => {
    return new Intl.DateTimeFormat('uk-UA', {
      dateStyle: 'medium',
    }).format(new Date(value));
  };

  /**
   * Format date-time for display
   */
  const formatDateTime = (value: string | Date): string => {
    return new Intl.DateTimeFormat('uk-UA', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(new Date(value));
  };

  /**
   * Get status badge classes
   */
  const getStatusClasses = (status: 'pending' | 'approved' | 'rejected'): string => {
    const baseClasses = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1';
    
    switch (status) {
      case 'approved':
        return `${baseClasses} bg-emerald-100 text-emerald-700 ring-emerald-200`;
      case 'rejected':
        return `${baseClasses} bg-rose-100 text-rose-700 ring-rose-200`;
      default: // pending
        return `${baseClasses} bg-amber-100 text-amber-700 ring-amber-200`;
    }
  };

  /**
   * Get status text
   */
  const getStatusText = (status: 'pending' | 'approved' | 'rejected'): string => {
    const statusMap: Record<string, string> = {
      pending: 'Pending',
      approved: 'Approved',
      rejected: 'Rejected',
    };
    
    return statusMap[status] || status;
  };

  return {
    formatCurrency,
    formatDate,
    formatDateTime,
    getStatusClasses,
    getStatusText,
  };
}