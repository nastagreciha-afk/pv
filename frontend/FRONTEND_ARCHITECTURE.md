# Invoice Management Frontend - Nuxt 4 / Vue 3.5 / TailwindCSS 4

## Architecture Overview

This frontend implementation follows modern best practices for building maintainable, scalable web applications with Nuxt 4, Vue 3.5, and TailwindCSS 4.

### Key Principles

1. **Separation of Concerns**: Clear division between API layer, business logic, and presentation
2. **Type Safety**: Comprehensive TypeScript usage throughout
3. **Reusability**: Composable functions for shared logic
4. **Maintainability**: Clean, well-organized code structure
5. **User Experience**: Proper loading, error, and empty states
6. **Accessibility**: Semantic HTML and proper ARIA attributes
7. **Responsive Design**: Mobile-first approach with TailwindCSS

## Folder Structure

```
frontend/
├── composables/              # Shared business logic and API calls
│   ├── useInvoiceApi.ts      # API communication layer
│   └── useFormatters.ts      # Formatting utilities
├── pages/
│   ├── invoices/
│   │   ├── index.vue         # Invoice list page
│   │   └── [id].vue          # Invoice details + edit form
│   └── index.vue             # Home page (redirects to invoices)
├── app.vue                   # Main application layout
└── nuxt.config.ts            # Nuxt configuration
```

## Core Components

### 1. API Layer (`composables/useInvoiceApi.ts`)

**Responsibilities:**
- Handles all communication with backend API
- Manages loading and error states
- Provides type-safe interfaces for API responses
- Centralizes API configuration

**Key Features:**
- TypeScript interfaces for Invoice and API responses
- Comprehensive error handling
- Loading state management
- Success/error message handling
- Methods for CRUD operations

```typescript
// Example usage in components
const { getInvoices, getInvoice, updateInvoice, isLoading, error } = useInvoiceApi();
```

### 2. Formatting Utilities (`composables/useFormatters.ts`)

**Responsibilities:**
- Currency formatting with locale support
- Date and datetime formatting
- Status badge styling
- Status text localization

**Key Features:**
- Locale-aware number and date formatting
- Consistent status badge styling
- Reusable across all components
- Type-safe parameters

```typescript
// Example usage
const { formatCurrency, formatDate, getStatusClasses, getStatusText } = useFormatters();
```

### 3. Invoice List Page (`pages/invoices/index.vue`)

**Features:**
- Paginated invoice list with 20 items per page
- Clickable rows for navigation to details
- Loading, error, and empty states
- Refresh functionality
- Responsive table design
- Status badges with proper coloring

**UX Elements:**
- Skeleton loading spinner
- Error handling with retry option
- Empty state with helpful message
- Pagination controls (mobile and desktop)
- Hover effects on rows

### 4. Invoice Details Page (`pages/invoices/[id].vue`)

**Features:**
- Complete invoice information display
- Status badge with proper coloring
- Readable date formatting
- Last updated timestamp
- Edit form with validation
- Auto-calculated gross amount
- Form disabled when status ≠ pending

**Form Validation:**
- Vee-validate + Zod integration
- Client-side validation rules
- Server-side validation on submit
- Clear error messages
- Disabled state handling

**UX Elements:**
- Back navigation button
- Loading state with spinner
- Error handling with retry
- 404 state for missing invoices
- Toast notifications for success/error
- Responsive form layout
- Clear visual feedback

## Technical Implementation Details

### TypeScript Usage

```typescript
// Strong typing for all API responses
interface Invoice {
  id: number;
  number: string;
  supplier_name: string;
  supplier_tax_id: string;
  net_amount: number;
  vat_amount: number;
  gross_amount: number;
  currency: string;
  status: 'pending' | 'approved' | 'rejected';
  issue_date: string;
  due_date: string;
  created_at: string;
  updated_at: string;
}
```

### API Communication

```typescript
// Centralized API calls with proper error handling
const getInvoices = async (page: number = 1, perPage: number = 20): Promise<PaginatedResponse<Invoice>> => {
  try {
    isLoading.value = true;
    clearMessages();
    
    const response = await $fetch<PaginatedResponse<Invoice>>(`${apiBase}/invoices`, {
      params: { page, per_page: perPage },
    });
    
    return response;
  } catch (err) {
    error.value = 'Failed to fetch invoices. Please try again.';
    console.error('API Error:', err);
    throw err;
  } finally {
    isLoading.value = false;
  }
};
```

