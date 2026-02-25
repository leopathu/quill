# GitHub Copilot Instructions

## Project Overview
This is a Laravel + Vue 3 + Inertia.js application using MySQL and Tailwind CSS.

## General Coding Standards

### Code Quality
- Write clean, readable, and maintainable code
- Follow SOLID principles and DRY (Don't Repeat Yourself)
- Use meaningful variable and function names
- Add comments for complex logic, but prefer self-documenting code
- Avoid deep nesting; extract methods when necessary

### Security
- Always validate and sanitize user input
- Use Laravel's built-in security features (CSRF, XSS protection)
- Never expose sensitive data in responses
- Use parameterized queries (Eloquent) to prevent SQL injection
- Implement proper authentication and authorization

## Laravel Standards

### Code Style
- Follow PSR-12 coding standards
- Use Laravel Pint for code formatting
- Run `./vendor/bin/pint` before committing

### Best Practices
- Use Eloquent ORM instead of raw SQL queries
- Leverage Laravel's form request validation
- Use resource controllers for RESTful operations
- Implement service classes for complex business logic
- Use Laravel's dependency injection container
- Utilize Laravel collections for data manipulation
- Use database migrations for schema changes, never modify database directly
- Use seeders and factories for test data

### File Organization
- Place business logic in service classes (`app/Services/`)
- Use form requests for validation (`app/Http/Requests/`)
- Use resource classes for API responses (`app/Http/Resources/`)
- Keep controllers thin; delegate to services
- Use traits for shared model functionality

### Routing
- Use route model binding when possible
- Group related routes with middleware and prefixes
- Use named routes for easier maintenance
- Prefer resource routes for RESTful endpoints

### Database
- Always use migrations for schema changes
- Add proper indexes for foreign keys and frequently queried columns
- Use descriptive column names following snake_case convention
- Implement soft deletes where appropriate
- Use database transactions for multiple related operations

### Inertia.js Integration
- Return Inertia responses from controllers: `Inertia::render('Component', $data)`
- Use Inertia's form helper for form submissions
- Share common data via HandleInertiaRequests middleware
- Use lazy data evaluation for heavy computations
- Prefer partial reloads when appropriate

## Vue 3 Standards

### Code Style
- Use Composition API with `<script setup>` syntax
- Follow Vue 3 style guide (https://vuejs.org/style-guide/)
- Use PascalCase for component names
- Use kebab-case for component file names in multi-word scenarios

### Best Practices
- Use `defineProps()` and `defineEmits()` for component contracts
- Implement proper prop validation with types
- Keep components small and focused (Single Responsibility)
- Use computed properties for derived state
- Use `ref()` for primitive values, `reactive()` for objects
- Prefer composition functions (composables) for reusable logic
- Use `v-for` with `:key` attribute always
- Avoid mutating props; emit events instead

### Component Structure
```vue
<template>
  <!-- Template here -->
</template>

<script setup>
// Imports
import { ref, computed } from 'vue';

// Props and emits
const props = defineProps({
  // prop definitions
});

const emit = defineEmits(['event-name']);

// Composables and state
// Computed properties
// Methods
// Lifecycle hooks
</script>

<style scoped>
/* Component-specific styles */
</style>
```

### File Organization
- Place reusable components in `resources/js/Components/`
- Page components go in `resources/js/Pages/`
- Composables in `resources/js/Composables/`
- Layouts in `resources/js/Layouts/`
- Use index files for cleaner imports when appropriate

### Inertia.js + Vue Integration
- Use `usePage()` for accessing shared data
- Use `useForm()` for Inertia-aware form handling
- Use `Link` component for client-side navigation
- Implement proper loading states during navigation

## Tailwind CSS Standards

### Best Practices
- Use Tailwind utility classes instead of custom CSS when possible
- Extract repeated patterns into components
- Use Tailwind's configuration for custom colors, spacing, etc.
- Follow mobile-first responsive design (`sm:`, `md:`, `lg:`, `xl:`)
- Use Tailwind's arbitrary values sparingly
- Prefer semantic color names in configuration

### Class Organization
- Group related utilities logically
- Use `@apply` directive sparingly (only in base styles)
- Keep utility classes readable with proper spacing

## Testing Standards

### PHP/Laravel Testing
- Write feature tests for all endpoints
- Use PHPUnit for unit and feature tests
- Test happy paths and edge cases
- Use database factories and seeders for test data
- Mock external dependencies
- Run tests before committing: `php artisan test`

### Vue Testing
- Test component behavior, not implementation details
- Use Vitest or Vue Test Utils for component testing
- Test user interactions and expected outcomes
- Mock Inertia helpers in tests

## Git Commit Standards

### Commit Messages
- Use conventional commits format:
  - `feat:` new feature
  - `fix:` bug fix
  - `refactor:` code refactoring
  - `docs:` documentation changes
  - `test:` adding or updating tests
  - `style:` code style changes (formatting)
  - `chore:` maintenance tasks

### Example
```
feat: add user profile page with edit functionality

- Created ProfileController with show and update methods
- Added Profile.vue component with form validation
- Implemented profile update API endpoint
```

## Performance Optimization

### Laravel
- Use eager loading to prevent N+1 queries
- Cache expensive operations
- Use queue jobs for long-running tasks
- Optimize database queries with proper indexing
- Use chunking for large datasets

### Vue
- Use `v-show` for frequently toggled elements
- Use `v-if` for conditional rendering
- Implement lazy loading for routes and components
- Avoid unnecessary watchers; use computed properties
- Use virtual scrolling for long lists

## Accessibility Standards

- Use semantic HTML elements
- Provide proper ARIA labels and roles
- Ensure keyboard navigation works properly
- Maintain proper heading hierarchy
- Test with screen readers
- Ensure proper color contrast ratios
- Add alt text to images

## Environment-Specific Guidelines

### Development
- Use descriptive error messages
- Enable debug mode in `.env` (`APP_DEBUG=true`)
- Log queries for optimization
- Use Laravel Telescope for debugging (if installed)

### Production
- Disable debug mode (`APP_DEBUG=false`)
- Use queue workers for async jobs
- Enable caching (config, routes, views)
- Minimize and version assets
- Use CDN for static assets
- Implement proper error logging and monitoring

## Code Review Checklist

Before submitting code:
- [ ] Code follows project conventions and standards
- [ ] All tests pass
- [ ] No console errors or warnings
- [ ] Proper error handling implemented
- [ ] Security best practices followed
- [ ] Code is properly documented
- [ ] Performance considerations addressed
- [ ] Accessibility requirements met
- [ ] Database migrations are reversible
- [ ] No sensitive data in version control
