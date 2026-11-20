import { cva, type VariantProps } from 'class-variance-authority';

export { default as Button } from './Button.vue';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg text-[13px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/35 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default:
                    'bg-primary text-primary-foreground hover:bg-[var(--primary-hover)]',
                outline:
                    'border border-border bg-card text-foreground hover:border-ring/50 hover:bg-secondary/60',
                ghost:
                    'text-muted-foreground hover:bg-secondary hover:text-foreground',
                link:
                    'text-secondary-foreground underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-9.5 px-4 py-2',
                sm: 'h-8.5 rounded-lg px-3 text-[12.5px]',
                lg: 'h-10.5 rounded-lg px-5 text-[14px] font-bold',
                icon: 'h-9.5 w-9.5',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
