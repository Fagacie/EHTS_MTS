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
            label: 'Catalogue',
            href: routes.catalogue,
            matchPrefix: '/training',
            isInertia: true,
        },
        {
            label: 'Sessions',
            href: routes.sessions,
            matchPrefix: '/sessions',
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
