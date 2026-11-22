import type { SharedRoutes } from '@/types/etms';

export interface NavItem {
    label: string;
    href: string;
    matchPrefix: string;
    isInertia: boolean;
}

export function getPublicNavItems(routes: SharedRoutes): NavItem[] {
    return [
        {
            label: 'Physical Sessions',
            href: routes.sessions,
            matchPrefix: '/sessions',
            isInertia: true,
        },
        {
            label: 'Standalone Modules',
            href: routes.catalogue,
            matchPrefix: '/training',
            isInertia: true,
        },
        {
            label: 'Instructors',
            href: routes.instructors,
            matchPrefix: '/instructors',
            isInertia: true,
        },
    ];
}