### Form Validation

```typescript
// Zod schema for form validation
const schema = toTypedSchema(
  z
    .object({
      net_amount: z.coerce.number().gt(0, 'Net amount must be greater than 0'),
      vat_amount: z.coerce.number().min(0, 'VAT amount must be >= 0'),
      due_date: z.string().min(1, 'Due date is required'),
    })
    .refine(
      (val) => {
        if (!invoice.value) return true;
        const issue = new Date(invoice.value.issue_date);
        const due = new Date(val.due_date);
        return due >= issue;
      },
      {
        message: 'Due date must be on or after issue date',
        path: ['due_date'],
      },
    ),
);
```

### Auto-Calculated Fields

```typescript
// Computed property for gross amount
const grossComputed = computed(() => {
  const net = Number(values.net_amount || 0);
  const vat = Number(values.vat_amount || 0);
  return (net + vat).toFixed(2);
});
```

## State Management

### Loading States
- Centralized `isLoading` state in composable
- Proper loading indicators for all API calls
- Disabled form elements during submission

### Error Handling
- Centralized error state management
- User-friendly error messages
- Retry functionality
- Console logging for debugging

### Success Messages
- Toast notifications for successful operations
- Clear feedback for user actions
- Auto-clearing of messages

## Styling Approach

### TailwindCSS Usage
- Utility-first approach for rapid development
- Consistent spacing scale
- Responsive design with breakpoints
- Dark mode ready (though not implemented)
- Custom color palette

### Component Styling
- Consistent button styles
- Form input styling
- Table styling with hover effects
- Status badge styling
- Loading spinner animations

### Responsive Design
- Mobile-first approach
- Breakpoints: sm (640px), md (768px), lg (1024px)
- Flexible grid layouts
- Stacked layouts on mobile

## Routing

### Route Structure
- `/invoices` - List of all invoices
- `/invoices/[id]` - Individual invoice details
- Navigation between pages with proper back button

### Navigation
- Programmatic navigation with `navigateTo()`
- Back button functionality
- Clickable table rows

## Performance Considerations

### Optimizations Implemented
- Pagination to limit data loading
- Loading states to prevent layout shifts
- Efficient computed properties
- Proper error handling to prevent crashes
- TypeScript for better code quality

### Future Optimizations
- Add caching for API responses
- Implement lazy loading for images
- Add skeleton loading for better perceived performance
- Consider virtual scrolling for large lists

## Accessibility

### Implemented Features
- Semantic HTML structure
- Proper heading hierarchy
- ARIA attributes for interactive elements
- Keyboard navigation support
- Focus management
- Color contrast compliance

### Form Accessibility
- Proper label associations
- Input validation feedback
- Disabled state indicators
- Focus styles for keyboard users

## Error Handling Strategy

### API Errors
- Network error handling
- Server error handling
- Validation error handling
- User-friendly error messages
- Retry functionality

### Form Errors
- Client-side validation
- Server-side validation
- Clear error messages
- Visual error indicators

### Edge Cases
- Empty state handling
- 404 not found handling
- Invalid data handling
- Network failure recovery

## Testing Strategy

### Manual Testing
- All user flows tested
- Error conditions verified
- Responsive design checked
- Form validation tested

### Automated Testing (Recommended)
- Unit tests for composables
- Component tests for pages
- E2E tests for user flows
- Accessibility audits

## Deployment Considerations

### Environment Configuration
- API base URL configurable
- Proper error reporting
- Logging configuration

### Build Optimization
- Nuxt 4 production build
- Tree shaking for unused code
- Code splitting for routes
- Asset optimization

## Future Enhancements

### Potential Features
- Create new invoice functionality
- Status change workflow
- Bulk actions
- Export functionality
- Advanced filtering and search
- User authentication
- Role-based access control

### Technical Improvements
- Add Pinia for complex state management
- Implement API caching
- Add end-to-end testing
- Performance monitoring
- Error tracking integration

## Conclusion

This frontend implementation provides a clean, maintainable, and production-ready solution for the invoice management system. It follows modern best practices for Vue.js/Nuxt.js development, with proper separation of concerns, comprehensive error handling, and excellent user experience.

The architecture is designed to be easily extensible for future requirements while maintaining high code quality and developer experience.