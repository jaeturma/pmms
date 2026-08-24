import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';

export default function AuthLayout({
    title = '',
    description = '',
    wide = false,
    split = false,
    gradient = false,
    children,
}: {
    title?: string;
    description?: string;
    wide?: boolean;
    split?: boolean;
    gradient?: boolean;
    children: React.ReactNode;
}) {
    if (split) {
        return (
            <AuthSplitLayout title={title} description={description}>
                {children}
            </AuthSplitLayout>
        );
    }

    return (
        <AuthLayoutTemplate
            title={title}
            description={description}
            wide={wide}
            gradient={gradient}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
